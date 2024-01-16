<?php
include 'connect_db.php';

// Add Supplier
if (isset($_POST['add_supplier'])) {
    $supplierName = $conn->real_escape_string($_POST['supplier_name']);
    $address = $conn->real_escape_string($_POST['supplier_address']);
    $phone = $conn->real_escape_string($_POST['supplier_phone']);

    $insertSupplier = "INSERT INTO Suppliers (SupplierName, Address, Phone) VALUES ('$supplierName', '$address', '$phone')";
    
    if ($conn->query($insertSupplier) === TRUE) {
        echo "<p style='color:green;'>New supplier added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error adding supplier: " . $conn->error . "</p>";
    }
}

// Add Customer
if (isset($_POST['add_customer'])) {
    $customerName = $conn->real_escape_string($_POST['customer_name']);
    $address = $conn->real_escape_string($_POST['customer_address']);
    $phone = $conn->real_escape_string($_POST['customer_phone']);

    $insertCustomer = "INSERT INTO Customers (CustomerName, Address, Phone) VALUES ('$customerName', '$address', '$phone')";
    
    if ($conn->query($insertCustomer) === TRUE) {
        echo "<p style='color:green;'>New customer added successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error adding customer: " . $conn->error . "</p>";
    }
}

// HTML Form for Adding Supplier
echo "<form method='post'>";
echo "<h2>Add New Supplier</h2>";
echo "Name: <input type='text' name='supplier_name' required> ";
echo "Address: <textarea name='supplier_address' required></textarea> ";
echo "Phone: <input type='text' name='supplier_phone' required> ";
echo "<input type='submit' name='add_supplier' value='Add Supplier'>";
echo "</form>";

// HTML Form for Adding Customer
echo "<form method='post'>";
echo "<h2>Add New Customer</h2>";
echo "Name: <input type='text' name='customer_name' required> ";
echo "Address: <textarea name='customer_address' required></textarea> ";
echo "Phone: <input type='text' name='customer_phone' required> ";
echo "<input type='submit' name='add_customer' value='Add Customer'>";
echo "</form>";

echo "</body></html>";

$conn->close();
?>
