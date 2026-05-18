<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../db_config.php';
require_once 'functions.php';


// Handle Batch Delete
if (isset($_POST['batch_delete']) && isset($_POST['user_ids'])) {
    $userIds = $_POST['user_ids'];
    try {
        foreach ($userIds as $id) {
            $pdo->prepare("DELETE FROM tickets WHERE customer_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM chat_logs WHERE user_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM chat_sessions WHERE user_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$id]);
        }
        logActivity($pdo, $_SESSION['admin_id'], 'Delete All Users', "Deleted all users");
        $success = count($userIds) . " user(s) deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting users: " . $e->getMessage();
    }
}

// Handle Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // Check if Super Admin
    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Super Admin') {
        $error = "Access Denied: Only Super Admins can reset passwords.";
    } else {
        $userId = $_POST['user_id'];
        $newPassword = $_POST['new_password'];

        if (strlen($newPassword) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                logActivity($pdo, $_SESSION['admin_id'], 'Reset Password', "Reset password for User ID: $userId");
                $success = "Password updated successfully.";
            } catch (PDOException $e) {
                $error = "Error updating password: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete All
if (isset($_POST['delete_all'])) {
    try {
        $pdo->exec("DELETE FROM tickets");
        $pdo->exec("DELETE FROM chat_logs");
        $pdo->exec("DELETE FROM chat_sessions");
        $pdo->exec("DELETE FROM users");
        logActivity($pdo, $_SESSION['admin_id'], 'Delete All Users', "Deleted ALL users and related data");
        $success = "All users deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting all users: " . $e->getMessage();
    }
}

// Handle Single Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Delete related tickets and chat logs first (if FK constraints don't cascade)
        $pdo->prepare("DELETE FROM tickets WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM chat_logs WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM chat_sessions WHERE user_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, $_SESSION['admin_id'], 'Delete User', "Deleted User ID: $id");
        $success = "User deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Fetch Users
$search = $_GET['search'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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
            <a href="users.php"
                class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Manage
                Users</a>
            <a href="tickets.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage
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
                <h2 class="text-2xl font-bold text-gray-800">Manage Users</h2>
                <p class="text-sm text-gray-500">The National Museum of India</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" placeholder="Search users..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Search</button>
                <?php if ($search): ?><a href="users.php"
                        class="text-gray-500 flex items-center hover:text-gray-700">Clear</a><?php endif; ?>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded my-6">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">User List (<?php echo count($users); ?>)</h3>
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
                                <th class="py-3 px-6 text-left">ID</th>
                                <th class="py-3 px-6 text-left">Full Name</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-left">Phone</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php foreach ($users as $user): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6">
                                        <input type="checkbox" name="user_ids[]" value="<?php echo $user['user_id']; ?>"
                                            class="user-checkbox">
                                    </td>
                                    <td class="py-3 px-6 text-left whitespace-nowrap font-bold">
                                        <?php echo $user['user_id']; ?>
                                    </td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center">
                                            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'Super Admin'): ?>
                                                <button type="button"
                                                    onclick="openResetModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')"
                                                    class="text-blue-600 hover:text-blue-900 mr-3 transition transform hover:scale-110"
                                                    title="Reset Password">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        class="lucide lucide-key">
                                                        <path
                                                            d="m21 2-2 2m-7.6 7.6a6 6 0 1 1-6.1-6.1c1.2-1.9 3.5-3.3 6.1-3.3 4.1 0 7.3 3.3 7.3 7.3 0 1.9-0.7 3.5-1.9 4.9" />
                                                        <path d="m9 15 3 3" />
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                            <a href="users.php?delete=<?php echo $user['user_id']; ?>"
                                                onclick="return confirm('Are you sure? This will delete all their tickets and chat history.');"
                                                class="w-4 mr-2 transform hover:text-red-500 hover:scale-110">
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
    <!-- Reset Password Modal -->
    <div id="resetModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Reset Password</h3>
                <form method="POST" class="mt-2 text-left">
                    <input type="hidden" name="user_id" id="resetUserId">
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 mb-2">New Password for <span id="resetUserName"
                                class="font-bold"></span>:</p>
                        <input type="text" name="new_password" required minlength="6" placeholder="Enter new password"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Must be at least 6 characters.</p>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="reset_password"
                            class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Update Password
                        </button>
                    </div>
                </form>
                <div class="mt-3">
                    <button onclick="closeResetModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function openResetModal(userId, userName) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUserName').textContent = userName;
            document.getElementById('resetModal').classList.remove('hidden');
        }

        function closeResetModal() {
            document.getElementById('resetModal').classList.add('hidden');
        }

        // Close modal on click outside
        window.onclick = function (event) {
            const modal = document.getElementById('resetModal');
            if (event.target == modal) {
                closeResetModal();
            }
        }

        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }

        function deleteSelected() {
            const selected = document.querySelectorAll('.user-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one user to delete.');
                return;
            }
            if (confirm(`Are you sure you want to delete ${selected.length} user(s)? This will also delete all their tickets and chat history.`)) {
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
            if (confirm('Are you sure you want to delete ALL users? This action cannot be undone!')) {
                if (confirm('This will permanently delete ALL users, tickets, and chat history. Are you absolutely sure?')) {
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