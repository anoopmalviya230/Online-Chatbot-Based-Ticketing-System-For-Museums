<?php
// Function to log admin activity
function logActivity($pdo, $admin_id, $action, $details = null)
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_id, $action, $details, $ip]);
    } catch (PDOException $e) {
        // Silently fail or log to file if DB logging fails, to not interrupt user flow
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
?>