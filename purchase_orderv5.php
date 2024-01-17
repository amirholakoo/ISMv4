<?php
include 'connect_db.php';

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

// Create Purchase Order
if (isset($_POST['create_purchase'])) {
    // Extract and sanitize input data
    $licenseNumber = $_POST['license_number_purchase'];
    $supplierID = $_POST['supplier_name_purchase'];
    $materialID = $_POST['material_id_purchase'];
    $weight1 = $_POST['weight1'];
    $weight2 = $_POST['weight2'];
    $netWeight = abs($weight1 - $weight2);
    $pricePerKG = $_POST['price_per_kg'];
    $shippingCost = $_POST['shipping_cost'];
    $vatApplicable = isset($_POST['vat']) ? 'YES' : 'NO';
    $totalPrice = $netWeight * $pricePerKG + $shippingCost;
    if ($vatApplicable == 'YES') {
        $totalPrice *= 1.09; // Adding 9% VAT
    }
    $invoiceStatus = $_POST['invoice_status'];
    $paymentStatus = $_POST['payment_status'];
    $invoiceNumber = $conn->real_escape_string($_POST['invoice_number']);
    $documentInfo = $conn->real_escape_string($_POST['document_info']);
    $comments = $conn->real_escape_string($_POST['comments']);
    $exitTime = date("Y-m-d H:i:s");

    // Begin Transaction
    $conn->begin_transaction();

    try {
        // Insert into Purchases
        $insertPurchaseQuery = "INSERT INTO Purchases (SupplierID, MaterialID, Weight1, Weight2, NetWeight, PricePerKG, ShippingCost, VAT, TotalPrice, InvoiceStatus, PaymentStatus, InvoiceNumber, DocumentInfo, Comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertPurchaseStmt = $conn->prepare($insertPurchaseQuery);
        $insertPurchaseStmt->bind_param("iiidddssssssss", $supplierID, $materialID, $weight1, $weight2, $netWeight, $pricePerKG, $shippingCost, $vatApplicable, $totalPrice, $invoiceStatus, $paymentStatus, $invoiceNumber, $documentInfo, $comments);
        $insertPurchaseStmt->execute();
        $purchaseID = $conn->insert_id;

        // Update Shipments
        $updateShipmentsQuery = "UPDATE Shipments SET ExitTime = ?, PricePerKG = ?, ShippingCost = ?, PurchaseID = ?, VAT = ?, InvoiceStatus = ?, PaymentStatus = ?, DocumentInfo = ?, Comments = ?, Status = 'Delivered', Location = 'Delivered' WHERE LicenseNumber = ?";
        $updateShipmentsStmt = $conn->prepare($updateShipmentsQuery);
        $updateShipmentsStmt->bind_param("sddissssss", $exitTime, $pricePerKG, $shippingCost, $purchaseID, $vatApplicable, $invoiceStatus, $paymentStatus, $documentInfo, $comments, $licenseNumber);
        $updateShipmentsStmt->execute();

        // Update Trucks
        $updateTrucksQuery = "UPDATE Trucks SET Status = 'Free' WHERE LicenseNumber = ?";
        $updateTrucksStmt = $conn->prepare($updateTrucksQuery);
        $updateTrucksStmt->bind_param("s", $licenseNumber);
        $updateTrucksStmt->execute();

        $conn->commit();
        echo "<p style='color:green;'>Purchase order created successfully for $licenseNumber.</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error creating purchase order: " . $e->getMessage() . "</p>";
    }

    $insertPurchaseStmt->close();
    $updateShipmentsStmt->close();
    $updateTrucksStmt->close();
}

// Fetch Incoming Trucks
$incomingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location IN ('Entrance', 'LoadingUnloading', 'LoadedUnloaded')";
$incomingTrucksResult = $conn->query($incomingTrucksQuery);

// Fetch Suppliers for Dropdown
// ...

// Fetch Suppliers for Dropdown
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);
$selectedSupplierID = isset($_POST["supplier_name"]) ? $_POST["supplier_name"] : '';

// Fetch Incoming Trucks for Purchase
$incomingTrucksForPurchaseQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location = 'Office'";
$incomingTrucksForPurchaseResult = $conn->query($incomingTrucksForPurchaseQuery);


// HTML Form for Updating Shipment
echo "<form method='post'>";
echo "<h2>Update Shipment with Supplier and Material Details</h2>";
echo "Truck (License Number): <select name='license_number'>";
while ($row = $incomingTrucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

echo "Supplier Name: <select name='supplier_name' onchange='this.form.submit()'>";
echo "<option value=''>Choose a Supplier</option>";
while ($row = $suppliersResult->fetch_assoc()) {
    $selected = ($row['SupplierID'] == $selectedSupplierID) ? 'selected' : '';
    echo "<option value='" . $row['SupplierID'] . "' $selected>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

// Displaying Materials based on selected Supplier
if ($selectedSupplierID != '') {
    $materialsQuery = "SELECT MaterialID, MaterialType, MaterialName FROM RawMaterials WHERE SupplierID = $selectedSupplierID";
    $materialsResult = $conn->query($materialsQuery);

    if ($materialsResult->num_rows > 0) {
        echo "<label for='material_id'>Select Material:</label>";
        echo "<select name='material_id'>";
        while ($row = $materialsResult->fetch_assoc()) {
            echo "<option value='" . $row['MaterialID'] . "'>" . $row['MaterialType'] . " - " . $row['MaterialName'] . "</option>";
        }
        echo "</select> <br>";
        echo "<input type='hidden' name='supplier_name' value='$selectedSupplierID'>";
        echo "<input type='submit' name='update_shipment' value='Update Shipment'>";
    } else {
        echo "No materials for selected supplier.";
    }
}

echo "</form>";

// ...


echo "</body></html>";

$conn->close();
?>
