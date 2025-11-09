<?php
session_start();
include("../config/db.php");

echo "<h2>Testing Customers Table</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'customers'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Customers table exists</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE customers");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h3>Sample Data (first 5 records):</h3>";
    $data = $conn->query("SELECT * FROM customers LIMIT 5");
    if ($data->num_rows > 0) {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th></tr>";
        while($row = $data->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No data found in customers table</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Customers table does not exist</p>";
}

$conn->close();
?>