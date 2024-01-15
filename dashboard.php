<?php
include 'connect_db.php';

// Fetch data for reports
$query = "SELECT * FROM Shipments";
$result = $conn->query($query);

// Dashboard HTML
echo "<!DOCTYPE html><html><head><title>ISMv4 Dashboard</title>";
echo "<style> /* Add your CSS styles here */ </style>";
echo "</head><body>";

echo "<h1>ISMv4 Dashboard</h1>";

// Links
echo "<nav>";
echo "<a href='trucks.php'>Trucks</a> | ";
echo "<a href='customers.php'>Customers</a> | ";
echo "<a href='suppliers.php'>Suppliers</a> | ";
echo "<a href='shipments.php'>Shipments</a> | ";
echo "<a href='sales.php'>Sales</a> | ";
echo "<a href='purchases.php'>Purchases</a>";
echo "</nav>";

// Shipments Report
echo "<h2>Shipments Report</h2>";
if ($result->num_rows > 0) {
    echo "<table><tr><th>ShipmentID</th><th>Status</th><th>Location</th><th>Quantity</th></tr>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>".$row["ShipmentID"]."</td><td>".$row["Status"]."</td><td>".$row["Location"]."</td><td>".$row["Quantity"]."</td></tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}
echo "</body></html>";

$conn->close();
?>
