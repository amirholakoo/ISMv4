<?php
include 'connect_db.php';

// Update Shipment with Supplier and Material Info
if (isset($_POST['update_shipment'])) {
    $licenseNumber = $_POST['license_number'];
    $supplierID = $_POST['supplier_id'];
    $materialID = $_POST['material_id'];

    // Fetch Supplier Name and Material Name
    $supplierQuery = "SELECT SupplierName FROM Suppliers WHERE SupplierID = ?";
    $materialQuery = "SELECT MaterialName FROM RawMaterials WHERE MaterialID = ?";

    $stmtSupplier = $conn->prepare($supplierQuery);
    $stmtSupplier->bind_param("i", $supplierID);
    $stmtSupplier->execute();
    $resultSupplier = $stmtSupplier->get_result();
    $supplierName = ($resultSupplier->fetch_assoc())['SupplierName'];

    $stmtMaterial = $conn->prepare($materialQuery);
    $stmtMaterial->bind_param("i", $materialID);
    $stmtMaterial->execute();
    $resultMaterial = $stmtMaterial->get_result();
    $materialName = ($resultMaterial->fetch_assoc())['MaterialName'];

    // Update Shipment
    $updateShipmentQuery = "UPDATE Shipments SET SupplierName = ?, MaterialID = ?, MaterialName = ? WHERE LicenseNumber = ? AND Status = 'Incoming'";
    $updateShipment = $conn->prepare($updateShipmentQuery);
    $updateShipment->bind_param("siis", $supplierName, $materialID, $materialName, $licenseNumber);

    if ($updateShipment->execute()) {
        echo "<p style='color:green;'>Shipment updated successfully for $licenseNumber.</p>";
    } else {
        echo "<p style='color:red;'>Error updating shipment: " . $updateShipment->error . "</p>";
    }

    $stmtSupplier->close();
    $stmtMaterial->close();
    $updateShipment->close();
}

// Fetch Incoming Trucks
$trucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming'";
$trucksResult = $conn->query($trucksQuery);

// Fetch Suppliers
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// HTML Form for Updating Shipment
echo "<form method='post'>";
echo "<h2>Update Shipment</h2>";
echo "Truck (License Number): <select name='license_number'>";
while ($row = $trucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

echo "Supplier: <select name='supplier_id' onchange='loadMaterials(this.value)'>";
echo "<option value=''>Select Supplier</option>";
while ($row = $suppliersResult->fetch_assoc()) {
    echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

echo "Material: <select name='material_id' id='material_select'>";
// The material options will be populated based on the selected supplier using JavaScript
echo "</select> <br>";

echo "<input type='submit' name='update_shipment' value='Update Shipment'>";
echo "</form>";

// JavaScript for dynamically loading materials based on the selected supplier
echo "<script>
function loadMaterials(supplierID) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_materials.php?supplier_id=' + supplierID, true);
    xhr.onload = function() {
        if (this.status == 200) {
            document.getElementById('material_select').innerHTML = this.responseText;
        }
    }
    xhr.send();
}
</script>";

echo "</body></html>";

$conn->close();
?>
