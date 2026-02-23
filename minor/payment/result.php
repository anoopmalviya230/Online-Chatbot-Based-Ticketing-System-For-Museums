<?php
session_start();
$status = $_GET['status'] ?? 'failed';
$ref = $_GET['ref'] ?? 'Unknown';

$title = ($status === 'success') ? "Payment Successful!" : "Payment Failed";
$color = ($status === 'success') ? "text-green-600" : "text-red-600";
$bg_icon = ($status === 'success') ? "bg-green-100" : "bg-red-100";
$icon = ($status === 'success') ?
    '<svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
    '<svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 flex flex-col items-center justify-center min-h-screen p-4 font-[Inter]">

    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-sm w-full text-center">
        <div class="mx-auto flex items-center justify-center w-20 h-20 rounded-full <?php echo $bg_icon; ?> mb-6">
            <?php echo $icon; ?>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2"><?php echo $title; ?></h1>

        <?php if ($status === 'success'): ?>
            <p class="text-gray-500 mb-6">Thank you for your booking. A receipt has been sent to your dashboard.</p>
        <?php else: ?>
            <p class="text-gray-500 mb-6">The transaction operation was cancelled/failed. No funds were deducted.</p>
        <?php endif; ?>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <span class="text-xs text-gray-400 uppercase tracking-widest block mb-1">Transaction Ref</span>
            <span class="text-lg font-mono font-semibold text-gray-800"><?php echo htmlspecialchars($ref); ?></span>
        </div>

        <a href="../backend/index.php"
            class="block w-full bg-indigo-600 text-white font-semibold py-3 rounded-lg hover:bg-indigo-700 transition">
            Return to Chat
        </a>
    </div>

</body>

</html>