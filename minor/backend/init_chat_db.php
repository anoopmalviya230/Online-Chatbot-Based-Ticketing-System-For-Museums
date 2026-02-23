<?php
require_once 'db_config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS chat_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        sender ENUM('user', 'bot') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    )";

    $pdo->exec($sql);
    echo "Table 'chat_logs' created successfully (or already exists).";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>