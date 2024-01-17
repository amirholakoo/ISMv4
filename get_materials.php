<?php
include 'connect_db.php';

if (isset($_GET['supplier_id'])) {
    $supplierID = $_GET['supplier_id'];

    $query = "SELECT MaterialID, MaterialName FROM RawMaterials WHERE SupplierID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $supplierID);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<option value=''>Select Material</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['MaterialID'] . "'>" . $row['MaterialName'] . "</option>";
    }
}
?>
