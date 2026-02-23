<?php
// Database configuration
$host = '127.0.0.1';
$dbname = 'museum_db';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (often empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create chat_sessions table
    $sql = "CREATE TABLE IF NOT EXISTS chat_sessions (
        user_id INT PRIMARY KEY,
        chat_state JSON NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Table 'chat_sessions' created successfully (or already exists).";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>