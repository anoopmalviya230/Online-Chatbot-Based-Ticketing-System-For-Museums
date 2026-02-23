<?php
session_start();
require_once '../backend/db_config.php';

if (!isset($_GET['ref'])) {
    die("Invalid Request");
}

$ref = $_GET['ref'];

// Fetch ticket details
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE booking_ref = ?");
    $stmt->execute([$ref]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Booking not found.");
    }

    if ($ticket['payment_status'] === 'paid') {
        die("This ticket is already paid.");
    }

} catch (PDOException $e) {
    die("Database Error");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - National Museum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4" style="font-family: 'Inter', sans-serif;">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">

        <!-- Header -->
        <div class="bg-indigo-600 p-6 text-white text-center">
            <h1 class="text-2xl font-bold">Checkout</h1>
            <p class="text-indigo-100 text-sm mt-1">Order #<?php echo htmlspecialchars($ticket['booking_ref']); ?></p>
        </div>

        <!-- Order Summary -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex justify-between mb-2">
                <span class="text-gray-500">Exhibit</span>
                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($ticket['ticket_type']); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="text-gray-500">Date & Time</span>
                <span
                    class="font-medium text-gray-800"><?php echo htmlspecialchars($ticket['visit_date'] . ' ' . $ticket['time_slot']); ?></span>
            </div>
            <div class="flex justify-between mb-4">
                <span class="text-gray-500">Quantity</span>
                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($ticket['quantity']); ?></span>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-dashed border-gray-200">
                <span class="text-gray-600 font-bold">Total Amount</span>
                <span
                    class="text-2xl font-bold text-indigo-600">₹<?php echo htmlspecialchars($ticket['total_amount']); ?></span>
            </div>
        </div>

        <!-- Payment Options -->
        <div class="p-6 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Select Payment Method</h3>

            <form action="process.php" method="POST" class="space-y-3">
                <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ticket['booking_ref']); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($ticket['total_amount']); ?>">

                <!-- UPI -->
                <button type="submit" name="method" value="upi"
                    class="w-full flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl hover:border-indigo-500 hover:shadow-md transition cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <img src="https://cdn-icons-png.flaticon.com/512/2921/2921222.png" alt="UPI" class="w-8 h-8">
                        <span class="font-medium text-gray-800 group-hover:text-indigo-600">Pay via UPI</span>
                    </div>
                    <div class="w-4 h-4 border-2 border-gray-300 rounded-full group-hover:border-indigo-600"></div>
                </button>

                <!-- Card -->
                <button type="submit" name="method" value="card"
                    class="w-full flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl hover:border-indigo-500 hover:shadow-md transition cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <img src="https://cdn-icons-png.flaticon.com/512/11516/11516568.png" alt="Card" class="w-8 h-8">
                        <span class="font-medium text-gray-800 group-hover:text-indigo-600">Credit / Debit Card</span>
                    </div>
                    <div class="w-4 h-4 border-2 border-gray-300 rounded-full group-hover:border-indigo-600"></div>
                </button>

                <!-- Cancel -->
                <a href="process.php?action=cancel&ref=<?php echo htmlspecialchars($ticket['booking_ref']); ?>"
                    class="block text-center text-sm text-gray-400 hover:text-red-500 mt-4 transition">Cancel
                    Payment</a>
            </form>
        </div>

        <div class="p-4 bg-gray-100 text-center">
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd"></path>
                </svg>
                Secure Mock Payment Gateway
            </p>
        </div>
    </div>

</body>

</html>