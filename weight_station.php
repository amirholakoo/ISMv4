<?php
include 'connect_db.php';

// Update Weight1
if (isset($_POST['update_weight1'])) {
    $licenseNumber = $_POST['license_number_weight1'];
    $weight1 = $_POST['weight1'];
    $weight1Time = date("Y-m-d H:i:s");
    $location = 'LoadingUnloading';

    $updateQuery = "UPDATE Shipments SET Weight1 = ?, Weight1Time = ?, Location = ? WHERE LicenseNumber = ? AND Location = 'Entrance'";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("dsss", $weight1, $weight1Time, $location, $licenseNumber);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Weight1 updated successfully for $licenseNumber.</p>";
    } else {
        echo "<p style='color:red;'>Error updating weight: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Update Weight2
if (isset($_POST['update_weight2'])) {
    $licenseNumber = $_POST['license_number_weight2'];
    $weight2 = $_POST['weight2'];
    $weight2Time = date("Y-m-d H:i:s");
    $location = 'Office';

    $updateQuery = "UPDATE Shipments SET Weight2 = ?, Weight2Time = ?, Location = ? WHERE LicenseNumber = ? AND (Location = 'LoadingUnloading' OR Location = 'LoadedUnloaded')";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("dsss", $weight2, $weight2Time, $location, $licenseNumber);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Weight2 updated successfully for $licenseNumber.</p>";
    } else {
        echo "<p style='color:red;'>Error updating weight: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Fetch Trucks at Entrance
$entranceTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Location = 'Entrance'";
$entranceTrucksResult = $conn->query($entranceTrucksQuery);

// Fetch Trucks at Loading/Unloading or Loaded/Unloaded
$loadingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Location IN ('LoadingUnloading', 'LoadedUnloaded')";
$loadingTrucksResult = $conn->query($loadingTrucksQuery);

// HTML Form for Weight1 Update
echo "<form method='post'>";
echo "<h2>Update Weight1</h2>";
echo "Truck (License Number): <select name='license_number_weight1'>";
while ($row = $entranceTrucksResult->fetch_assoc()) {
echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";
echo "Weight1: <input type='number' name='weight1' required> <br>";
echo "<input type='submit' name='update_weight1' value='Update Weight1'>";
echo "</form>";

// HTML Form for Weight2 Update
echo "<form method='post'>";
echo "<h2>Update Weight2</h2>";
echo "Truck (License Number): <select name='license_number_weight2'>";
while ($row = $loadingTrucksResult->fetch_assoc()) {
echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";
echo "Weight2: <input type='number' name='weight2' required> <br>";
echo "<input type='submit' name='update_weight2' value='Update Weight2'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
