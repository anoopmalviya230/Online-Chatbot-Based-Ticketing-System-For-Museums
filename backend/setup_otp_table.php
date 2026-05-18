<?php
// Script to create email_otps table
include '../connect.php';

$sql = "CREATE TABLE IF NOT EXISTS email_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    full_name VARCHAR(50) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'email_otps' created successfully!<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Clean up expired OTPs
$cleanupSql = "DELETE FROM email_otps WHERE expires_at < NOW()";
if ($conn->query($cleanupSql) === TRUE) {
    echo "✅ Cleaned up expired OTPs<br>";
}

$conn->close();
?>