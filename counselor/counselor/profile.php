<?php 
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Siguraduhing tama ang path ng db.php at header.php
require_once __DIR__ . '/../config/db.php'; 
include __DIR__ . '/../includes/header.php'; 

// 2. I-verify ang session
$session_user_id = $_SESSION['user_id'] ?? null; 

if (!$session_user_id) {
    echo "<div style='text-align:center; padding:50px; font-family:Inter,sans-serif;'>
            <h2>Access Denied</h2>
            <p>Please log in to view your profile.</p>
          </div>";
    exit;
}

try {
    // Tukuyin kung $pdo o $conn ang gamit sa db.php
    $db = isset($pdo) ? $pdo : $conn;

    // UPDATED SQL: Ginamit ang u.user_id base sa iyong screenshot
    $sql = "SELECT p.*, u.role, u.status, u.first_name, u.last_name, u.email
            FROM counselor_profiles p 
            JOIN users u ON p.user_id = u.user_id 
            WHERE p.user_id = :uid LIMIT 1"; 
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $session_user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Data mula sa counselor_profiles
        $specialization = htmlspecialchars($row['specialization'] ?? 'N/A');
        $office = htmlspecialchars($row['office_location'] ?? 'N/A');
        $phone = htmlspecialchars($row['phone_number'] ?? 'N/A');
        $address = htmlspecialchars($row['address'] ?? 'N/A');
        $city = htmlspecialchars($row['city'] ?? 'N/A');
        $zip = htmlspecialchars($row['zip_code'] ?? 'N/A');
        $gender = htmlspecialchars($row['gender'] ?? 'Not set');
        $bday = ($row['birth_date'] && $row['birth_date'] != '0000-00-00') 
                ? date("F d, Y", strtotime($row['birth_date'])) 
                : 'Not provided';
        
        // Avatar logic gamit ang avatar_path mula sa DB
        $avatarName = !empty($row['avatar_path']) ? $row['avatar_path'] : 'default_counselor.png';
        $avatarPath = "../uploads/avatars/" . $avatarName; 

        // Data mula sa users table
        $fullName = htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $email = htmlspecialchars($row['email'] ?? 'No email found');
        $accountStatus = $row['status'] ?? 'INACTIVE';
        $role = strtoupper($row['role'] ?? 'COUNSELOR');
    } else {
        die("<div class='content-section' style='text-align:center; padding:100px;'>
                <i class='fas fa-user-slash' style='font-size:50px; color:#ccc;'></i>
                <h3>Profile record not found.</h3>
                <p>Please ensure your counselor profile is initialized in the database.</p>
             </div>");
    }

} catch (PDOException $e) {
    die("<div style='color:red; padding:20px; border:1px solid red; margin:20px; font-family:sans-serif;'>
            <strong>Database Error:</strong> " . $e->getMessage() . "
         </div>");
}
?>

<style>
    .content-section { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .header-title h1 { font-weight: 800; margin: 0; color: #333; }
    .header-title p { color: #8c92a0; margin-bottom: 30px; }
    
    .profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start; }
    .form-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eef2f7; }
    
    .profile-sidebar { text-align: center; }
    .profile-avatar { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #f0f2f5; margin-bottom: 15px; background: #eee; }
    .profile-name { font-size: 1.25rem; font-weight: 700; color: #333; margin-bottom: 10px; }
    
    .badge-status { font-size: 0.7rem; padding: 4px 10px; border-radius: 12px; font-weight: 600; display: inline-block; margin-bottom: 5px; text-transform: uppercase; }
    .status-active, .status-ACTIVE { background: #eafaf1; color: #2ecc71; }
    .status-inactive, .status-INACTIVE { background: #fff0f0; color: #e74c3c; }
    .role-label { background: #eef2f7; color: #4267b2; text-transform: uppercase; font-size: 0.65rem; padding: 4px 10px; border-radius: 12px; font-weight: 700; }

    .info-section { margin-bottom: 30px; }
    .info-section h3 { font-size: 1rem; color: #4267b2; border-bottom: 1px solid #eef2f7; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
    .detail-item label { display: block; font-size: 0.75rem; color: #8c92a0; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .detail-item p { font-size: 0.95rem; color: #333; font-weight: 500; margin: 0; }

    .btn-primary { background: #4267b2; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; width: 100%; margin-top: 20px; text-decoration: none; display: inline-block; text-align: center; }
    .btn-primary:hover { background: #365899; }
</style>

<div class="content-section">
    <div class="header-title">
        <h1>My Profile</h1>
        <p>Personal and professional information management</p>
    </div>

    <div class="profile-grid">
        <div class="form-card profile-sidebar">
            <img src="<?= $avatarPath ?>" alt="Avatar" class="profile-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=random'">
            <div class="profile-name"><?= $fullName ?></div>
            
            <div style="margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; align-items: center;">
                <span class="role-label"><?= $role ?></span>
                <span class="badge-status status-<?= $accountStatus ?>">
                    ● <?= $accountStatus ?>
                </span>
            </div>

            <p style="font-size: 0.85rem; color: #666;"><?= $specialization ?></p>
            
            <a href="edit_profile.php" class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</a>
        </div>

        <div class="form-card">
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Contact & Personal</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Email Address</label>
                        <p><?= $email ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?= $phone ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Birth Date</label>
                        <p><?= $bday ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Gender</label>
                        <p><?= $gender ?></p>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-map-marker-alt"></i> Location Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>City</label>
                        <p><?= $city ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Zip Code</label>
                        <p><?= $zip ?></p>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <label>Full Address</label>
                        <p><?= $address ?></p>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-briefcase"></i> Professional Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Office Location</label>
                        <p><?= $office ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Specialization</label>
                        <p><?= $specialization ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>