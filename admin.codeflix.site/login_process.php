<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/config/db.php';

if (!empty($_POST['username']) && !empty($_POST['password'])) {

    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE username = ? AND role = 'ADMIN' AND status = 'ACTIVE'
        LIMIT 1
    ");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        
        // --- LOGGING START (SUCCESS) ---
        // 1. Action: LOGIN_SUCCESS
        // 2. Table: users
        // 3. Object ID: The user's ID
        // 4. Changed Data: null (nothing changed)
        // 5. SQL: null
        // 6. User ID: The user's ID (Foreign Key)
        insertAudit(
            $pdo, 
            'LOGIN_SUCCESS', 
            'users', 
            $user['user_id'], 
            null, 
            null, 
            $user['user_id']
        );
        // --- LOGGING END ---

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['division'] = $user['division'];

        header("Location: /sysad/dashboard.php");
        exit;
    } else {
        
        // --- LOGGING START (FAILURE) ---
        // We log the failure, but we pass NULL for user_id because 
        // the user isn't logged in yet.
        insertAudit(
            $pdo, 
            'LOGIN_FAILED', 
            'users', 
            null, 
            ['attempted_username' => $_POST['username']], // Save the username they tried
            null, 
            null
        );
        // --- LOGGING END ---

        die("Unauthorized access. Check your password."); 
    }

} else {
    die("Please enter username and password.");
}
?>