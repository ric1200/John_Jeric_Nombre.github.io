<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ob_start(); 
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$session_user_id = $_SESSION['user_id'] ?? null;
$db = isset($pdo) ? $pdo : $conn;

if (!$session_user_id) {
    die("Access Denied.");
}

// --- UPDATE LOGIC (Dapat nasa taas ng kahit anong HTML) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $specialization = $_POST['specialization'];
    $office = $_POST['office_location'];
    $phone = $_POST['phone_number'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $zip = $_POST['zip_code'];
    $gender = $_POST['gender'];
    $bday = $_POST['birth_date'];

    try {
        $avatar_sql = "";
        $params = [
            ':spec' => $specialization,
            ':off' => $office,
            ':ph' => $phone,
            ':addr' => $address,
            ':cty' => $city,
            ':zip' => $zip,
            ':gen' => $gender,
            ':bd' => $bday,
            ':uid' => $session_user_id
        ];

        if (!empty($_FILES['avatar']['name'])) {
            $target_dir = "../uploads/avatars/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
            $new_file_name = "avatar_" . $session_user_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_sql = ", avatar_path = :avatar";
                $params[':avatar'] = $new_file_name;
            }
        }

        $sql = "UPDATE counselor_profiles SET 
                specialization = :spec, 
                office_location = :off, 
                phone_number = :ph, 
                address = :addr, 
                city = :cty, 
                zip_code = :zip, 
                gender = :gen, 
                birth_date = :bd 
                $avatar_sql
                WHERE user_id = :uid";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // REKTA SA PROFILE: Dito ang magic.
        header("Location: profile.php?status=updated");
        exit(); 
        
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// Dito na pwedeng mag-include ng header/HTML
include __DIR__ . '/../includes/header.php';

// --- FETCH CURRENT DATA ---
$stmt = $db->prepare("SELECT p.*, u.first_name, u.last_name FROM counselor_profiles p JOIN users u ON p.user_id = u.user_id WHERE p.user_id = ?");
$stmt->execute([$session_user_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .edit-container { max-width: 800px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .form-card { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eef2f7; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #666; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .btn-save { background: #4267b2; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 1rem; }
    .danger-alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #fff0f0; color: #e74c3c; border: 1px solid #e74c3c; }
    .avatar-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid #4267b2; }
</style>

<div class="edit-container">
    <h2>Edit Counselor Profile</h2>
    
    <?php if(isset($error_msg)): ?>
        <div class="danger-alert"><?= $error_msg ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="form-card">
        
        <div class="form-group" style="text-align: center;">
            <label>Profile Picture</label>
            <?php 
                $avatar = !empty($data['avatar_path']) ? $data['avatar_path'] : 'default_counselor.png';
            ?>
            <img src="../uploads/avatars/<?= $avatar ?>" class="avatar-preview" id="preview">
            <input type="file" name="avatar" class="form-control" onchange="previewImage(this)">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Specialization</label>
                <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($data['specialization'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Office Location</label>
                <input type="text" name="office_location" class="form-control" value="<?= htmlspecialchars($data['office_location'] ?? '') ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($data['phone_number'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Birth Date</label>
                <input type="date" name="birth_date" class="form-control" value="<?= $data['birth_date'] ?? '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
                <option value="Male" <?= ($data['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= ($data['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= ($data['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Full Address</label>
            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($data['address'] ?? '') ?></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($data['city'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Zip Code</label>
                <input type="text" name="zip_code" class="form-control" value="<?= htmlspecialchars($data['zip_code'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
        <a href="profile.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Cancel</a>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>