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
// ...

// Handle Purchase Order Creation
if (isset($_POST['create_purchase'])) {
    // Retrieve form data
    $licenseNumber = $_POST['license_number_po'];
    $pricePerKg = $_POST['price_per_kg'];
    $shippingCost = $_POST['shipping_cost'];
    $vat = isset($_POST['vat']) ? 'YES' : 'NO';
    $invoiceStatus = $_POST['invoice_status'];
    $paymentStatus = $_POST['payment_status'];
    $invoiceNumber = $_POST['invoice_number'];
    $documentInfo = $_POST['document_info'];
    $comments = $_POST['comments'];

    // Calculate total price
    $netWeight = abs($_POST['weight1'] - $_POST['weight2']);
    $totalPrice = ($pricePerKg * $netWeight) + $shippingCost;
    if ($vat === 'YES') {
        $totalPrice *= 1.09; // Adding 9% VAT
    }

    // Begin Transaction
    $conn->begin_transaction();
    try {
        // Insert into Purchases table
        $insertPurchaseQuery = "INSERT INTO Purchases (SupplierID, TruckID, LicenseNumber, MaterialID, MaterialType, MaterialName, Weight1, Weight2, NetWeight, PricePerKG, ShippingCost, VAT, TotalPrice, InvoiceStatus, PaymentStatus, InvoiceNumber, DocumentInfo, Comments) SELECT SupplierID, TruckID, LicenseNumber, MaterialID, MaterialType, MaterialName, Weight1, Weight2, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? FROM Shipments WHERE LicenseNumber = ?";
        $insertPurchaseStmt = $conn->prepare($insertPurchaseQuery);
        $insertPurchaseStmt->bind_param("ddddsssssss", $netWeight, $pricePerKg, $shippingCost, $vat, $totalPrice, $invoiceStatus, $paymentStatus, $invoiceNumber, $documentInfo, $comments, $licenseNumber);
        $insertPurchaseStmt->execute();

        // Update Shipments table
        $updateShipmentQuery = "UPDATE Shipments SET ExitTime = NOW(), PricePerKG = ?, ShippingCost = ?, PurchaseID = LAST_INSERT_ID(), VAT = ?, InvoiceStatus = ?, PaymentStatus = ?, DocumentInfo = ?, Comments = ?, Status = 'Delivered', Location = 'Delivered' WHERE LicenseNumber = ?";
        $updateShipmentStmt = $conn->prepare($updateShipmentQuery);
        $updateShipmentStmt->bind_param("ddsssss", $pricePerKg, $shippingCost, $vat, $invoiceStatus, $paymentStatus, $documentInfo, $comments, $licenseNumber);
        $updateShipmentStmt->execute();

        // Update Truck status
        $updateTruckQuery = "UPDATE Trucks SET Status = 'Free' WHERE LicenseNumber IN (SELECT LicenseNumber FROM Shipments WHERE LicenseNumber = ?)";
        $updateTruckStmt = $conn->prepare($updateTruckQuery);
        $updateTruckStmt->bind_param("s", $licenseNumber);
        $updateTruckStmt->execute();

        $conn->commit();
        echo "<p style='color:green;'>Purchase order created successfully.</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error creating purchase order: " . $e->getMessage() . "</p>";
    }
}


// Fetch Trucks for Purchase Order
$trucksQueryPO = "SELECT s.LicenseNumber, s.SupplierName, s.Weight1, s.Weight2 FROM Shipments s JOIN Trucks t ON s.TruckID = t.TruckID WHERE s.Status = 'Incoming' AND s.Location = 'Office' AND t.Status = 'Busy'";
$trucksResultPO = $conn->query($trucksQueryPO);
$selectedLicenseNumber = isset($_POST['license_number_po']) ? $_POST['license_number_po'] : '';

echo "<h2>Create Purchase Order</h2>";
echo "<form method='post'>";
echo "Truck (License Number): <select name='license_number_po' onchange='this.form.submit()'>";
echo "<option value=''>Select a Truck</option>";
while ($row = $trucksResultPO->fetch_assoc()) {
    $selected = ($row['LicenseNumber'] == $selectedLicenseNumber) ? 'selected' : '';
    echo "<option value='".$row['LicenseNumber']."' $selected>".$row['LicenseNumber']." - ".$row['SupplierName']."</option>";
}
echo "</select> <br>";

if ($selectedLicenseNumber != '') {
    // Re-fetch the selected truck's data
    $selectedTruckQuery = "SELECT LicenseNumber, SupplierName, Weight1, Weight2 FROM Shipments WHERE LicenseNumber = '$selectedLicenseNumber'";
    $selectedTruckResult = $conn->query($selectedTruckQuery);
    $selectedTruckInfo = $selectedTruckResult->fetch_assoc();

    // Display additional fields for selected truck
    echo "Net Weight: <input type='text' name='net_weight' value='".abs($selectedTruckInfo['Weight1'] - $selectedTruckInfo['Weight2'])."' readonly><br>";

    // [Additional fields for price per kg, shipping cost, VAT, invoice status, payment status, invoice number, document info, comments]
    // ...

    echo "<input type='submit' name='create_purchase' value='Create Purchase'>";
}
echo "</form>";


echo "</body></html>";
?>
