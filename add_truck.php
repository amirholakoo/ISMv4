<?php
include 'connect_db.php';

// Farsi Letters Array
$farsiLetters = ['الف', 'ب', 'پ', 'ت', 'ث', 'ج', 'چ', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'ژ', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 'ن', 'و', 'ه', 'ی'];

// Check License Number
$licenseExists = false;
if (isset($_POST['check_license'])) {
    $licenseNumber = $_POST['digit1'] . $_POST['farsi_letter'] . $_POST['digit2'] . 'IR' . $_POST['digit3'];
    $checkQuery = "SELECT * FROM Trucks WHERE LicenseNumber = '$licenseNumber'";
    $result = $conn->query($checkQuery);
    if ($result->num_rows > 1 ){
$licenseExists = true;
echo "<p style='color:red;'>License Number already exists in the database.</p>";
}
}
// Insert Truck Data
if (isset($_POST['add_truck']) && !$licenseExists) {
$licenseNumber = $_POST['digit1'] . $_POST['farsi_letter'] . $_POST['digit2'] . 'IR' . $_POST['digit3'];
$driverName = $conn->real_escape_string($_POST['driver_name']);
$phone = $conn->real_escape_string($_POST['phone']);
  $insertQuery = "INSERT INTO Trucks (LicenseNumber, DriverName, Phone, Status, Location) VALUES ('$licenseNumber', '$driverName', '$phone', 'Free', 'Entrance')";

if ($conn->query($insertQuery) === TRUE) {
    echo "<p style='color:green;'>New truck added successfully!</p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}
}

// HTML Form for Checking License Number
echo "<form method='post'>";
echo "<h2>Check Truck License Number</h2>";
echo "Digits: <input type='text' name='digit1' required size='2' maxlength='2'> ";
echo "Farsi Letter: <select name='farsi_letter'>";
foreach ($farsiLetters as $letter) {
echo "<option value='$letter'>$letter</option>";
}
echo "</select> ";
echo "Digits: <input type='text' name='digit2' required size='3' maxlength='3'> ";
echo "IR <input type='text' name='digit3' required size='2' maxlength='2'> ";
echo "<input type='submit' name='check_license' value='Check'>";
echo "</form>";

// HTML Form for Adding Truck
if (!$licenseExists) {
echo "<form method='post'>";
echo "<h2>Add New Truck</h2>";
echo "Digits: <input type='text' name='digit1' required size='2' maxlength='2' value='" . ($_POST['digit1'] ?? '') . "' readonly> ";
echo "Farsi Letter: <input type='text' name='farsi_letter' required value='" . ($_POST['farsi_letter'] ?? '') . "' readonly> ";
echo "Digits: <input type='text' name='digit2' required size='3' maxlength='3' value='" . ($_POST['digit2'] ?? '') . "' readonly> ";
echo "IR <input type='text' name='digit3' required size='2' maxlength='2' value='" . ($_POST['digit3'] ?? '') . "' readonly> ";
echo "<br><br>";
echo "Driver Name: <input type='text' name='driver_name' required> ";
echo "Phone: <input type='text' name='phone' required> ";
echo "<input type='submit' name='add_truck' value='Add Truck'>";
echo "</form>";
}

echo "</body></html>";

$conn->close();
?>  
