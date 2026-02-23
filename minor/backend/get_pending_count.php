<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

try {
    require_once 'db_config.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            "count" => 0,
            "bookings" => []
        ]);
        exit();
    }

    $userId = $_SESSION['user_id'];

    // Get all pending bookings for this user
    $stmt = $pdo->prepare("SELECT id, booking_ref, ticket_type, visit_date, time_slot, total_amount FROM tickets WHERE customer_id = ? AND payment_status = 'pending' ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "count" => count($bookings),
        "bookings" => $bookings
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database error",
        "count" => 0,
        "bookings" => []
    ]);
}
?>