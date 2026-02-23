<?php
session_start();
require_once '../backend/db_config.php';
require_once 'razorpay_config.php';

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

// Create Razorpay Order
$api_key = RAZORPAY_KEY_ID;
$api_secret = RAZORPAY_KEY_SECRET;

$orderData = [
    'receipt'         => $ticket['booking_ref'],
    'amount'          => $ticket['total_amount'] * 100, // Amount in paise
    'currency'        => 'INR',
    'payment_capture' => 1 // Auto capture
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':' . $api_secret);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderData));

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die('Razorpay Error: ' . curl_error($ch));
}

curl_close($ch);

$orderResponse = json_decode($response, true);

if ($http_status !== 200 || isset($orderResponse['error'])) {
    die("Error creating Razorpay order: " . ($orderResponse['error']['description'] ?? 'Unknown error'));
}

$razorpayOrderId = $orderResponse['id'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - National Museum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Payment Method</h3>

            <button id="rzp-button1"
                class="w-full flex items-center justify-center p-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 hover:shadow-lg transition cursor-pointer transform active:scale-95">
                Pay Now
            </button>

             <!-- Cancel -->
             <a href="process.php?action=cancel&ref=<?php echo htmlspecialchars($ticket['booking_ref']); ?>"
                    class="block text-center text-sm text-gray-400 hover:text-red-500 mt-4 transition">Cancel
                    Payment</a>
        </div>

        <div class="p-4 bg-gray-100 text-center">
            <p class="text-xs text-gray-500 flex items-center justify-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd"></path>
                </svg>
                Secured by Razorpay
            </p>
        </div>
    </div>

    <!-- Hidden Form for processing -->
    <form name='razorpayform' action="process.php" method="POST">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_signature"  id="razorpay_signature" >
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" >
        <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ticket['booking_ref']); ?>">
    </form>


    <script>
    var options = {
        "key": "<?php echo RAZORPAY_KEY_ID; ?>",
        "amount": "<?php echo $ticket['total_amount'] * 100; ?>", 
        "currency": "INR",
        "name": "National Museum",
        "description": "Ticket Booking",
        "image": "https://cdn-icons-png.flaticon.com/512/2921/2921222.png", // Optional Logo
        "order_id": "<?php echo $razorpayOrderId; ?>", 
        "handler": function (response){
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            document.razorpayform.submit();
        },
        "prefill": {
            // Optional: You can prefill user details here if available in session
            // "name": "Gaurav Kumar",
            // "email": "gaurav.kumar@example.com",
            // "contact": "9999999999"
        },
        "notes": {
            "address": "National Museum"
        },
        "theme": {
            "color": "#4F46E5"
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.on('payment.failed', function (response){
        alert(response.error.description);
        // You can redirect to a failure page here if you want
    });
    document.getElementById('rzp-button1').onclick = function(e){
        rzp1.open();
        e.preventDefault();
    }
    </script>

</body>
</html>