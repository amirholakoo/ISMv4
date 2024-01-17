<?php
include 'connect_db.php';

// Update Incoming Shipment
if (isset($_POST['update_incoming'])) {
    $licenseNumber = $_POST['license_number_incoming'];
    $quantity = $_POST['quantity'];
    $unloadingLocation = $_POST['unloading_location'];

    $updateIncoming = $conn->prepare("UPDATE Shipments SET Quantity = ?, UnloadLocation = ?, Location = 'LoadedUnloaded' WHERE LicenseNumber = ? AND Status = 'Incoming' AND Location = 'LoadingUnloading'");
    $updateIncoming->bind_param("iss", $quantity, $unloadingLocation, $licenseNumber);
    
    if ($updateIncoming->execute()) {
        echo "<p style='color:green;'>Incoming shipment updated successfully for $licenseNumber.</p>";
    } else {
        echo "<p style='color:red;'>Error updating incoming shipment: " . $updateIncoming->error . "</p>";
    }
    $updateIncoming->close();
}

// Prepare for Outgoing Shipment
if (isset($_POST['prepare_outgoing'])) {
    $licenseNumber = $_POST['license_number_outgoing'];
    $width = $_POST['width'];
    $selectedRolls = $_POST['selected_rolls'];
    $listOfReels = implode(',', $selectedRolls);

    // Update Products table
    $updateProducts = $conn->prepare("UPDATE Products SET Status = 'Sold', Location = ? WHERE ReelNumber IN (" . implode(',', array_fill(0, count($selectedRolls), '?')) . ")");
    $updateProducts->bind_param(str_repeat('s', count($selectedRolls) + 1), $licenseNumber, ...$selectedRolls);
    
    // Update Shipments table
    $updateShipments = $conn->prepare("UPDATE Shipments SET ListOfReels = ?, Location = 'LoadedUnloaded' WHERE LicenseNumber = ? AND Status = 'Outgoing' AND Location = 'LoadingUnloading'");
    $updateShipments->bind_param("ss", $listOfReels, $licenseNumber);

    if ($updateProducts->execute() &&$updateShipments->execute()) {
echo "<p style='color:green;'>Outgoing shipment prepared successfully for $licenseNumber.</p>";
} else {
echo "<p style='color:red;'>Error preparing outgoing shipment: " . $updateProducts->error . " " . $updateShipments->error . "</p>";
}
$updateProducts->close();
$updateShipments->close();
}

// Fetch Incoming Trucks
$incomingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Incoming' AND Location = 'LoadingUnloading'";
$incomingTrucksResult = $conn->query($incomingTrucksQuery);

// Fetch Outgoing Trucks
$outgoingTrucksQuery = "SELECT LicenseNumber FROM Shipments WHERE Status = 'Outgoing' AND Location = 'LoadingUnloading'";
$outgoingTrucksResult = $conn->query($outgoingTrucksQuery);

// Fetch Widths for Outgoing Rolls
$widthsQuery = "SELECT DISTINCT Width FROM Products WHERE Status = 'In-Stock'";
$widthsResult = $conn->query($widthsQuery);

// HTML Form for Incoming Shipment Update
echo "<form method='post'>";
echo "<h2>Update Incoming Shipment</h2>";
echo "Truck (License Number): <select name='license_number_incoming'>";
while ($row = $incomingTrucksResult->fetch_assoc()) {
echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";
echo "Quantity: <input type='number' name='quantity' required> <br>";
echo "Unloading Location: <input type='text' name='unloading_location' required> <br>";
echo "<input type='submit' name='update_incoming' value='Update Incoming Shipment'>";
echo "</form>";

// HTML Form for Outgoing Shipment Preparation
echo "<form method='post'>";
echo "<h2>Prepare Outgoing Shipment</h2>";
echo "Truck (License Number): <select name='license_number_outgoing'>";
while ($row = $outgoingTrucksResult->fetch_assoc()) {
echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";
echo "Width: <select name='width'>";
while ($row = $widthsResult->fetch_assoc()) {
echo "<option value='" . $row['Width'] . "'>" . $row['Width'] . "</option>";
}
echo "</select> <br>";

// Fetch and Display Rolls based on selected Width
// Note: This part requires JavaScript to dynamically fetch and display rolls based on the selected width
// For simplicity, it's not implemented here

echo "Select Rolls (hold Ctrl to select multiple):

<select name='selected_rolls[]' multiple size='10'>";
// Fetch rolls based on selected width
if (isset($_POST['width'])) {
$selectedWidth = $_POST['width'];
$rollsQuery = "SELECT ReelNumber FROM Products WHERE Width = $selectedWidth AND Status = 'In-Stock'";
$rollsResult = $conn->query($rollsQuery);
  while ($row = $rollsResult->fetch_assoc()) {
    echo "<option value='" . $row['ReelNumber'] . "'>" . $row['ReelNumber'] . "</option>";
}
}
echo "</select> <br>";
echo "<input type='submit' name='prepare_outgoing' value='Prepare Outgoing Shipment'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
