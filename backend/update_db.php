<?php
require_once 'db_config.php';

try {
    // Create chat_sessions table to store the state of the user's current conversation
    $sql = "CREATE TABLE IF NOT EXISTS chat_sessions (
        user_id INT PRIMARY KEY,
        chat_state JSON NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Table 'chat_sessions' created successfully (or already exists).<br>";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "<br>";
}
?>