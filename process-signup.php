<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
$mysqli = require __DIR__ . "/database.php";

// Validate inputs
if (empty($_POST["name"])) {
    die("Name is required!");
}
if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    die("Valid Email is required");
}
if (strlen($_POST["password"]) < 8) {
    die("Password must be at least 8 characters");
}
if (!preg_match("/[a-z]/i", $_POST["password"])) {
    die("Password must contain at least one letter");
}
if (!preg_match("/[0-9]/i", $_POST["password"])) {
    die("Password must contain at least one number");
}
if ($_POST["password"] != $_POST["confirm-password"]) {
    die("Passwords must match");
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
$verification_token = bin2hex(random_bytes(16));
$role = "user";
$is_verified = 0;

$sql = "SELECT email, is_verified FROM users WHERE email = ?";
$stmt = $mysqli->stmt_init();

if (!$stmt->prepare($sql)) {
    die("SQL error: " . $mysqli->error);
}

$stmt->bind_param("s", $_POST["email"]);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Check if the email is already verified
    if ($user["is_verified"] == 1) {
        die("Email is already verified. Please <a href= 'login.php'> login. </a> " ) ;
    } else {
        // Resend verification email
        $sql = "UPDATE users SET verification_token = ? WHERE email = ?";
        $stmt = $mysqli->stmt_init();
        
        if (!$stmt->prepare($sql)) {
            die("SQL error: " . $mysqli->error);
        }

        $stmt->bind_param("ss", $verification_token, $_POST["email"]);

        if ($stmt->execute()) {
            $mail = new PHPMailer(true);

            try {
                // SMTP settings
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'eysiianabo@gmail.com'; 
                $mail->Password = 'vxwy akrw yttj pfyh'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('eysiianabo@gmail.com', 'Email Verification');
                $mail->addAddress($_POST["email"], $_POST["name"]);

                // Generate local verification link
                $base_url = "http://localhost/LoginAct";  
                $verification_link = "$base_url/verify-email.php?token=$verification_token";
                echo "Verification link: $verification_link"; 

                // Email content
                $mail->isHTML(true);
                $mail->Subject = "Verify Your Email Address";
                $mail->Body = "<h1>Verify Your Email</h1>
                            <p>Thank you for signing up! Click the link below to verify your email address:</p>
                            <a href='$verification_link'>$verification_link</a>";

                $mail->send();
                echo "<script>alert('A new verification email has been sent. Please check your email.'); window.location.href = 'signup.html';</script>";
            } catch (Exception $e) {
                die("Error sending verification email: " . $mail->ErrorInfo);
            }
        } else {
            die("Error updating verification token: " . $mysqli->error);
        }
    }
} else {
    // Insert new user
    $sql = "INSERT INTO users (fullname, email, password_hash, role, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->stmt_init();

    if (!$stmt->prepare($sql)) {
        die("SQL error: " . $mysqli->error);
    }

    $stmt->bind_param("sssssi", $_POST["name"], $_POST["email"], $password_hash, $role, $verification_token, $is_verified);

    if ($stmt->execute()) {
        $mail = new PHPMailer(true);

        try {
            // SMTP settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'eysiianabo@gmail.com'; 
            $mail->Password = 'vxwy akrw yttj pfyh'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('eysiianabo@gmail.com', 'Email Verification');
            $mail->addAddress($_POST["email"], $_POST["name"]);

            // Generate local verification link
            $base_url = "http://localhost/LoginAct";  
            $verification_link = "$base_url/verify-email.php?token=$verification_token";
            echo "Verification link: $verification_link"; 

            // Email content
            $mail->isHTML(true);
            $mail->Subject = "Verify Your Email Address";
            $mail->Body = "<h1>Verify Your Email</h1>
                        <p>Thank you for signing up! Click the link below to verify your email address:</p>
                        <a href='$verification_link'>$verification_link</a>";

            $mail->send();
            echo "<script>alert('Signup successful! Please check your email to verify your account.'); window.location.href = 'signup.html';</script>";
        } catch (Exception $e) {
            die("Error sending verification email: " . $mail->ErrorInfo);
        }
    } else {
        die("Error inserting user: " . $mysqli->error);
    }
}
?>
