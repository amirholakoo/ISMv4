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

// Fetch Incoming Trucks
$incomingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location IN ('Entrance', 'LoadingUnloading', 'LoadedUnloaded')";
$incomingTrucksResult = $conn->query($incomingTrucksQuery);

// Fetch Suppliers for Dropdown
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

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

echo "</body></html>";

$conn->close();
?>
