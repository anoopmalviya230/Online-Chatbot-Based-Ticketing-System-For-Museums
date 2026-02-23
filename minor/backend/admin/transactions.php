<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../db_config.php';
require_once 'functions.php';

// Fetch Transactions
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name, u.email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        WHERE p.transaction_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? 
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("
        SELECT p.*, u.full_name, u.email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        ORDER BY p.payment_date DESC
    ");
}
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions</title>
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
            <a href="users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage
                Users</a>
            <a href="tickets.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage
                Tickets</a>
            <a href="transactions.php"
                class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Transactions</a>
            <a href="admins.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage
                Admins</a>
            <a href="logout.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-red-600 mt-10">Logout</a>
        </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Transactions</h2>
                <p class="text-sm text-gray-500">View all payment records</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" placeholder="Search ID or User..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Search</button>
                <?php if ($search): ?><a href="transactions.php"
                        class="text-gray-500 flex items-center hover:text-gray-700">Clear</a><?php endif; ?>
            </form>
        </div>

        <div class="bg-white shadow-md rounded my-6">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Transaction History (<?php echo count($transactions); ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">Transaction ID</th>
                            <th class="py-3 px-6 text-left">User</th>
                            <th class="py-3 px-6 text-left">Amount</th>
                            <th class="py-3 px-6 text-center">Status</th>
                            <th class="py-3 px-6 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left font-mono">
                                        <?php echo htmlspecialchars($t['transaction_id']); ?>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-medium text-gray-800"><?php echo htmlspecialchars($t['full_name'] ?? 'Unknown'); ?></span>
                                            <span class="text-xs text-gray-500">ID: <?php echo $t['user_id']; ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-6 text-left font-bold text-green-600">
                                        ₹<?php echo number_format($t['amount'], 2); ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs">
                                            <?php echo htmlspecialchars(ucfirst($t['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-right">
                                        <?php echo date('M d, Y h:i A', strtotime($t['payment_date'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-gray-500">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>