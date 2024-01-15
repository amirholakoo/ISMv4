<?php
include 'connect_db.php';

// Fetch data for reports
$query = "SELECT * FROM Shipments";
$result = $conn->query($query);

// Dashboard HTML with styling
echo "<!DOCTYPE html><html><head><title>📊 ISMv4 Dashboard</title>";
echo "<style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; }
        h1 { color: #4CAF50; }
        nav a { color: #5D5C61; margin-right: 20px; text-decoration: none; font-size: 1.2em; }
        nav a:hover { color: #4CAF50; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
      </style>";
echo "</head><body>";

echo "<h1>🏭 ISMv4 Dashboard</h1>";

// Navigation Links
echo "<nav>";
echo "<a href='trucks.php'>🚚 Trucks</a> | ";
echo "<a href='customers.php'>👥 Customers</a> | ";
echo "<a href='suppliers.php'>🏢 Suppliers</a> | ";
echo "<a href='shipments.php'>📦 Shipments</a> | ";
echo "<a href='sales.php'>💰 Sales</a> | ";
echo "<a href='purchases.php'>🛒 Purchases</a>";
echo "</nav>";

// Shipments Report
echo "<h2>🚛 Shipments Report</h2>";
if ($result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Status</th><th>Location</th><th>📦 Quantity</th></tr>";
    // Output data of each row
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
