<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
require_once '../db_config.php';
require_once 'functions.php';


// Handle Batch Delete
if (isset($_POST['batch_delete']) && isset($_POST['ticket_ids'])) {
    $ticketIds = $_POST['ticket_ids'];
    try {
        foreach ($ticketIds as $id) {
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
            $stmt->execute([$id]);
        }
        logActivity($pdo, $_SESSION['admin_id'], 'Delete Tickets', "Deleted " . count($ticketIds) . " tickets");
        $success = count($ticketIds) . " ticket(s) deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting tickets: " . $e->getMessage();
    }
}

// Handle Delete All
if (isset($_POST['delete_all'])) {
    try {
        $pdo->exec("DELETE FROM tickets");
        logActivity($pdo, $_SESSION['admin_id'], 'Delete All Tickets', "Deleted ALL tickets");
        $success = "All tickets deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting all tickets: " . $e->getMessage();
    }
}

// Handle Single Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, $_SESSION['admin_id'], 'Delete Ticket', "Deleted Ticket ID: $id");
        $success = "Ticket deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting ticket: " . $e->getMessage();
    }
}

// Handle Status Update (Cancel/Confirm) - Simple Toggle or Set
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    $newStatus = ($action == 'cancel') ? 'cancelled' : 'paid'; // simplify to toggle for now or specific actions
    try {
        $stmt = $pdo->prepare("UPDATE tickets SET payment_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        logActivity($pdo, $_SESSION['admin_id'], 'Update Ticket', "Updated Ticket ID $id status to $newStatus");
        $success = "Ticket status updated to $newStatus.";
    } catch (PDOException $e) {
        $error = "Error updating ticket: " . $e->getMessage();
    }
}

// Fetch Tickets
// Fetch Tickets
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT tickets.*, users.full_name FROM tickets LEFT JOIN users ON tickets.customer_id = users.user_id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (tickets.booking_ref LIKE ? OR users.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter) {
    $sql .= " AND tickets.payment_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY tickets.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Tickets</title>
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
            <a href="tickets.php"
                class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Manage
                Tickets</a>
            <a href="transactions.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Transactions</a>
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
                <h2 class="text-2xl font-bold text-gray-800">Manage Tickets</h2>
                <p class="text-sm text-gray-500">The National Museum of India</p>
            </div>
            <form method="GET" class="flex gap-2 items-center">
                <select name="status"
                    class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="paid" <?php echo $statusFilter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="unpaid" <?php echo $statusFilter == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled
                    </option>
                </select>
                <input type="text" name="search" placeholder="Ref or Name..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Search</button>
                <?php if ($search || $statusFilter): ?><a href="tickets.php"
                        class="text-gray-500 hover:text-gray-700">Clear</a><?php endif; ?>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded my-6">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Ticket List (<?php echo count($tickets); ?>)</h3>
                <div class="flex gap-2">
                    <button onclick="deleteSelected()"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                        <i data-lucide="trash-2" class="inline w-4 h-4 mr-1"></i>
                        Delete Selected
                    </button>
                    <button onclick="deleteAll()"
                        class="bg-red-700 text-white px-4 py-2 rounded hover:bg-red-800 transition">
                        <i data-lucide="alert-triangle" class="inline w-4 h-4 mr-1"></i>
                        Delete All
                    </button>
                </div>
            </div>
            <form id="batchForm" method="POST">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                                </th>
                                <th class="py-3 px-6 text-left">Ref</th>
                                <th class="py-3 px-6 text-left">Customer</th>
                                <th class="py-3 px-6 text-left">Type</th>
                                <th class="py-3 px-6 text-center">Qty</th>
                                <th class="py-3 px-6 text-left">Date</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php foreach ($tickets as $t): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6">
                                        <input type="checkbox" name="ticket_ids[]" value="<?php echo $t['id']; ?>"
                                            class="ticket-checkbox">
                                    </td>
                                    <td class="py-3 px-6 text-left whitespace-nowrap font-bold">
                                        <?php echo $t['booking_ref']; ?>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <?php echo htmlspecialchars($t['full_name'] ?? 'Guest'); ?>
                                    </td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($t['ticket_type']); ?></td>
                                    <td class="py-3 px-6 text-center"><?php echo $t['quantity']; ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo $t['visit_date']; ?></td>
                                    <td class="py-3 px-6 text-center">
                                        <?php
                                        $statusColor = 'bg-yellow-200 text-yellow-600';
                                        if ($t['payment_status'] == 'paid')
                                            $statusColor = 'bg-green-200 text-green-600';
                                        if ($t['payment_status'] == 'cancelled')
                                            $statusColor = 'bg-red-200 text-red-600';
                                        ?>
                                        <span
                                            class="<?php echo $statusColor; ?> py-1 px-3 rounded-full text-xs"><?php echo $t['payment_status']; ?></span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center space-x-2">
                                            <?php if ($t['payment_status'] != 'cancelled'): ?>
                                                <a href="tickets.php?action=cancel&id=<?php echo $t['id']; ?>"
                                                    title="Cancel Ticket" onclick="return confirm('Cancel this ticket?');"
                                                    class="w-4 transform hover:text-red-500 hover:scale-110">
                                                    <i data-lucide="x-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="tickets.php?delete=<?php echo $t['id']; ?>" title="Delete Record"
                                                onclick="return confirm('Delete this record entirely?');"
                                                class="w-4 transform hover:text-red-500 hover:scale-110">
                                                <i data-lucide="trash-2"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <script>
        lucide.createIcons();

        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.ticket-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function deleteSelected() {
            const selected = document.querySelectorAll('.ticket-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one ticket to delete.');
                return;
            }
            if (confirm(`Are you sure you want to delete ${selected.length} ticket(s)?`)) {
                const form = document.getElementById('batchForm');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'batch_delete';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        }

        function deleteAll() {
            if (confirm('Are you sure you want to delete ALL tickets? This action cannot be undone!')) {
                if (confirm('This will permanently delete ALL tickets. Are you absolutely sure?')) {
                    const form = document.getElementById('batchForm');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_all';
                    input.value = '1';
                    form.appendChild(input);
                    form.submit();
                }
            }
        }
    </script>
</body>

</html>