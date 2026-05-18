<?php
require_once 'db_config.php';

try {
    // Create admins table
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'admins' created successfully.<br>";

    // Check if default admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insert default admin
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        echo "Default admin user created (Username: admin, Password: admin123).<br>";
    } else {
        echo "Admin user already exists.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>