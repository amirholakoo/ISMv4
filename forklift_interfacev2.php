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
if (isset($_POST['recordSale'])) {
    $selectedRolls = $_POST['selectedRolls'];
    $licenseNumber = $_POST['license_number_outgoing']; // Assuming you're passing this from the form
    $listOfReels = implode(',', $selectedRolls);

    // Begin Transaction
    $conn->begin_transaction();

    try {
        // Update Products table
        $updateProductsQuery = "UPDATE Products SET Status = 'Sold', Location = ? WHERE ReelNumber IN (" . implode(',', array_fill(0, count($selectedRolls), '?')) . ")";
        $updateProducts = $conn->prepare($updateProductsQuery);
        $updateProducts->bind_param(str_repeat('s', count($selectedRolls) + 1), $licenseNumber, ...$selectedRolls);
        $updateProducts->execute();

        // Update Shipments table
        $updateShipmentsQuery = "UPDATE Shipments SET ListOfReels = ?, Location = 'LoadedUnloaded' WHERE LicenseNumber = ? AND Status = 'Outgoing' AND Location = 'LoadingUnloading'";
        $updateShipments = $conn->prepare($updateShipmentsQuery);
        $updateShipments->bind_param("ss", $listOfReels, $licenseNumber);
        $updateShipments->execute();

        $conn->commit();
        echo "<p style='color:green;'>Outgoing shipment prepared successfully for $licenseNumber.</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p style='color:red;'>Error preparing outgoing shipment: " . $e->getMessage() . "</p>";
    }
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
echo "<form action='forklift_interface.php' method='post'>";
echo "<h2>Prepare Outgoing Shipment</h2>";
echo "Truck (License Number): <select name='license_number_outgoing'>";
while ($row = $outgoingTrucksResult->fetch_assoc()) {
    echo "<option value='" . $row['LicenseNumber'] . "'>" . $row['LicenseNumber'] . "</option>";
}
echo "</select> <br>";

// Dropdown for selecting roll width
echo "<label for='width'>Select Roll Width:</label>";
echo "<select name='width' onchange='this.form.submit()'>";
echo "<option value=''>Choose a Width</option>";
$widthSql = "SELECT DISTINCT Width FROM Products WHERE Status = 'In-Stock' ORDER BY Width";
$widthResult = $conn->query($widthSql);
while ($row = $widthResult->fetch_assoc()) {
echo "<option value='".$row["Width"]."'>".$row["Width"]." cm</option>";
}
echo "</select>";
echo "</form>";

// Displaying in-stock rolls based on selected width
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["width"])) {
$selectedWidth = $_POST["width"];
$rollsSql = "SELECT ReelNumber FROM Products WHERE Width = $selectedWidth AND Status = 'In-Stock'";
$rollsResult = $conn->query($rollsSql);
if ($rollsResult->num_rows > 0) {
    echo "<h2>In-Stock Rolls (Width: $selectedWidth cm):</h2>";
    echo "<form action='forklift_interface.php' method='post'>";
    echo "<input type='hidden' name='width' value='$selectedWidth'>";
    echo "<input type='hidden' name='license_number_outgoing' value='" . ($_POST['license_number_outgoing'] ?? '') . "'>";
    echo "<table><tr><th>Select</th><th>Reel Number</th></tr>";

    while ($row = $rollsResult->fetch_assoc()) {
        echo "<tr><td><input type='checkbox' name='selectedRolls[]' value='".$row["ReelNumber"]."'></td><td>".$row["ReelNumber"]."</td></tr>";
    }
    echo "</table>";
    echo "<input type='submit' name='recordSale' value='Record Sale'>";
    echo "</form>";
} else {
    echo "No in-stock rolls for selected width.";
}
}

echo "</body></html>";

$conn->close();
?>
