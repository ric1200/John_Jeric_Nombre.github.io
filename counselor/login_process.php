<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_POST['username']) || empty($_POST['password'])) {
    header('Location: index.php?error=empty');
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $pdo->prepare("
    SELECT user_id, role, password_hash
    FROM users
    WHERE username = ?
      AND division = 'COUNSELOR'
      AND role != 'ADMIN' 
      AND status = 'ACTIVE'
    LIMIT 1
");

$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {

    session_regenerate_id(true);

    $_SESSION['user_id']  = $user['user_id'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['division'] = 'COUNSELOR';

    header('Location: counselor/dashboard.php');
    exit;
}

// ❌ invalid login
header('Location: index.php?error=invalid');
exit;
?>