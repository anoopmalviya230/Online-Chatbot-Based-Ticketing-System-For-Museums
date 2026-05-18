<?php
session_start();
header('Content-Type: application/json');

// Return JSON response indicating if user is logged in
echo json_encode([
    'logged_in' => isset($_SESSION['user_id'])
]);
?>