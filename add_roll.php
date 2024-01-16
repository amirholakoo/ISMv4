<?php
include 'connect_db.php';
include 'phpqrcode/qrlib.php'; // Path to phpqrcode library

// Add Roll to Products Table
if (isset($_POST['add_roll'])) {
    $reelNumber = $conn->real_escape_string($_POST['reel_number']);
    $width = $_POST['width'];
    $gsm = $_POST['gsm'];
    $length = $_POST['length'];
    $grade = $conn->real_escape_string($_POST['grade']);
    $breaks = $conn->real_escape_string($_POST['breaks']);
    $comments = $conn->real_escape_string($_POST['comments']);
    $status = 'In-Stock';
    $location = 'Production';

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO Products (ReelNumber, Width, GSM, Length, Grade, Breaks, Comments, Status, Location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiissss", $reelNumber, $width, $gsm, $length, $grade, $breaks, $comments, $status, $location);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>New roll added successfully!</p>";

        // Generate QR Code
        $qrData = "Reel Number: $reelNumber, Width: $width, GSM: $gsm, Breaks: $breaks, Comments: $comments";
        $qrCodePath = 'qrcodes/'.$reelNumber.'.png';
        QRcode::png($qrData, $qrCodePath);

        // Display QR Code
        echo "<p>QR Code for Reel Number $reelNumber:</p>";
        echo "<img src='$qrCodePath' />";
    } else {
        echo "<p style='color:red;'>Error adding roll: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// HTML Form for Adding Roll
echo "<form method='post'>";
echo "<h2>Add New Roll</h2>";
echo "Reel Number: <input type='text' name='reel_number' required> <br>";
echo "Width: <input type='number' name='width' required> <br>";
echo "GSM: <input type='number' name='gsm' required> <br>";
echo "Length: <input type='number' name='length' required> <br>";
echo "Grade: <input type='text' name='grade'> <br>";
echo "Breaks: <input type='text' name='breaks'> <br>";
echo "Comments: <textarea name='comments'></textarea> <br>";
echo "<input type='submit' name='add_roll' value='Add Roll'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
