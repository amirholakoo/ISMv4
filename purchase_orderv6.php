<?php
include 'connect_db.php';

// Function to calculate total price
function calculateTotalPrice($netWeight, $pricePerKg, $shippingCost, $vat) {
    $totalPrice = ($netWeight * $pricePerKg) + $shippingCost;
    if ($vat) {
        $totalPrice += $totalPrice * 0.09; // 9% VAT
    }
    return $totalPrice;
}

// Update Shipment with Supplier and Material Details
if (isset($_POST['update_shipment'])) {
    $licenseNumber = $_POST['license_number'];
    $supplierID = $_POST['supplier_name'];
    $materialID = $_POST['material_id'];

    // Fetching material details
    $materialQuery = "SELECT MaterialType, MaterialName FROM RawMaterials WHERE MaterialID = ?";
    $materialStmt = $conn->prepare($materialQuery);
    $materialStmt->bind_param("i", $materialID);
    $materialStmt->execute();
    $materialResult = $materialStmt->get_result();
    $materialRow = $materialResult->fetch_assoc();

    // Updating Shipment
    $updateShipmentQuery = "UPDATE Shipments SET SupplierName = (SELECT SupplierName FROM Suppliers WHERE SupplierID = ?), MaterialID = ?, MaterialType = ?, MaterialName = ? WHERE LicenseNumber = ? AND Status = 'Incoming' AND Location IN ('Entrance', 'LoadingUnloading', 'LoadedUnloaded')";
    $updateShipmentStmt = $conn->prepare($updateShipmentQuery);
    $updateShipmentStmt->bind_param("iisss", $supplierID, $materialID, $materialRow['MaterialType'], $materialRow['MaterialName'], $licenseNumber);

    if ($updateShipmentStmt->execute()) {
        echo "<p style='color:green;'>Shipment updated successfully for $licenseNumber.</p>";
    } else {
        echo "<p style='color:red;'>Error updating shipment: " . $updateShipmentStmt->error . "</p>";
    }
    $updateShipmentStmt->close();
    $materialStmt->close();
}

