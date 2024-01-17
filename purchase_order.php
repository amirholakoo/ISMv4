<?php
include 'connect_db.php';

// Update Shipment with Supplier Info
if (isset($_POST['update_shipment'])) {
    $licenseNumber = $_POST['license_number_shipment'];
    $supplierName = $_POST['supplier_name'];
    $materialID = $_POST['material_id'];

    // Fetch Material Name from RawMaterials table
    $materialQuery = $conn->prepare("SELECT MaterialName FROM RawMaterials WHERE MaterialID = ?");
    $materialQuery->bind_param("i", $materialID);
    $materialQuery->execute();
    $materialResult = $materialQuery->get_result();
    $materialName = ($materialResult->fetch_assoc())['MaterialName'];

    $updateShipmentQuery = "UPDATE Shipments SET SupplierName = ?, MaterialID = ?, MaterialName = ? WHERE LicenseNumber = ? AND Status = 'Incoming'";
    $updateShipment = $conn->prepare($updateShipmentQuery);
    $updateShipment->bind_param("siis", $supplierName, $materialID, $materialName, $licenseNumber);
    $updateShipment->execute();

    echo "<p style='color:green;'>Shipment updated with supplier info.</p>";
}

// Handle Complete Purchase Details
if (isset($_POST['record_purchase'])) {
    $licenseNumber = $_POST['license_number_purchase'];
    $pricePerKG = $_POST['price_per_kg'];
    $shippingCost = $_POST['shipping_cost'];
    $vatIncluded = isset($_POST['vat_included']) ? 1 : 0;
    $vatRate = 0.09; // 9%
    $invoiceStatus = $_POST['invoice_status'];
    $paymentStatus = $_POST['payment_status'];
    $invoiceNumber = $_POST['invoice_number'];
    $documentInfo = $_POST['document_info'];

    // Fetch weights from Shipments table
    $weightsQuery = $conn->prepare("SELECT Weight1, Weight2 FROM Shipments WHERE LicenseNumber = ? AND Status = 'Incoming' AND Location = 'Office'");
    $weightsQuery->bind_param("s", $licenseNumber);
    $weightsQuery->execute();
    $weightsResult = $weightsQuery->get_result();
    $row = $weightsResult->fetch_assoc();
    $weight1 = abs($row['Weight1']);
    $weight2 = abs($row['Weight2']);
    $netWeight = $weight1 - $weight2; // Assuming weight2 is after loading and weight1 before

    // Calculate Total Price
    $totalPrice = $netWeight * $pricePerKG;
    if ($vatIncluded) {
        $totalPrice += $totalPrice * $vatRate;
    }
    $totalPrice += $shippingCost;

    // Update Purchases table
    $insertPurchaseQuery = "INSERT INTO Purchases (SupplierID, TruckID, LicenseNumber, MaterialID, MaterialName, Weight1, Weight2, NetWeight, ShippingCost, VAT, TotalPrice, InvoiceStatus, PaymentStatus, InvoiceNumber, DocumentInfo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    // [Code to bind parameters and execute the query]

    // Update Shipments table
    $updateShipmentsQuery = "UPDATE Shipments SET Status = 'Delivered', Location = 'Delivered' WHERE LicenseNumber = ?";
    // [Code to bind parameters and execute the query]

    // Update Truck status
    $updateTruckQuery = "UPDATE Trucks SET Status = 'Free' WHERE LicenseNumber = ?";
    // [Code to bind parameters and execute the query]

    echo "<p style='color:green;'>Purchase recorded and shipment updated successfully.</p>";
}

// Fetch Incoming Trucks
$incomingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming'";
$incomingTrucksResult = $conn->query($incomingTrucksQuery);


// Fetch Suppliers
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// HTML Form for Updating Shipment with Supplier Info
echo "<form method='post'>";
echo "<h2>Update Shipment with Supplier Info</h2>";
echo "Truck (License Number): <select name='license_number_shipment'>";
while ($row = $incomingTrucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

echo "Supplier Name: <select name='supplier_name' onchange='loadMaterials(this.value)'>";
echo "<option value=''>Select Supplier</option>";
while ($row = $suppliersResult->fetch_assoc()) {
    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

echo "Material ID: <select name='material_id' id='materialDropdown'>";
echo "<option value=''>Select Material</option>";
// Options will be loaded based on selected supplier via JavaScript
echo "</select> <br>";

echo "<input type='submit' name='update_shipment' value='Update Shipment'>";
echo "</form>";


// HTML Form for Complete Purchase Details
echo "<form method='post'>";
echo "<h2>Complete Purchase Details</h2>";
// [Existing form code to select truck for purchase details]
echo "Price Per KG: <input type='number' step='0.01' name='price_per_kg' required> <br>";
echo "Shipping Cost: <input type='number' step='0.01' name='shipping_cost' required> <br>";
echo "Include VAT (9%): <input type='checkbox' name='vat_included'><br>";
echo "Invoice Status: <select name='invoice_status'><option value='Received'>Received</option><option value='NA'>NA</option></select> <br>";
echo "Payment Status: <select name='payment_status'><

option value='Paid'>Paid</option><option value='UnPaid'>UnPaid</option></select> <br>";
echo "Invoice Number: <input type='text' name='invoice_number' required> <br>";
echo "Documents/Info: <textarea name='document_info'></textarea><br>";
echo "<input type='submit' name='record_purchase' value='Record Purchase'>";
echo "</form>";

// JavaScript to load materials based on selected supplier
echo "<script>
function loadMaterials(supplierID) {
var xhr = new XMLHttpRequest();
xhr.onreadystatechange = function() {
if (xhr.readyState == 4 && xhr.status == 200) {
document.getElementById('materialDropdown').innerHTML = xhr.responseText;
}
};
xhr.open('GET', 'get_materials.php?supplier_id=' + supplierID, true);
xhr.send();
}
</script>";

echo "</body></html>";

$conn->close();
?>
