<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
require_once '../db_config.php';

// Fetch Logs
$stmt = $pdo->query("SELECT audit_logs.*, admins.username FROM audit_logs LEFT JOIN admins ON audit_logs.admin_id = admins.id ORDER BY created_at DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <!-- Sidebar -->
    <div class="bg-indigo-900 text-white w-64 space-y-6 py-7 px-2 fixed inset-y-0 left-0">
        <div class="px-4 mb-2 text-center">
            <h1 class="text-xl font-bold tracking-wider uppercase">National Museum</h1>
            <p class="text-xs text-indigo-300">Admin Portal</p>
        </div>
        <nav>
            <a href="dashboard.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Dashboard</a>
            <a href="exhibitions.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Exhibitions</a>
            <a href="users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Users</a>
            <a href="tickets.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Tickets</a>
            <a href="admins.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Admins</a>
            <a href="audit_logs.php"
                class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Audit
                Logs</a>
            <a href="logout.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-red-600 mt-10">Logout</a>
        </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Audit Logs</h2>
            <p class="text-sm text-gray-500">Track recent administrative activities.</p>
        </div>

        <div class="bg-white shadow-md rounded my-6 overflow-hidden">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Time</th>
                        <th class="py-3 px-6 text-left">Admin</th>
                        <th class="py-3 px-6 text-left">Action</th>
                        <th class="py-3 px-6 text-left">Details</th>
                        <th class="py-3 px-6 text-left">IP</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($logs as $log): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                <?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
                            <td class="py-3 px-6 text-left font-medium">
                                <?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                            <td class="py-3 px-6 text-left">
                                <span
                                    class="bg-blue-100 text-blue-600 py-1 px-3 rounded-full text-xs"><?php echo htmlspecialchars($log['action']); ?></span>
                            </td>
                            <td class="py-3 px-6 text-left max-w-md truncate"
                                title="<?php echo htmlspecialchars($log['details']); ?>">
                                <?php echo htmlspecialchars($log['details']); ?>
                            </td>
                            <td class="py-3 px-6 text-left text-xs"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>

</html>