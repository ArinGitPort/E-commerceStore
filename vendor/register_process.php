<?php
require '../config/db_connection.php';
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Secure input function
function validate_input($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// === Input Validation ===
$errors = [];
$firstName = validate_input($_POST['firstName'] ?? '');
$lastName = validate_input($_POST['lastName'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$email = filter_var($email, FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

// Validate fields
if (empty($firstName)) $errors[] = "First name is required.";
if (empty($lastName)) $errors[] = "Last name is required.";
if (!$email) $errors[] = "Valid email is required.";
if (empty($password)) $errors[] = "Password is required.";
if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";
if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";

if (!empty($errors)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'errors' => $errors]));
}

try {
    $pdo->beginTransaction();

    // === Get Role ID for Customers ===
    $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'Customer' LIMIT 1");
    $stmt->execute();
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        throw new Exception("Role 'Customer' does not exist in database.");
    }
    $roleId = $role['role_id'];

    // === Check if Email Already Exists ===
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        throw new Exception("Email already registered.");
    }

    // === Hash Password ===
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // === Insert User ===
    $fullName = "$firstName $lastName";
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $hashedPassword, $roleId]);
    $userId = $pdo->lastInsertId();

    // === Generate Verification Token ===
    $token = bin2hex(random_bytes(32));
    $verifyLink = "https://yourdomain.com/verify.php?token=$token&email=" . urlencode($email);

    // === Store Token in Database ===
    $stmt = $pdo->prepare("INSERT INTO email_verification (user_id, token, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $token]);

    // === Send Email using PHPMailer ===
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; // For debugging
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your@gmail.com'; // Your full Gmail address
        $mail->Password   = 'your_app_password'; // App password (see below)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Important additional settings
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $mail->setFrom('your@gmail.com', 'Bunniwinkle'); // Must match SMTP username
        $mail->addAddress($email, $fullName);
        $mail->addReplyTo('support@yourdomain.com', 'Bunniwinkle Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify your Bunniwinkle account';
        $mail->Body    = "
            <h2>Welcome to Bunniwinkle, $firstName!</h2>
            <p>Thank you for registering. Please verify your email by clicking the link below:</p>
            <p><a href='$verifyLink' style='background:#4e73df;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Verify Email</a></p>
            <p>Or copy this link to your browser:<br>$verifyLink</p>
            <p>This link will expire in 24 hours.</p>
        ";
        $mail->AltBody = "Welcome to Bunniwinkle!\n\nPlease verify your email by visiting this link:\n$verifyLink\n\nThis link expires in 24 hours.";

        $mail->send();
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Please check your email to verify your account.'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Mail Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Registration complete but we could not send verification email. Please contact support.'
        ]);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}