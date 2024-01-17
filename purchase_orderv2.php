<?php
include 'connect_db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Update Shipments with Supplier and Material Information
if (isset($_POST['update_shipment'])) {
    $licenseNumber = $_POST['license_number'];
    $supplierID = $_POST['supplier_id'];
    $materialID = $_POST['material_id'];

    // Fetch Supplier Name and Material Name
    $supplierQuery = "SELECT SupplierName FROM Suppliers WHERE SupplierID = $supplierID";
    $supplierResult = $conn->query($supplierQuery);
    $supplierName = $supplierResult->fetch_assoc()['SupplierName'];

    $materialQuery = "SELECT MaterialName FROM RawMaterials WHERE MaterialID = $materialID";
    $materialResult = $conn->query($materialQuery);
    $materialName = $materialResult->fetch_assoc()['MaterialName'];

    // Update Shipments Table
    $updateShipment = $conn->prepare("UPDATE Shipments SET SupplierName = ?, MaterialID = ?, MaterialName = ? WHERE LicenseNumber = ? AND Status = 'Incoming'");
    $updateShipment->bind_param("siss", $supplierName, $materialID, $materialName, $licenseNumber);
    
    if ($updateShipment->execute()) {
        echo "<p style='color:green;'>Shipment updated successfully for $licenseNumber.</p>";
    } else {
        echo "<p style='color:red;'>Error updating shipment: " . $updateShipment->error . "</p>";
    }
    $updateShipment->close();
}

// Fetch Incoming Trucks
$incomingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming'";
$incomingTrucksResult = $conn->query($incomingTrucksQuery);

// Fetch Suppliers
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// HTML Form for Updating Shipments
echo "<form method='post'>";
echo "<h2>Update Incoming Shipment</h2>";
echo "Truck (License Number): <select name='license_number'>";
while ($row = $incomingTrucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

echo "Supplier: <select name='supplier_id' onchange='this.form.submit()'>";
echo "<option value=''>Choose a Supplier</option>";
while ($row = $suppliersResult->fetch_assoc()) {
    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

// Displaying Materials based on selected Supplier
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["supplier_id"])) {
    $selectedSupplierID = $_POST["supplier_id"];
    $materialsQuery = "SELECT MaterialID, MaterialName FROM RawMaterials WHERE SupplierID = $selectedSupplierID";
    $materialsResult = $conn->query($materialsQuery);

    if ($materialsResult->num_rows > 0) {
        echo "<label for='material_id'>Select Material:</label>";
        echo "<select name='material_id'>";
        while ($row = $materialsResult->fetch_assoc()) {
            echo "<option value='" . $row['MaterialID'] . "'>" . $row['MaterialName'] . "</option>";
        }
        echo "</select> <br>";
    } else {
        echo "No materials found for selected supplier.";
    }
}

echo "<input type='submit' name='update_shipment' value='Update Shipment'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
