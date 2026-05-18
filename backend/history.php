<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_page.html");
    exit();
}
require_once 'db_config.php';

$userId = $_SESSION['user_id'];

$sql = "SELECT tickets.*, users.full_name, users.phone 
        FROM tickets 
        LEFT JOIN users ON tickets.customer_id = users.user_id 
        WHERE tickets.customer_id = ?
        ORDER BY tickets.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Acro Museum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

    <header class="bg-white shadow-md">
        <div class="container mx-auto max-w-5xl px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center">
                    <!-- Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                        <path d="M13 5v2" />
                        <path d="M13 17v2" />
                        <path d="M13 11v2" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-800">My Reports</h1>
            </div>
            <a href="index.php"
                class="text-indigo-600 hover:text-indigo-800 font-medium text-sm transition duration-200">
                &larr; Back to Chatbot
            </a>
        </div>
    </header>

    <main class="container mx-auto max-w-5xl px-4 py-8">

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Booking Records</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-xs leading-normal">
                            <th class="py-3 px-6">Booking Ref</th>
                            <th class="py-3 px-6">Customer</th>
                            <th class="py-3 px-6">Phone</th>
                            <th class="py-3 px-6">Event/Type</th>
                            <th class="py-3 px-6 text-center">Qty</th>
                            <th class="py-3 px-6">Date & Time</th>
                            <th class="py-3 px-6 text-right">Amount</th>
                            <th class="py-3 px-6 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php if (count($bookings) > 0): ?>
                            <?php foreach ($bookings as $row): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition duration-150">
                                    <td class="py-3 px-6 font-medium text-indigo-600 whitespace-nowrap">
                                        <?php echo htmlspecialchars($row['booking_ref']); ?>
                                    </td>
                                    <td class="py-3 px-6 font-medium text-gray-800">
                                        <?php echo htmlspecialchars($row['full_name'] ? $row['full_name'] : 'Guest'); ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <?php echo htmlspecialchars($row['phone'] ? $row['phone'] : '-'); ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <?php echo htmlspecialchars($row['ticket_type']); ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs">
                                            <?php echo htmlspecialchars($row['quantity']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6">
                                        <div><?php echo htmlspecialchars($row['visit_date']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['time_slot']); ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-6 text-right font-medium text-gray-800">
                                        ₹<?php echo htmlspecialchars($row['total_amount']); ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php if ($row['payment_status'] === 'paid'): ?>
                                            <span
                                                class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs font-semibold">Paid</span>
                                        <?php elseif ($row['payment_status'] === 'cancelled'): ?>
                                            <span
                                                class="bg-red-100 text-red-700 py-1 px-3 rounded-full text-xs font-semibold">Cancelled</span>
                                        <?php else: ?>
                                            <span
                                                class="bg-yellow-100 text-yellow-700 py-1 px-3 rounded-full text-xs font-semibold"><?php echo htmlspecialchars($row['payment_status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-6 px-6 text-center text-gray-500">
                                    No records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>

</html>