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

// Handle Create/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exhibition'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $imageUrl = trim($_POST['image_url']);
        $type = $_POST['type'];

        if (empty($title) || empty($imageUrl)) {
            $error = "Title and Image URL are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO exhibitions (title, description, image_url, type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $description, $imageUrl, $type]);
                logActivity($pdo, $_SESSION['admin_id'], 'Create Exhibition', "Created '$title'");
                $success = "Exhibition added successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_exhibition'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM exhibitions WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, $_SESSION['admin_id'], 'Delete Exhibition', "Deleted ID: $id");
        $success = "Exhibition deleted successfully.";
    } elseif (isset($_POST['update_exhibition'])) {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $imageUrl = trim($_POST['image_url']);
        $type = $_POST['type'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE exhibitions SET title=?, description=?, image_url=?, type=?, status=? WHERE id=?");
        $stmt->execute([$title, $description, $imageUrl, $type, $status, $id]);
        logActivity($pdo, $_SESSION['admin_id'], 'Update Exhibition', "Updated ID: $id");
        $success = "Exhibition updated successfully.";
    }
}

// Fetch Exhibitions
$exhibitions = $pdo->query("SELECT * FROM exhibitions ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Exhibitions</title>
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
    <div class="bg-indigo-900 text-white w-64 space-y-6 py-7 px-2 fixed inset-y-0 left-0 overflow-y-auto">
        <div class="px-4 mb-2 text-center">
            <h1 class="text-xl font-bold tracking-wider uppercase">National Museum</h1>
            <p class="text-xs text-indigo-300">Admin Portal</p>
        </div>
        <nav>
            <a href="dashboard.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Dashboard</a>
            <a href="exhibitions.php"
                class="block py-2.5 px-4 rounded transition duration-200 bg-indigo-800 hover:bg-indigo-700">Exhibitions</a>
            <a href="users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Users</a>
            <a href="tickets.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Tickets</a>
            <a href="admins.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Admins</a>
            <a href="audit_logs.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-indigo-700">Audit
                Logs</a>
            <a href="logout.php"
                class="block py-2.5 px-4 rounded transition duration-200 hover:bg-red-600 mt-10">Logout</a>
        </nav>
    </div>

    <!-- Content -->
    <div class="flex-1 ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Manage Exhibitions</h2>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 flex items-center">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Exhibition
            </button>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($exhibitions as $ex): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden relative group">
                    <img src="<?php echo htmlspecialchars($ex['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($ex['title']); ?>" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($ex['title']); ?></h3>
                            <span
                                class="text-xs font-semibold px-2 py-1 rounded <?php echo $ex['type'] == 'Permanent' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800'; ?>">
                                <?php echo $ex['type']; ?>
                            </span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2 line-clamp-3">
                            <?php echo htmlspecialchars($ex['description']); ?></p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-xs text-gray-400"><?php echo $ex['status']; ?></span>
                            <div class="flex gap-2">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($ex)); ?>)"
                                    class="text-blue-600 hover:text-blue-800"><i data-lucide="edit"></i></button>
                                <form method="POST" onsubmit="return confirm('Delete this exhibition?');" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $ex['id']; ?>">
                                    <button type="submit" name="delete_exhibition"
                                        class="text-red-600 hover:text-red-800"><i data-lucide="trash-2"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Add New Exhibition</h3>
            <form method="POST">
                <input type="text" name="title" placeholder="Title" required class="w-full mb-3 p-2 border rounded">
                <textarea name="description" placeholder="Description" required class="w-full mb-3 p-2 border rounded"
                    rows="3"></textarea>
                <input type="text" name="image_url" placeholder="Image URL (e.g., images/pic.jpg)" required
                    class="w-full mb-3 p-2 border rounded">
                <select name="type" class="w-full mb-3 p-2 border rounded">
                    <option value="Permanent">Permanent</option>
                    <option value="Special">Special</option>
                </select>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" name="add_exhibition"
                        class="px-4 py-2 bg-indigo-600 text-white rounded">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Edit Exhibition</h3>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="text" name="title" id="edit_title" placeholder="Title" required
                    class="w-full mb-3 p-2 border rounded">
                <textarea name="description" id="edit_description" placeholder="Description" required
                    class="w-full mb-3 p-2 border rounded" rows="3"></textarea>
                <input type="text" name="image_url" id="edit_image_url" placeholder="Image URL" required
                    class="w-full mb-3 p-2 border rounded">
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <select name="type" id="edit_type" class="w-full p-2 border rounded">
                        <option value="Permanent">Permanent</option>
                        <option value="Special">Special</option>
                    </select>
                    <select name="status" id="edit_status" class="w-full p-2 border rounded">
                        <option value="Active">Active</option>
                        <option value="Archived">Archived</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="px-4 py-2 text-gray-600">Cancel</button>
                    <button type="submit" name="update_exhibition"
                        class="px-4 py-2 bg-blue-600 text-white rounded">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function openEditModal(ex) {
            document.getElementById('edit_id').value = ex.id;
            document.getElementById('edit_title').value = ex.title;
            document.getElementById('edit_description').value = ex.description;
            document.getElementById('edit_image_url').value = ex.image_url;
            document.getElementById('edit_type').value = ex.type;
            document.getElementById('edit_status').value = ex.status;
            document.getElementById('editModal').classList.remove('hidden');
        }
    </script>
</body>

</html>