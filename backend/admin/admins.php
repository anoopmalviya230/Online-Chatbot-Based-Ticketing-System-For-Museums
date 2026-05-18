<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
require_once '../db_config.php';
require_once 'functions.php';

$success = '';
$error = '';

// Handle Admin Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $adminId = $_POST['reset_admin_id'];
    $newPassword = $_POST['new_password'];

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $adminId])) {
                logActivity($pdo, $_SESSION['admin_id'], 'Reset Admin Password', "Reset password for Admin ID: $adminId");
                $success = "Admin password updated successfully.";
            } else {
                $error = "Failed to update password.";
            }
        } catch (PDOException $e) {
            $error = "Error updating password: " . $e->getMessage();
        }
    }
}

// Handle Create Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username already exists.";
        } else {
            // Create new admin
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hashedPassword, $role])) {
                logActivity($pdo, $_SESSION['admin_id'], 'Create Admin', "Created admin '$username' as $role");
                $success = "Admin '$username' created successfully!";
            } else {
                $error = "Failed to create admin.";
            }
        }
    }
}

// Handle Delete Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $adminId = $_POST['admin_id'];

    // Prevent deleting yourself
    if ($adminId == $_SESSION['admin_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        if ($stmt->execute([$adminId])) {
            logActivity($pdo, $_SESSION['admin_id'], 'Delete Admin', "Deleted admin ID: $adminId");
            $success = "Admin deleted successfully!";
        } else {
            $error = "Failed to delete admin.";
        }
    }
}

// Fetch all admins
$stmt = $pdo->query("SELECT id, username, role, created_at FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins</title>
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
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Dashboard</a>
                <a href="users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage
                    Users</a>
                <a href="tickets.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Manage Tickets</a>
                <a href="transactions.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Transactions</a>
                <a href="admins.php"
                    class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Manage
                    Admins</a>
                <a href="logout.php"
                    class="block py-2.5 px-4 rounded transition duration-200 hover:bg-red-600 mt-10">Logout</a>
            </nav>
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b p-4">
                <div class="flex justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Manage Admins</h2>
                    <span class="text-gray-600">Welcome,
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Create Admin Form -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Create New Admin</h3>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                <input type="text" name="username" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Enter username">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                <input type="password" name="password" required minlength="6"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Min. 6 characters">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select name="role"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Editor">Editor</option>
                                    <option value="Viewer">Viewer</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="create_admin"
                            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition duration-200">
                            <i data-lucide="user-plus" class="inline w-4 h-4 mr-1"></i>
                            Create Admin
                        </button>
                    </form>
                </div>

                <!-- Admins List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Admin Accounts (<?php echo count($admins); ?>)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Username</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created At</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $admin['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="flex-shrink-0 h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                    <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($admin['username']); ?>
                                                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                            <span
                                                                class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($admin['role'] ?? 'Super Admin'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y h:i A', strtotime($admin['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm flex items-center">
                                            <button type="button"
                                                onclick="openResetModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars(addslashes($admin['username'])); ?>')"
                                                class="text-blue-600 hover:text-blue-900 mr-3 transition transform hover:scale-110"
                                                title="Reset Password">
                                                <i data-lucide="key" class="w-4 h-4"></i>
                                            </button>
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                <form method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                    <button type="submit" name="delete_admin"
                                                        class="text-red-600 hover:text-red-900">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Cannot delete yourself</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
                    <i data-lucide="key" class="w-6 h-6 text-indigo-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Reset Password</h3>
                <form method="POST" class="mt-2 text-left">
                    <input type="hidden" name="reset_admin_id" id="resetAdminId">
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 mb-2">New Password for <span id="resetAdminName"
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

        function openResetModal(adminId, adminName) {
            document.getElementById('resetAdminId').value = adminId;
            document.getElementById('resetAdminName').textContent = adminName;
            document.getElementById('resetModal').classList.remove('hidden');
        }

        function closeResetModal() {
            document.getElementById('resetModal').classList.add('hidden');
        }

        window.onclick = function (event) {
            const modal = document.getElementById('resetModal');
            if (event.target == modal) {
                closeResetModal();
            }
        }
    </script>
</body>

</html>