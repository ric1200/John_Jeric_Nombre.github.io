<?php
session_start();
require __DIR__ . '/config/db.php';
if (isset($_SESSION['user_id'])) {
    insertAudit(
        $pdo, 
        'LOGOUT', 
        'users', 
        $_SESSION['user_id'], 
        null, 
        null, 
        $_SESSION['user_id']
    );
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header("Location: /index.php");
exit;
?>