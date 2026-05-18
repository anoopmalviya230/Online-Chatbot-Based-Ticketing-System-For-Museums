<?php
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    // Note: You have a confirm-password field in HTML, 
    // you might want to add a check here if ($password !== $_POST['confirm-password']) ...

    // ✅ Hash the password before saving
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Email already exists - Alert and go back
        echo "<script>
                alert('❌ Email already registered. Please use a different email.');
                window.history.back();
              </script>";
    } else {
        // Insert the new user
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $phone, $hashedPassword);

        if ($stmt->execute()) {
            // ✅ SUCCESS: Show Alert -> Redirect to Login Page
            echo "<script>
                    alert('✅ Signup Successful! Redirecting to Login Page...');
                    window.location.href = 'login_page.html';
                  </script>";
        } else {
            // Failed
            echo "<script>
                    alert('❌ Registration failed. Please try again.');
                    window.history.back();
                  </script>";
        }
        $stmt->close();
    }

    $check->close();
    $conn->close();
}
?>