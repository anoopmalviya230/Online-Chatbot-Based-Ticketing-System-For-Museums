<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

require_once '../db_config.php';
require_once 'functions.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password, role FROM admins WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                // Password is correct
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_role'] = $row['role']; // Store Role
                logActivity($pdo, $row['id'], 'Login', "Admin logged in");
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Museum Ticket Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../loader.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex items-center justify-center">
    <!-- Loader Overlay -->
    <div id="loader"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); z-index: 9999; justify-content: center; align-items: center;">
        <div class="container" style="height: auto;">
            <div class="loader"></div>
            <p class="text">Logging in...</p>
        </div>
    </div>
    <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-100">
        <div class="text-center mb-8">
            <h1 class="text-lg font-semibold text-indigo-900 uppercase tracking-widest">The National Museum of India
            </h1>
            <h2 class="text-3xl font-bold mt-2 text-gray-800">Admin Portal</h2>
        </div>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    id="username" type="text" name="username" placeholder="Username">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                    id="password" type="password" name="password" placeholder="******************">
            </div>
            <div class="flex items-center justify-between">
                <button
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition duration-200"
                    type="submit">
                    Sign In
                </button>
            </div>
            <div class="mt-4 text-center">
                <a href="../../homepage.html" class="text-sm text-gray-600 hover:text-indigo-600">Back to Home</a>
            </div>
        </form>
    </div>
    <script>
        // Function to hide loader
        function hideLoader() {
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = 'none';
            }
        }

        // Function to check if admin is already logged in
        function checkAdminSession() {
            fetch('check_session.php')
                .then(res => res.json())
                .then(data => {
                    if (data.logged_in) {
                        // Admin is already logged in, redirect to dashboard
                        window.location.href = 'dashboard.php';
                    }
                })
                .catch(err => {
                    console.error('Session check error:', err);
                });
        }

        // Hide loader on page load
        window.addEventListener('load', function () {
            hideLoader();
            checkAdminSession(); // Check session on load
        });

        // Hide loader when page is shown (handles browser back button)
        window.addEventListener('pageshow', function (event) {
            hideLoader();
            checkAdminSession(); // Check session when page is shown
        });

        // Also hide loader immediately on DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            hideLoader();
        });

        // Show loader on form submit
        document.querySelector('form').addEventListener('submit', function () {
            document.getElementById('loader').style.display = 'flex';
        });
    </script>
</body>

</html>