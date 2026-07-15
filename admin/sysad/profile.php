<?php
require_once __DIR__ . '/../config/auth_guard.php';
require __DIR__ . '/../config/db.php';

// Mas maganda kung galing sa Session ang ID para hindi napapakialaman sa URL
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($_SESSION['user_id'] ?? 1);

try {
    $sql = "SELECT u.user_id, u.first_name, u.middle_name, u.last_name, u.email, 
                   a.department, a.admin_level, a.phone_number, a.avatar_path 
            FROM users u
            LEFT JOIN admin_profiles a ON u.user_id = a.user_id
            WHERE u.user_id = :uid";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) { die("User not found."); }

    $avatar_image = !empty($profile['avatar_path']) ? $profile['avatar_path'] : '../assets/images/default_admin.png';
    
    $fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
    if (empty($fullName)) { $fullName = 'Unknown Admin'; }

} catch (PDOException $e) { die("Database error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | UCL Counseling System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/profile.css?v=<?= time(); ?>"> 
</head>
<body>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            
            <div class="page-header">
                <h1>Administrator Profile</h1>
                <p class="subtitle">Personal information and account settings.</p>
            </div>

            <div class="profile-grid">
                <div class="data-card avatar-section">
                    <div class="avatar-container">
                        <img src="<?= htmlspecialchars($avatar_image) ?>" alt="Admin Avatar" class="profile-avatar">
                        <a href="upload_avatar.php?user_id=<?= $user_id ?>" class="btn-avatar-edit" title="Change Photo">
                            <i class="fas fa-camera"></i>
                        </a>
                    </div>
                    <h2 class="profile-name"><?= htmlspecialchars($fullName) ?></h2>
                    <span class="badge-role"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($profile['admin_level'] ?? 'System Admin') ?></span>
                    
                    <a href="edit_profile.php?user_id=<?= $user_id ?>" class="btn-edit-profile">
                        <i class="fas fa-user-edit"></i> Edit Profile Details
                    </a>
                </div>

                <div class="data-card">
                    <div class="section-title"><i class="fas fa-info-circle"></i> Account Information</div>
                    
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value"><?= htmlspecialchars($profile['email'] ?? 'N/A') ?></span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-building"></i></div>
                        <div>
                            <span class="detail-label">Department</span>
                            <span class="detail-value"><?= htmlspecialchars($profile['department'] ?? 'Not Specified') ?></span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <span class="detail-label">Phone Number</span>
                            <span class="detail-value"><?= htmlspecialchars($profile['phone_number'] ?? 'Not Specified') ?></span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-key"></i></div>
                        <div>
                            <span class="detail-label">Account ID</span>
                            <span class="detail-value" style="font-family: monospace;">#USR-<?= str_pad($user_id, 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>