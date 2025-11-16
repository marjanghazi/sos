<?php
// config.php
$host = "localhost";
$username = "root"; // Change as per your setup
$password = ""; // Change as per your setup
$database = "sosenerg_dashboard";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>