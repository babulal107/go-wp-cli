<?php
$host='localhost';
$database='gsthero_live';
$username='root';
$password='root';

$conn = new MySQLi($host, $username, $password, $database);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
