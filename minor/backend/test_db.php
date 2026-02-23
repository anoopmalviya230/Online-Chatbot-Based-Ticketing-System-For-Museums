<?php
require_once 'db_config.php';

echo "Database connection successful!\n";

$tables = ['tickets', 'chat_sessions', 'chat_logs'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "Table '$table' exists.\n";
    } catch (PDOException $e) {
        echo "Table '$table' MISSING or error: " . $e->getMessage() . "\n";
    }
}
?>
