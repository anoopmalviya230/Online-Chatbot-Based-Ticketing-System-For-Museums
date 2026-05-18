<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_page.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$logs = [];

// Handle Clear History
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM chat_logs WHERE user_id = ?");
        $stmt->execute([$user_id]);
        header("Location: chat_history.php"); // Refresh to show empty state
        exit();
    } catch (PDOException $e) {
        $error = "Error clearing history: " . $e->getMessage();
    }
}

try {
    // Fetch all logs for the user, ordered by time (oldest first for readability)
    $stmt = $pdo->prepare("SELECT sender, message, created_at FROM chat_logs WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$user_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching history: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat History - National Museum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 flex flex-col h-screen">

    <!-- Header -->
    <header class="bg-white shadow-sm z-10">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center max-w-3xl">
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="text-indigo-600">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Chat History
            </h1>
            <div class="flex items-center gap-4">
                <form method="POST"
                    onsubmit="return confirm('Are you sure you want to delete your entire chat history? This cannot be undone.');">
                    <button type="submit" name="clear_history"
                        class="text-red-500 hover:text-red-700 font-medium text-sm flex items-center gap-1 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18" />
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                        </svg>
                        Clear History
                    </button>
                </form>
                <a href="index.php"
                    class="text-indigo-600 hover:text-indigo-800 font-medium text-sm flex items-center gap-1 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    Back to Chat
                </a>
            </div>
    </header>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-4">
        <div class="container mx-auto max-w-3xl bg-white rounded-lg shadow min-h-full p-6">
            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-600 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (empty($logs)): ?>
                <div class="text-center text-gray-500 py-10">
                    <p>No chat history found.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $isUser = ($log['sender'] === 'user');
                        $align = $isUser ? 'items-end' : 'items-start';
                        $bg = $isUser ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-gray-100 text-gray-800 rounded-bl-none';
                        $time = date("M d, h:i A", strtotime($log['created_at']));
                        ?>
                        <div class="flex flex-col <?php echo $align; ?>">
                            <div class="max-w-[80%] <?php echo $bg; ?> px-4 py-2 rounded-lg break-words shadow-sm">
                                <?php echo nl2br(htmlspecialchars($log['message'])); ?>
                            </div>
                            <span class="text-xs text-gray-400 mt-1"><?php echo $time; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>