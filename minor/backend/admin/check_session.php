<?php
session_start();
header('Content-Type: application/json');

// Return JSON response indicating if admin is logged in
echo json_encode([
    'logged_in' => isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true
]);
?>