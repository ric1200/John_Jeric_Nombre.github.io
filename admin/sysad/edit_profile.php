<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/auth_guard.php';
require __DIR__ . '/../config/db.php';

// Safe User ID check: GET -> SESSION -> Default 1
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($_SESSION['user_id'] ?? 1);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['first_name']);
    $mname = trim($_POST['middle_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $phone_number = trim($_POST['phone_number']);

    try {
        $pdo->beginTransaction();
        $sql_users = "UPDATE users SET first_name = :fn, middle_name = :mn, last_name = :ln, email = :email WHERE user_id = :uid";
        $stmt_users = $pdo->prepare($sql_users);
        $stmt_users->execute([':fn' => $fname, ':mn' => $mname, ':ln' => $lname, ':email' => $email, ':uid' => $user_id]);

        $sql_admin = "INSERT INTO admin_profiles (user_id, department, phone_number) 
                      VALUES (:uid, :dept, :phone)
                      ON DUPLICATE KEY UPDATE department = :dept2, phone_number = :phone2";
        $stmt_admin = $pdo->prepare($sql_admin);
        $stmt_admin->execute([':uid' => $user_id, ':dept' => $department, ':phone' => $phone_number, ':dept2' => $department, ':phone2' => $phone_number]);

        $pdo->commit();
        $message = "<div class='alert success'><i class='fas fa-check-circle'></i> Profile updated successfully!</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Error: " . $e->getMessage() . "</div>";
    }
}

$stmt = $pdo->prepare("SELECT u.*, a.department, a.phone_number FROM users u LEFT JOIN admin_profiles a ON u.user_id = a.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die("Error: User profile not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | UCL Counseling System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/edit_profile.css?v=<?= time(); ?>">
</head>
<body>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Edit System Profile</h1>
                <p>Manage your personal details and contact information.</p>
            </div>

            <?php echo $message; ?>

            <div class="data-card">
                <form action="" method="POST">
                    
                    <div class="section-title"><i class="fas fa-user-circle"></i> Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name</label>
                            <div class="search-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" placeholder="e.g. Juan" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <div class="search-wrapper">
                                <i class="fas fa-fingerprint input-icon"></i>
                                <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($profile['middle_name'] ?? '') ?>" placeholder="e.g. Ramos">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <div class="search-wrapper">
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>" placeholder="e.g. Dela cruz" required>
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="fas fa-address-book"></i> Contact & Department</div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="search-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" placeholder="j.delacruz@gmail.com" required>
                        </div>
                    </div>

                    <div class="form-grid two-cols">
                        <div class="form-group">
                            <label>Department</label>
                            <div class="search-wrapper">
                                <i class="fas fa-building input-icon"></i>
                                <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($profile['department'] ?? '') ?>" placeholder="e.g. CSDL Staff">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="search-wrapper">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($profile['phone_number'] ?? '') ?>" placeholder="09123456789">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-check-circle"></i> Save Changes
                        </button>
                        <a href="profile.php?user_id=<?= $user_id ?>" class="btn btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>