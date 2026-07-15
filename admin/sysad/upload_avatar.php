<?php
// Simulan ang session para makuha ang logged in user kung walang ID sa URL
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/auth_guard.php';
require __DIR__ . '/../config/db.php';

// 1. Kunin ang ID sa GET, kung wala, sa POST, kung wala pa rin, sa SESSION
$user_id = 0;
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
} elseif (isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}

$message = '';

// 2. I-verify muna kung existing ang user sa database bago mag-load
$stmt = $pdo->prepare("SELECT u.first_name, a.avatar_path FROM users u LEFT JOIN admin_profiles a ON u.user_id = a.user_id WHERE u.user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Kung talagang walang nahanap, huwag i-die, i-redirect nalang back to dashboard
if (!$user && $user_id !== 0) {
    header("Location: dashboard.php?error=usernotfound");
    exit();
}

// 3. Handle Upload Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar_file']) && $user_id > 0) {
    $file = $_FILES['avatar_file'];
    $upload_dir = '../uploads/admins/';
    
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'jpg', 'jpeg'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $unique_name = 'admin_' . $user_id . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $unique_name;
            $db_path = '../uploads/admins/' . $unique_name; 

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                try {
                    $sql = "INSERT INTO admin_profiles (user_id, avatar_path) 
                            VALUES (:uid, :path) 
                            ON DUPLICATE KEY UPDATE avatar_path = :path2";
                    $pdo->prepare($sql)->execute([':uid' => $user_id, ':path' => $db_path, ':path2' => $db_path]);
                    
                    header("Location: profile.php?user_id=$user_id&upload=success");
                    exit();
                } catch (PDOException $e) { $message = "DB Error: " . $e->getMessage(); }
            } else { $message = "Folder permission error."; }
        } else { $message = "Invalid file type (JPG/PNG only)."; }
    } else { $message = "Error code: " . $file['error']; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Profile Picture | UCL Counseling System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/css/upload_avatar.css?v=<?= time(); ?>">
</head>
<body>
    
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Update Profile Picture</h1>
            <p>User: <?= htmlspecialchars($user['first_name'] ?? 'Guest') ?> (ID: <?= $user_id ?>)</p>
        </div>
        
        <div class="data-card">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                
                <div class="preview-box">
                    <img id="imgPreview" src="<?= !empty($user['avatar_path']) ? $user['avatar_path'] : '../assets/images/default_admin.png' ?>">
                </div>
                
                <input type="file" name="avatar_file" id="avatarInput" accept="image/*" style="margin-bottom: 20px; width: 100%;" required>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-upload">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="profile.php?user_id=<?= $user_id ?>" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('avatarInput').onchange = function (evt) {
            const [file] = this.files;
            if (file) { 
                document.getElementById('imgPreview').src = URL.createObjectURL(file); 
            }
        }
    </script>
</body>
</html>