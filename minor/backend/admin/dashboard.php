<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
require_once '../db_config.php';

// Fetch User Count
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

// Fetch Ticket Count
$stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
$ticketCount = $stmt->fetchColumn();

// Fetch Revenue
$stmt = $pdo->query("SELECT SUM(total_amount) FROM tickets WHERE payment_status = 'paid'");
$revenue = $stmt->fetchColumn();
$revenue = $revenue ? number_format($revenue, 2) : "0.00";

// --- NEW STATS ---
$todayDate = date('Y-m-d');

// Today's Visitors (Paid) - Visitors expected today
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM tickets WHERE visit_date = ? AND payment_status = 'paid'");
$stmt->execute([$todayDate]);
$todayVisitors = $stmt->fetchColumn() ?: 0;

// Availability (Limit - Reserved)
// Reserved = Paid + Pending bookings for today
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM tickets WHERE visit_date = ? AND payment_status != 'cancelled'");
$stmt->execute([$todayDate]);
$reserved = $stmt->fetchColumn() ?: 0;
$dailyLimit = 2000;
$availability = $dailyLimit - $reserved;
if ($availability < 0)
    $availability = 0;


// Chart Data: Revenue Last 7 Days & Visitor Counts
$dates = [];
$revenues = [];
$visitorCounts = []; // For new chart

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('d M', strtotime($date));

    // Revenue
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM tickets WHERE payment_status = 'paid' AND DATE(created_at) = ?");
    $stmt->execute([$date]);
    $amt = $stmt->fetchColumn() ?: 0;
    $revenues[] = $amt;

    // Visitors (By Visit Date)
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM tickets WHERE visit_date = ? AND payment_status = 'paid'");
    $stmt->execute([$date]);
    $vc = $stmt->fetchColumn() ?: 0;
    $visitorCounts[] = $vc;
}

// Future Bookings (Next 14 Days)
$futureDates = [];
$futureCounts = [];
for ($i = 0; $i <= 14; $i++) {
    $fDate = date('Y-m-d', strtotime("+$i days"));
    $futureDates[] = date('d M', strtotime($fDate));
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM tickets WHERE visit_date = ? AND payment_status != 'cancelled'");
    $stmt->execute([$fDate]);
    $cnt = $stmt->fetchColumn() ?: 0;
    $futureCounts[] = $cnt;
}


// Chart Data: Ticket Types
$ticketTypes = [];
$typeCounts = [];
$stmt = $pdo->query("SELECT ticket_type, COUNT(*) as count FROM tickets GROUP BY ticket_type");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ticketTypes[] = ucfirst($row['ticket_type']);
    $typeCounts[] = $row['count'];
}

// All Time Visitors
$allTimeDates = [];
$allTimeCounts = [];
$stmt = $pdo->query("SELECT visit_date, SUM(quantity) as total FROM tickets WHERE payment_status = 'paid' GROUP BY visit_date ORDER BY visit_date ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $allTimeDates[] = date('d M', strtotime($row['visit_date']));
    $allTimeCounts[] = $row['total'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div
            class="bg-indigo-900 text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out">
            <div class="px-4">
                <h1 class="text-2xl font-bold">Admin Panel</h1>
            </div>
            <nav>
                <a href="dashboard.php"
                    class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Dashboard</a>
                <a href="users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage
                    Users</a>
                <a href="tickets.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage Tickets</a>
                <a href="transactions.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Transactions</a>
                <a href="admins.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage Admins</a>
                <a href="logout.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-red-600 mt-10">Logout</a>
            </nav>
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b p-4">
                <div class="flex justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                    <span class="text-gray-600">Welcome,
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
                    <!-- Existing Stats -->
                    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Users</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $userCount; ?></h3>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                                <i data-lucide="users"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Bookings</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $ticketCount; ?></h3>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full text-green-600">
                                <i data-lucide="ticket"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Total Revenue</p>
                                <h3 class="text-lg font-bold text-gray-800">₹<?php echo $revenue; ?></h3>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full text-yellow-600">
                                <i data-lucide="indian-rupee"></i>
                            </div>
                        </div>
                    </div>

                    <!-- NEW Stats -->
                    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Today's Visitors</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $todayVisitors; ?></h3>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full text-purple-600">
                                <i data-lucide="footprints"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Availability</p>
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $availability; ?></h3>
                                <p class="text-xs text-gray-400">/ 2000</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full text-orange-600">
                                <i data-lucide="calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Revenue Trend (Last 7 Days)</h3>
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Visitor Trend (Last 7 Days)</h3>
                        <canvas id="visitorChart"></canvas>
                    </div>
                </div>

                <!-- Future Bookings Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Future Bookings (Next 14 Days)</h3>
                    <div class="h-64">
                        <canvas id="futureChart"></canvas>
                    </div>
                </div>

                <!-- All Time Visitors Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Visitor Trend (All Time)</h3>
                    <div class="h-64">
                        <canvas id="allTimeVisitorChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Ticket Type Distribution</h3>
                        <div class="h-64 flex justify-center">
                            <canvas id="ticketChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="flex gap-4">
                        <a href="users.php" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">View
                            Users</a>
                        <a href="tickets.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">View
                            Tickets</a>
                    </div>
                </div>

            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        lucide.createIcons();

        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: { responsive: true }
        });

        const visitorCtx = document.getElementById('visitorChart').getContext('2d');
        new Chart(visitorCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Visitors',
                    data: <?php echo json_encode($visitorCounts); ?>,
                    backgroundColor: 'rgba(147, 51, 234, 0.6)',
                    borderColor: 'rgb(147, 51, 234)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });

        const futureCtx = document.getElementById('futureChart').getContext('2d');
        new Chart(futureCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($futureDates); ?>,
                datasets: [{
                    label: 'Booked Tickets',
                    data: <?php echo json_encode($futureCounts); ?>,
                    backgroundColor: 'rgba(245, 158, 11, 0.6)',
                    borderColor: 'rgb(245, 158, 11)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 10 // visual helper
                    }
                }
            }
        });

        const ticketCtx = document.getElementById('ticketChart').getContext('2d');
        new Chart(ticketCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($ticketTypes); ?>,
                datasets: [{
                    data: <?php echo json_encode($typeCounts); ?>,
                    backgroundColor: [
                        'rgb(59, 130, 246)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)', 'rgb(139, 92, 246)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const allTimeCtx = document.getElementById('allTimeVisitorChart').getContext('2d');
        new Chart(allTimeCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($allTimeDates); ?>,
                datasets: [{
                    label: 'Total Visitors',
                    data: <?php echo json_encode($allTimeCounts); ?>,
                    borderColor: 'rgb(236, 72, 153)', // Pink-500
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>

</html>