<?php
session_start();
header('Content-Type: application/json');

include 'db_config.php';
include 'config_email.php';
include 'SimpleMailer.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$full_name = trim($data['full_name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$password = trim($data['password'] ?? '');

// Validate inputs
if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

try {
    // Check if email already exists in users table
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }
    $stmt->close();

    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Calculate expiry time
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

    // Delete any existing OTP for this email
    $deleteStmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
    $deleteStmt->bind_param("s", $email);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Store OTP in database
    $insertStmt = $conn->prepare("INSERT INTO email_otps (email, otp, full_name, phone, password, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("ssssss", $email, $otp, $full_name, $phone, $hashedPassword, $expires_at);

    if (!$insertStmt->execute()) {
        throw new Exception('Failed to store OTP');
    }
    $insertStmt->close();

    // Check if email is configured
    if (SMTP_USERNAME === 'your-email@gmail.com' || SMTP_PASSWORD === 'your-app-password-here') {
        // Email not configured - return OTP in response for testing
        echo json_encode([
            'success' => true,
            'message' => 'Email not configured. OTP for testing: ' . $otp,
            'testing_mode' => true,
            'otp' => $otp // Only for development!
        ]);
        exit;
    }

    // Send OTP email
    $mailer = new SimpleMailer(
        SMTP_HOST,
        SMTP_PORT,
        SMTP_USERNAME,
        SMTP_PASSWORD,
        SMTP_FROM_EMAIL,
        SMTP_FROM_NAME
    );

    $subject = "Your Museum Booking OTP: " . $otp;
    $htmlBody = getOTPEmailTemplate($full_name, $otp);

    $emailSent = $mailer->send($email, $full_name, $subject, $htmlBody);

    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent to your email address',
            'testing_mode' => false
        ]);
    } else {
        // Email failed but OTP is stored - allow manual verification
        echo json_encode([
            'success' => true,
            'message' => 'Email sending failed. OTP for testing: ' . $otp,
            'testing_mode' => true,
            'otp' => $otp
        ]);
    }

} catch (Exception $e) {
    error_log("Send OTP Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to send OTP. Please try again.']);
}

$conn->close();
?>