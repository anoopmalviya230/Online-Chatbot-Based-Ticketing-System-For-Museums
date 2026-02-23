<?php
// Database configuration
$host = 'localhost';
$dbname = 'museum_db';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (often empty)

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
// Set the PDO error mode to exception
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create MySQLi connection for backward compatibility
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}