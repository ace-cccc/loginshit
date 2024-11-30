<?php
session_start();
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('26117127430-lidvqh2ruq7ebmoat050pl6aqtd0ehs4.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/LoginAct/google-callback.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $oauth = new Google_Service_Oauth2($client);
    $user_info = $oauth->userinfo->get();

    // Check if user already exists
    $mysqli = require __DIR__ . '/database.php';
    $email = $user_info->email;

    $sql = "SELECT id, fullname, email FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // User exists, log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = 'user';
    } else {
        // User does not exist, create a new user
        $fullname = $user_info->name;
        $password_hash = password_hash(random_bytes(16), PASSWORD_DEFAULT); // Generate a random password

        $sql = "INSERT INTO users (fullname, email, password_hash, role, is_verified) VALUES (?, ?, ?, 'user', 1)";
        $stmt = $mysqli->stmt_init();

        if (!$stmt->prepare($sql)) {
            die('SQL error: ' . $mysqli->error);
        }

        $stmt->bind_param("sss", $fullname, $email, $password_hash);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $mysqli->insert_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['role'] = 'user';
        } else {
            die('Error creating user: ' . $mysqli->error);
        }
    }

    // Redirect to home page
    header('Location: index.php');
    exit;
} else {
    die('Error during Google authentication.');
}
?>
