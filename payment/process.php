<?php
session_start();
require_once '../backend/db_config.php';

// Handle Cancel
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    $ref = $_GET['ref'] ?? '';
    // Optional: Update DB to 'cancelled' if desired, or leave pending
    header("Location: result.php?status=failed&ref=$ref");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref = $_POST['ref'];
    $method = $_POST['method'];

    if (!$ref) {
        die("Invalid Request");
    }

    try {
        // Simulate Processing Delay
        sleep(1);

        // Update Ticket Status
        $stmt = $pdo->prepare("UPDATE tickets SET payment_status = 'paid' WHERE booking_ref = ?");
        $stmt->execute([$ref]);

        // Redirect to Success
        header("Location: result.php?status=success&ref=$ref");
        exit();

    } catch (PDOException $e) {
        die("Payment Processing Error");
    }
} else {
    header("Location: ../backend/index.php");
}
?>