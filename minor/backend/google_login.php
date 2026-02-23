<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Get the posted JSON data (the credential)
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['credential'])) {
    echo json_encode(['success' => false, 'error' => 'No credential provided']);
    exit();
}

$id_token = $data['credential'];

// Verify the token with Google's API (simple cURL way, no composer needed)
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$payload = json_decode($response, true);

if (isset($payload['error_description']) || !isset($payload['email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit();
}

// Token is valid!
$email = $payload['email'];
$name = isset($payload['name']) ? $payload['name'] : 'Google User';
// Optional: $picture = $payload['picture'];

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists -> Log them in
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['phone'] = $user['phone']; // Might be empty if google login used initially

        echo json_encode(['success' => true, 'redirect' => 'backend/index.php']);
    } else {
        // User does not exist -> Register them
        // We set a random password because they are using Google Login
        $random_password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
        // Phone default to 0000000000 since Google doesn't always provide it
        $phone = 0000000000;

        $insert = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
        $insert->execute([$name, $email, $phone, $random_password]);

        $newUserId = $pdo->lastInsertId();

        $_SESSION['user_id'] = $newUserId;
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;

        echo json_encode(['success' => true, 'redirect' => 'backend/index.php']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>