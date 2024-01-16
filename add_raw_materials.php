<?php
include 'connect_db.php';

// Fetch Suppliers for Dropdown
$suppliersQuery = "SELECT SupplierID, SupplierName FROM Suppliers";
$suppliersResult = $conn->query($suppliersQuery);

// Add Raw Material
if (isset($_POST['add_raw_material'])) {
    $supplierID = $_POST['supplier_id'];
    $materialType = $_POST['material_type'];
    $materialName = $_POST['material_name'];
    $userName = $conn->real_escape_string($_POST['user_name']);
    $comments = $userName . ' Created Date: ' . date("Y-m-d H:i:s");

    // Prepared statement to insert raw material
    $stmt = $conn->prepare("INSERT INTO RawMaterials (SupplierID, MaterialType, MaterialName, Comments) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $supplierID, $materialType, $materialName, $comments);
    
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

echo "Material Type: <select name='material_type'>
    <option value='OCC'>OCC (قراضه کاغذ و مقوا)</option>
    <option value='Office Waste'>Office Waste (زباله‌های اداری)</option>
    <option value='Chemical'>Chemical (مواد شیمیایی)</option>
    <option value='Parts'>Parts (قطعات)</option>
    <option value='Core'>Core (هسته)</option>
</select> <br>";

echo "Material Name: <input type='text' name='material_name' required> <br>";
echo "User Name: <input type='text' name='user_name' required> <br>";
echo "<input type='submit' name='add_raw_material' value='Add Raw Material'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
