<?php
include 'connect_db.php';

// Add Supplier
if (isset($_POST['add_new_supplier'])) {
    $newSupplierName = $conn->real_escape_string($_POST['new_supplier_name']);
    $newAddress = $conn->real_escape_string($_POST['new_supplier_address']);
    $newPhone = $conn->real_escape_string($_POST['new_supplier_phone']);

    $addSupplierStmt = $conn->prepare("INSERT INTO Suppliers (SupplierName, Address, Phone) VALUES (?, ?, ?)");
    $addSupplierStmt->bind_param("sss", $newSupplierName, $newAddress, $newPhone);
    
    if ($addSupplierStmt->execute()) {
        echo "<p style='color:green;'>New supplier added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error adding supplier: " . $addSupplierStmt->error . "</p>";
    }
    $addSupplierStmt->close();
}

// Add Raw Material
if (isset($_POST['add_raw_material'])) {
    $materialType = $_POST['material_type'];
    $materialName = $_POST['material_name'];
    $supplierName = $_POST['supplier_name'];
    $userName = $conn->real_escape_string($_POST['user_name']);
    $comments = $userName . ' Created Date: ' . date("Y-m-d H:i:s");

    $addMaterialStmt = $conn->prepare("INSERT INTO RawMaterials (SupplierName, MaterialType, MaterialName, Comments) VALUES (?, ?, ?, ?)");
    $addMaterialStmt->bind_param("ssss", $supplierName, $materialType, $materialName, $comments);
    
    if ($addMaterialStmt->execute()) {
        echo "<p style='color:green;'>New raw material added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error adding raw material: " . $addMaterialStmt->error . "</p>";
    }
    $addMaterialStmt->close();
}

// HTML Form for Adding New Supplier
echo "<form method='post'>";
echo "<h2>Add New Supplier</h2>";
echo"Name: <input type='text' name='new_supplier_name' required> ";
echo "Address: <textarea name='new_supplier_address' required></textarea> ";
echo "Phone: <input type='text' name='new_supplier_phone' required> ";
echo "<input type='submit' name='add_new_supplier' value='Add Supplier'>";
echo "</form>";

// HTML Form for Adding Raw Material
echo "<form method='post'>";
echo "<h2>Add New Raw Material</h2>";
echo "Supplier Name: <input type='text' name='supplier_name' required> <br>";
echo "Material Type: <input type='text' name='material_type' required> <br>";
echo "Material Name: <input type='text' name='material_name' required> <br>";
echo "User Name: <input type='text' name='user_name' required> <br>";
echo "<input type='submit' name='add_raw_material' value='Add Raw Material'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
