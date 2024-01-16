<?php
include 'connect_db.php';

// Fetch Free Trucks for Dropdown
$trucksQuery = "SELECT LicenseNumber FROM Trucks WHERE Status = 'Free'";
$trucksResult = $conn->query($trucksQuery);

// Create Shipment
if (isset($_POST['create_shipment'])) {
    $licenseNumber = $_POST['license_number'];
    $shipmentType = $_POST['shipment_type'];
    $entryTime = date("Y-m-d H:i:s");
    $location = 'Entrance';

    // Transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Insert into Shipments
        $insertShipment = $conn->prepare("INSERT INTO Shipments (Status, Location, LicenseNumber, EntryTime) VALUES (?, ?, ?, ?)");
        $insertShipment->bind_param("ssss", $shipmentType, $location, $licenseNumber, $entryTime);
        $insertShipment->execute();
        $insertShipment->close();

        // Update Truck Status
        $updateTruck = $conn->prepare("UPDATE Trucks SET Status = 'Busy' WHERE LicenseNumber = ?");
        $updateTruck->bind_param("s", $licenseNumber);
        $updateTruck->execute();
        $updateTruck->close();

        $conn->commit();
        echo "<p style='color:green;'>Shipment created and truck status updated successfully!</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error creating shipment: " . $e->getMessage() . "</p>";
    }
}

// HTML Form for Creating Shipment
echo "<form method='post'>";
echo "<h2>Create Shipment</h2>";
echo "Truck (License Number): <select name='license_number'>";
while ($row = $trucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

echo "Shipment Type: <select name='shipment_type'>
    <option value='Incoming'>Incoming</option>
    <option value='Outgoing'>Outgoing</option>
</select> <br>";

echo "<input type='submit' name='create_shipment' value='Create Shipment'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
