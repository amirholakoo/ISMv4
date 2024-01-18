<?php
include 'connect_db.php';

// Display All Incoming Shipments
echo "<h2>Incoming Shipments Overview</h2>";
$incomingShipmentsQuery = "SELECT * FROM Shipments WHERE Status = 'Incoming'";
$incomingShipmentsResult = $conn->query($incomingShipmentsQuery);

if ($incomingShipmentsResult->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ShipmentID</th><th>LicenseNumber</th><th>SupplierName</th><th>MaterialID</th><th>MaterialType</th><th>MaterialName</th><th>Weight1</th><th>Weight2</th><th>EntryTime</th><th>ExitTime</th><th>Location</th></tr>";
    while ($row = $incomingShipmentsResult->fetch_assoc()) {
        echo "<tr><td>".$row["ShipmentID"]."</td><td>".$row["LicenseNumber"]."</td><td>".$row["SupplierName"]."</td><td>".$row["MaterialID"]."</td><td>".$row["MaterialType"]."</td><td>".$row["MaterialName"]."</td><td>".$row["Weight1"]."</td><td>".$row["Weight2"]."</td><td>".$row["EntryTime"]."</td><td>".$row["ExitTime"]."</td><td>".$row["Location"]."</td></tr>";
    }
    echo "</table>";
} else {
    echo "No incoming shipments found.";
}

// [Bottom Section will go here]

echo "</body></html>";
?>
