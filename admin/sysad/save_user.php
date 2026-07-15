<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Correct paths */
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/auth_guard.php';
require __DIR__ . '/../config/role_guard.php';
require_once '/home/code2025/public_html/shared/config/mail_config.php';

/* Validate POST data */
if (
    empty($_POST['first_name']) ||
    empty($_POST['middle_name']) ||
    empty($_POST['last_name']) ||
    empty($_POST['email']) ||
    empty($_POST['username']) ||
    empty($_POST['division']) ||
    empty($_POST['role'])
) {
    die('Missing required fields');
}

/*  Insert user (NO password yet) */
$stmt = $pdo->prepare("
    INSERT INTO users
    (first_name, middle_name, last_name, email, username, division, role, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
");

$stmt->execute([
    $_POST['first_name'],
    $_POST['middle_name'],
    $_POST['last_name'],
    $_POST['email'],
    $_POST['username'],
    $_POST['division'],
    $_POST['role']
]);

$user_id = $pdo->lastInsertId();

/* Generate secure password setup token */
$token   = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

/* Store token */
$stmt = $pdo->prepare("
    INSERT INTO password_resets (user_id, token, expires_at)
    VALUES (?, ?, ?)
");
$stmt->execute([$user_id, $token, $expires]);

/*  Build password setup link */
$setup_link = "https://admin.codeflix.site/set_password.php?token=" . $token;

/* Send email to user */
$subject = "Counseling Account Password Setup";

$message = "
<p>Good day,</p>

<p>An account has been created for you in the <strong>Counseling Online System</strong>.</p>

<p><strong>Username:</strong> {$_POST['username']}</p>
<p><strong>Division:</strong> {$_POST['division']}</p>

<p>Please click the link below to set your password:</p>

<p>
  <a href='{$setup_link}'>Set Your Password</a>
</p>

<p>This link will expire in <strong>1 hour</strong>.</p>

<p>If you did not request this account, please ignore this email.</p>

<p>- Counseling System Administrator</p>
";

sendSystemEmail($_POST['email'], $subject, $message);

header("Location: create_user.php? success=1");
exit;
