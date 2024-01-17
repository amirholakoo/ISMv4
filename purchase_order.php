<?php
include 'connect_db.php';

// Update Shipment with Supplier Info
if (isset($_POST['update_shipment'])) {
    // [Existing code to update shipment with supplier info]
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
// [Existing code to fetch incoming trucks forboth sections]

// Fetch Suppliers
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// HTML Form for Updating Shipment with Supplier Info
echo "<form method='post'>";
echo "<h2>Update Shipment with Supplier Info</h2>";
// [Existing form code for updating shipment]
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