// Handle Purchase Order Creation
if (isset($_POST['create_purchase'])) {
    // [Same logic as provided in the previous script for creating purchase orders]
  // Extract form data
    $licenseNumber = $_POST['license_number'];
    $supplierName = $_POST['supplier_name'];
    $materialType = $_POST['material_type'];
    $materialName = $_POST['material_name'];
    $weight1 = $_POST['weight1'];
    $weight2 = $_POST['weight2'];
    $netWeight = abs($weight1 - $weight2);
    $pricePerKg = $_POST['price_per_kg'];
    $shippingCost = $_POST['shipping_cost'];
    $vat = isset($_POST['vat']) ? 1 : 0;
    $invoiceStatus = $_POST['invoice_status'];
    $paymentStatus = $_POST['payment_status'];
    $invoiceInfo = $_POST['invoice_info'];
    $documentInfo = $_POST['document_info'];
    $comments = $_POST['comments'];
    $exitTime = date("Y-m-d H:i:s");
    $totalPrice = calculateTotalPrice($netWeight, $pricePerKg, $shippingCost, $vat);

    // Begin Transaction
    $conn->begin_transaction();

    try {
        // Insert into Purchases
        $insertPurchase = $conn->prepare("INSERT INTO Purchases (SupplierID, TruckID, Weight1, Weight2, NetWeight, PricePerKG, ShippingCost, VAT, TotalPrice, InvoiceStatus, PaymentStatus, InvoiceNumber, DocumentInfo, Comments) VALUES ((SELECT SupplierID FROM Suppliers WHERE SupplierName = ?), (SELECT TruckID FROM Trucks WHERE LicenseNumber = ?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertPurchase->bind_param("sddddddisssss", $supplierName, $licenseNumber, $weight1, $weight2, $netWeight, $pricePerKg, $shippingCost, $vat, $totalPrice, $invoiceStatus, $paymentStatus, $invoiceInfo, $documentInfo, $comments);
        $insertPurchase->execute();
        $purchaseID = $conn->insert_id;

        // Update Shipments
        $updateShipment = $conn->prepare("UPDATE Shipments SET ExitTime = ?, PricePerKG = ?, ShippingCost = ?, PurchaseID = ?, VAT = ?, InvoiceStatus = ?, PaymentStatus = ?, DocumentInfo = ?, Comments = ?, Status = 'Delivered', Location = 'Delivered' WHERE LicenseNumber = ?");
        $updateShipment->bind_param("sddissssss", $exitTime, $pricePerKg, $shippingCost, $purchaseID, $vat, $invoiceStatus, $paymentStatus, $documentInfo, $comments, $licenseNumber);
        $updateShipment->execute();

        // Update Truck Status to Free
        $updateTruck = $conn->prepare("UPDATE Trucks SET Status = 'Free' WHERE LicenseNumber = ?");
        $updateTruck->bind_param("s", $licenseNumber);
        $updateTruck->execute();

        $conn->commit();
        echo "<p style='color:green;'>Purchase order created and shipment updated successfully.</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error creating purchase order: " . $e->getMessage() . "</p>";
    }
}

// Fetch Incoming Trucks for Updating Shipments
$incomingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location IN ('Entrance', 'LoadingUnloading', 'LoadedUnloaded')";
$incomingTrucksResult = $conn->query($incomingTrucksQuery);

// Fetch Suppliers for Dropdown
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// Fetch Trucks with Incoming and Location: Office for Purchase Orders
$officeTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location = 'Office'";
$officeTrucksResult = $conn->query($officeTrucksQuery);

// HTML Form for Updating Shipment with Supplier and Material Details
echo "<form method='post'>";
echo "<h2>Update Shipment with Supplier and Material Details</h2>";
// [Same form elements as provided in the previous script for updating shipments]
echo "Truck (License Number): <select name='license_number'>";
while ($row = $incomingTrucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

echo "Supplier Name: <select name='supplier_name' onchange='this.form.submit()'>";
echo "<option value=''>Choose a Supplier</option>";
while ($row = $suppliersResult->fetch_assoc()) {
    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

// Displaying Materials based on selected Supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["supplier_name"])) {
    $selectedSupplierID = $_POST["supplier_name"];
    $materialsQuery = "SELECT MaterialID, MaterialType, MaterialName FROM RawMaterials WHERE SupplierID = $selectedSupplierID";
    $materialsResult = $conn->query($materialsQuery);

    if ($materialsResult->num_rows > 0) {
        echo "<label for='material_id'>Select Material:</label>";
        echo "<select name='material_id'>";
        while ($row = $materialsResult->fetch_assoc()) {
            echo "<option value='" . $row['MaterialID'] . "'>" . $row['MaterialType'] . " - " . $row['MaterialName'] . "</option>";
        }
        echo "</select> <br>";
        echo "<input type='submit' name='update_shipment' value='Update Shipment'>";
    } else {
        echo "No materials for selected supplier.";
    }
}

echo "</form>";

// HTML Form for Creating Purchase Order
echo "<form method='post'>";
echo "<h2>Create Purchase Order</h2>";
// [Same form elements as provided in the previous script for creating purchase orders]
echo "Truck (License Number): <select name='license_number'>";
while ($row = $officeTrucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

// Supplier, Material Type, and Material Name
echo "Supplier Name: <select name='supplier_name'>";
while ($row = $suppliersResult->fetch_assoc()) {
    echo "<option value='" . $row['SupplierName'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

echo "Material Type: <input type='text' name='material_type' required><br>";
echo "Material Name: <input type='text' name='material_name' required><br>";

// Weight, Price, and Other Details
echo "Weight1: <input type='number' name='weight1' required><br>";
echo "Weight2: <input type='number' name='weight2' required><br>";
echo "Price per KG: <input type='number' step='0.01' name='price_per_kg' required><br>";
echo "Shipping Cost: <input type='number' step='0.01' name='shipping_cost' required><br>";
echo "VAT: <input type='checkbox' name='vat'><br>";
echo "Invoice Status: <select name='invoice_status'><option value='Received'>Received</option><option value='NA'>NA</option></select><br>";
echo "Payment Status: <select name='payment_status'><option value='Paid'>Paid</option><option value='Terms'>Terms</option></select><br>";
echo "Invoice Info: <input type='text' name='invoice_info'><br>";
echo "Documentation Info: <input type='text' name='document_info'><br>";
echo "Comments: <textarea name='comments'></textarea><br>";
echo "<input type='submit' name='create_purchase' value='Create Purchase'>";

echo "</form>";

echo "</body></html>";

$conn->close();
?>
