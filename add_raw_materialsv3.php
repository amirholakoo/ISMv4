<?php
include 'connect_db.php';

// Fetch Suppliers for Dropdown
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// Add Raw Material
if (isset($_POST['add_raw_material'])) {
    $supplierID = $_POST['supplier_id'];
    $supplierName = "";
    $materialType = $_POST['material_type'];
    $materialName = $_POST['material_name'];
    $description = $conn->real_escape_string($_POST['description']);
    $userName = $conn->real_escape_string($_POST['user_name']);
    $comments = $userName . ' Created Date: ' . date("Y-m-d H:i:s");

    // Fetch Supplier Name
    $supplierNameQuery = "SELECT SupplierName FROM Suppliers WHERE SupplierID = $supplierID";
    $nameResult = $conn->query($supplierNameQuery);
    if ($nameRow = $nameResult->fetch_assoc()) {
        $supplierName = $nameRow['SupplierName'];
    }

    // Prepared statement to insert raw material
    $stmt = $conn->prepare("INSERT INTO RawMaterials (SupplierID, SupplierName, MaterialType, MaterialName, Description, Comments) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $supplierID, $supplierName, $materialType, $materialName, $description, $comments);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>New raw material added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error adding raw material: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// HTML Form for Adding Raw Material
echo "<form method='post'>";
echo "<h2>Add New Raw Material</h2>";
echo "Supplier: <select name='supplier_id'>";
while ($row = $suppliersResult->fetch_assoc()) {
echo "<option value='" . $row['SupplierID'] . "'>" . $row['SupplierName'] . "</option>";
}
echo "</select> <br>";

echo "Material Type: <input type='text' name='material_type' required> <br>";
echo "Material Name: <input type='text' name='material_name' required> <br>";
echo "Description: <textarea name='description' required></textarea> <br>";
echo "User Name: <input type='text' name='user_name' required> <br>";
echo "<input type='submit' name='add_raw_material' value='Add Raw Material'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
