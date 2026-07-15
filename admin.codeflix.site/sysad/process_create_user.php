<?php
require_once __DIR__ . '/../shared/config/db.php';
require_once __DIR__ . '/../shared/config/mail_config.php';

/* Insert user WITHOUT password */
$stmt = $pdo->prepare("
  INSERT INTO users
  (first_name, middle_name, last_name, email, username, password_hash, division, role, status)
  VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 'ACTIVE')
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

/* Build setup link */
$setup_link = "https://admin.codeflix.site/set_password.php?token=" . $token;

/*  Redirect */
header('Location: users.php?created=1');
exit;
