<?php
include 'connect_db.php';

// Display All Incoming Shipments
echo "<h2>Incoming Shipments Overview</h2>";
$incomingShipmentsQuery = "SELECT * FROM Shipments WHERE Status = 'Incoming'";
$incomingShipmentsResult = $conn->query($incomingShipmentsQuery);

if ($incomingShipmentsResult->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ShipmentID</th><th>LicenseNumber</th><th>SupplierName</th><th>MaterialID</th><th>MaterialType</th><th>MaterialName</th><th>Weight1</th><th>Weight2</th><th>EntryTime</th><th>ExitTime</th><th>Location</th></tr>";
    while ($row = $incomingShipmentsResult->fetch_assoc()) {
        echo "<tr><td>".$row["ShipmentID"]."</td><td>".$row["LicenseNumber"]."</td><td>".$row["SupplierName"]."</td><td>".$row["MaterialID"]."</td><td>".$row["MaterialType"]."</td><td>".$row["MaterialName"]."</td><td>".$row["Weight1"]."</td><td>".$row["Weight2"]."</td><td>".$row["EntryTime"]."</td><td>".$row["ExitTime"]."</td><td>".$row["Location"]."</td></tr>";
    }
    echo "</table>";
} else {
    echo "No incoming shipments found.";
}

// [Bottom Section will go here]
// Handle the creation of the purchase order
if (isset($_POST['create_purchase_order'])) {
    // Extract and sanitize input data
    $licenseNumber = $_POST['license_number'];
    $supplierID = $_POST['supplier_id'];
    $materialID = $_POST['material_id'];
    $weight1 = $_POST['weight1'];
    $weight2 = $_POST['weight2'];
    $netWeight = abs($weight1 - $weight2);
    $pricePerKg = $_POST['price_per_kg'];
    $shippingCost = $_POST['shipping_cost'];
    $vat = isset($_POST['vat']) ? 'YES' : 'NO';
    $totalPrice = ($netWeight * $pricePerKg) + $shippingCost;
    if ($vat === 'YES') {
        $totalPrice *= 1.09; // Adding 9% VAT
    }
    $invoiceStatus = $_POST['invoice_status'];
    $paymentStatus = $_POST['payment_status'];
    $invoiceNumber = $_POST['invoice_number'];
    $documentInfo = $_POST['document_info'];
    $comments = $_POST['comments'];
    $exitTime = date("Y-m-d H:i:s");

    // Begin Transaction
    $conn->begin_transaction();

    try {
        // Insert into Purchases
        $insertPurchase = "INSERT INTO Purchases (SupplierID, TruckID, LicenseNumber, MaterialID, Weight1, Weight2, NetWeight, PricePerKG, ShippingCost, VAT, TotalPrice, InvoiceStatus, PaymentStatus, InvoiceNumber, DocumentInfo, Comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertPurchase);
        $stmt->bind_param("iisiddsdssssss", $supplierID, $licenseNumber, $materialID, $weight1, $weight2, $netWeight, $pricePerKg, $shippingCost, $vat, $totalPrice, $invoiceStatus, $paymentStatus, $invoiceNumber, $documentInfo, $comments);
        $stmt->execute();
        $purchaseID = $conn->insert_id;

        // Update Shipments
        $updateShipment = "UPDATE Shipments SET ExitTime = ?, PricePerKG = ?, ShippingCost = ?, PurchaseID = ?, VAT = ?, InvoiceStatus = ?, PaymentStatus = ?, DocumentInfo = ?, Comments = ?, Status = 'Delivered', Location = 'Delivered' WHERE LicenseNumber = ?";
        $stmt = $conn->prepare($updateShipment);
        $stmt->bind_param("sddsssss", $exitTime, $pricePerKg, $shippingCost, $purchaseID, $vat, $invoiceStatus, $paymentStatus, $documentInfo, $comments, $licenseNumber);
        $stmt->execute();

        // Update Trucks
        $updateTruck = "UPDATE Trucks SET Status = 'Free' WHERE LicenseNumber = ?";
        $stmt = $conn->prepare($updateTruck);
        $stmt->bind_param("s", $licenseNumber);
        $stmt->execute();

        $conn->commit();
        echo "<p style='color:green;'>Purchase order created and shipment updated successfully!</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error creating purchase order: " . $e->getMessage() . "</p>";
    }
}

// Fetch Trucks for Dropdown
$trucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location = 'Office'";
$trucksResult = $conn->query($trucksQuery);

// Fetch Suppliers for Dropdown
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// HTML Form for Creating Purchase Order
echo "<form method='post'>";
echo "<h2>Create Purchase Order</h2>";

// Truck Selection
echo "Truck (License Number): <select name='license_number'>";
while ($row = $trucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

// Supplier Selection
echo "Supplier Name: <select name='supplier_id'>";
while ($row = $suppliersResult->fetch_assoc()) {
    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

// Material Selection
// [Add material selection dropdowns here, similar to the supplier selection]

// Additional Fields
echo "Weight1: <input type='number' name='weight1' required> <br>";
echo "Weight2: <input type='number' name='weight2' required> <br>";
echo "Price Per KG: <input type='number' step='0.01' name='price_per_kg' required> <br>";
echo "Shipping Cost: <input type='number' step='0.01' name='shipping_cost' required> <br>";
echo "VAT: <input type='checkbox' name='vat'> <br>";
echo "Invoice Status: <select name='invoice_status'><option value='NA'>NA</option><option value='Received'>Received</option></select> <br>";
echo "Payment Status: <select name='payment_status'><option value='Terms'>Terms</option><option value='Paid'>Paid</option></select> <br>";
echo "Invoice Number: <input type='text' name='invoice_number'> <br>";
echo "Document Info: <textarea name='document_info'></textarea> <br>";
echo "Comments: <textarea name='comments'></textarea> <br>";

echo "<input type='submit' name='create_purchase_order' value='Create Purchase Order'>";
echo "</form>";

echo "</body></html>";
?>
