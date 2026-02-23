<?php
session_start();
header('Content-Type: application/json');

include 'db_config.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$email = trim($data['email'] ?? '');
$otp = trim($data['otp'] ?? '');

// Validate inputs
if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'error' => 'Email and OTP are required']);
    exit;
}

try {
    // Retrieve OTP record
    $stmt = $conn->prepare("SELECT * FROM email_otps WHERE email = ? AND otp = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid OTP']);
        exit;
    }

    $otpRecord = $result->fetch_assoc();
    $stmt->close();

    // Check if OTP has expired
    $currentTime = date('Y-m-d H:i:s');
    if ($currentTime > $otpRecord['expires_at']) {
        // Delete expired OTP
        $deleteStmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        $deleteStmt->close();

        echo json_encode(['success' => false, 'error' => 'OTP has expired. Please request a new one.']);
        exit;
    }

    // OTP is valid - Create user account
    $full_name = $otpRecord['full_name'];
    $phone = $otpRecord['phone'];
    $hashedPassword = $otpRecord['password'];

    // Insert user into users table
    $insertStmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $full_name, $email, $phone, $hashedPassword);

    if ($insertStmt->execute()) {
        // Delete used OTP
        $deleteStmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        $deleteStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully!',
            'redirect' => 'login_page.html'
        ]);
    } else {
        throw new Exception('Failed to create user account');
    }

    $insertStmt->close();

} catch (Exception $e) {
    error_log("Verify OTP Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Verification failed. Please try again.']);
}

$conn->close();
?>