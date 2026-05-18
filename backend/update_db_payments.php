<?php
require_once 'db_config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(100) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        status VARCHAR(50) DEFAULT 'completed',
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);
    echo "Payments table created successfully or already exists.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
