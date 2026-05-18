<?php
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email == '' || $password == '') {
        echo "<script>
                alert('❌ Please enter both email and password.');
                window.history.back();
              </script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // ✅ Login Successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone']; // Storing phone if needed

            // Redirect to frontend
            echo "<script>
                    alert('✅ Login Successful! Welcome, " . htmlspecialchars($user['full_name']) . ".');
                    window.location.href = 'http://localhost/minor/backend/index.php'; 
                  </script>";

        } else {
            echo "<script>
                    alert('❌ Incorrect password.');
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script>
                alert('❌ No account found with that email.');
                window.history.back();
              </script>";
    }

    $stmt->close();
    $conn->close();

} else {
    echo "<h3>⚠️ Please use the form to login.</h3>";
}
?>