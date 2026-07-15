<?php
// FILE: sysad/delete_user.php
session_start();
require __DIR__ . '/../config/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 1. Fetch user data first for the audit log
        $stmt = $pdo->prepare("SELECT username, status FROM users WHERE user_id = ?");
        $stmt->execute([$id]);
        $targetUser = $stmt->fetch();

        if ($targetUser) {
            // 2. Perform Soft Delete (Set Status to INACTIVE)
            $updateSql = "UPDATE users SET status = 'INACTIVE' WHERE user_id = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$id]);

            // 3. Log the action
            if (function_exists('insertAudit')) {
                insertAudit(
                    $pdo, 
                    'USER_DEACTIVATED', // Action Name
                    'users',            // Table
                    $id,                // Object ID
                    [
                        'previous_status' => $targetUser['status'],
                        'new_status' => 'INACTIVE',
                        'username' => $targetUser['username']
                    ], 
                    $updateSql, 
                    $_SESSION['user_id'] ?? 0
                );
            }
        }
    } catch (Exception $e) {
        // Ideally handle error, for now just redirect
        error_log($e->getMessage());
    }
}

// Redirect back to list
header("Location: manage_users.php");
exit;
?>