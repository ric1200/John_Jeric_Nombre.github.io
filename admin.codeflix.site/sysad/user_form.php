<?php
require_once __DIR__ . '/../config/auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// FILE: sysad/user_form.php
// FIXED: Ginawang safe check para hindi mag-error kung may session na
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
$user = null;
$error = '';

// --- 1. FETCH USER IF EDITING ---
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $division = $_POST['division'];
    $role = $_POST['role'];
    $status = $_POST['status'] ?? 'ACTIVE';

    try {
        if ($id) {
            // UPDATE
            $sql = "UPDATE users SET 
                    first_name=?, middle_name=?, last_name=?, email=?, 
                    username=?, division=?, role=?, status=? 
                    WHERE user_id=?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $username,
                $division,
                $role,
                $status,
                $id
            ]);

            // Audit
            if (function_exists('insertAudit')) {
                insertAudit($pdo, 'USER_UPDATE', 'users', $id, $_POST, null, $_SESSION['user_id'] ?? 0);
            }

        } else {
            // INSERT
            $sql = "INSERT INTO users 
                    (first_name, middle_name, last_name, email, username, division, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $username,
                $division,
                $role,
                'ACTIVE'
            ]);

            $newId = $pdo->lastInsertId();

            // Audit
            if (function_exists('insertAudit')) {
                insertAudit($pdo, 'USER_CREATE', 'users', $newId, $_POST, null, $_SESSION['user_id'] ?? 0);
            }
        }

        header("Location: manage_users.php");
        exit;

    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $user ? 'Edit' : 'Add' ?> User | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../assets/css/user_form.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container form-container">
        
        <div class="page-header">
            <a href="manage_users.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Users</a>
            <h1><?= $user ? 'Edit User Profile' : 'Add New User' ?></h1>
            <p><?= $user ? 'Update the details and permissions of this account.' : 'Fill in the details below to create a new system account.' ?></p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="post">
                <h3 class="section-title"><i class="far fa-id-card"></i> Personal Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required placeholder="e.g. Juan">
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required placeholder="e.g. Dela Cruz">
                    </div>
                </div>

                <hr class="divider">

                <h3 class="section-title"><i class="fas fa-shield-alt"></i> Account Details</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required placeholder="user.ucl@phinmaed.com">
                    </div>
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required placeholder="Choose a unique username">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>System Role <span class="required">*</span></label>
                        <select name="role" required>
                            <?php 
                            $roles = [
                                'ADMIN' => 'Admin', 
                                'COUNSELOR' => 'Counselor', 
                                'STUDENT' => 'Student'
                                
                            ];
                            foreach ($roles as $db_val => $display_text) {
                                $selected = ($user['role'] ?? '') === $db_val ? 'selected' : '';
                                echo "<option value='$db_val' $selected>$display_text</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Division <span class="required">*</span></label>
                        <select name="division" required>
                            <?php 
                            $divisions = [
                                'ADMIN' => 'System Admin', 
                                'COUNSELOR' => 'Counselor', 
                                'STUDENT' => 'Student', 
                                'REPORT' => 'Report'
                            ];
                            foreach ($divisions as $db_val => $display_text) {
                                $selected = ($user['division'] ?? '') === $db_val ? 'selected' : '';
                                echo "<option value='$db_val' $selected>$display_text</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <?php if($user): ?>
                <div class="form-group status-group">
                    <label>Account Status</label>
                    <select name="status">
                        <option value="ACTIVE" <?= ($user['status'] ?? '') == 'ACTIVE' ? 'selected' : '' ?>>Active (Can Login)</option>
                        <option value="INACTIVE" <?= ($user['status'] ?? '') == 'INACTIVE' ? 'selected' : '' ?>>Inactive (Disabled)</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="manage_users.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $user ? 'Update User Account' : 'Create User Account' ?>
                    </button>
                </div>
            </form>
        </div>
        
    </div>
</div>

</body>
</html>