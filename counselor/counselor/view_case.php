<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

$case_id = $_GET['id'] ?? null;

if (!$case_id) {
    echo "<div class='content-section'><p style='color:red;'>No case specified.</p></div>";
    exit;
}

// Handle Update
$updateSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_case'])) {
    $title    = $_POST['title'] ?? '';
    $priority = $_POST['priority'] ?? 'Normal';
    $status   = $_POST['status'] ?? 'Pending';

    $updateStmt = $pdo->prepare("UPDATE cases SET title = ?, priority = ?, status = ? WHERE case_id = ?");
    if ($updateStmt->execute([$title, $priority, $status, $case_id])) {
        $updateSuccess = true;
    }
}

/**
 * FETCH CASE DETAILS
 * Inayos ang JOIN condition para basahin ang ID man o username na naka-save sa cases table.
 */
$stmt = $pdo->prepare("
    SELECT 
        c.*, 
        u.user_id, u.username, u.first_name, u.last_name, u.email,
        sp.program, sp.year_level, sp.phone_number, sp.gender, sp.guardian_name, sp.guardian_contact
    FROM cases c
    LEFT JOIN users u ON c.student_user_id = u.username OR c.student_user_id = u.user_id
    LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
    WHERE c.case_id = ?
");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    echo "<div class='content-section'><p style='color:red;'>Case not found.</p></div>";
    exit;
}

// --- Variable Mapping ---
$student_id_display = !empty($case['username']) ? $case['username'] : $case['student_user_id'];
$student_name       = (!empty($case['first_name'])) ? $case['first_name'] . ' ' . $case['last_name'] : 'N/A';
$created_at         = !empty($case['created_at']) ? date("F d, Y - h:i A", strtotime($case['created_at'])) : 'N/A';

// Profile Variables
$program          = !empty($case['program']) ? $case['program'] : 'N/A';
$year_level       = !empty($case['year_level']) ? $case['year_level'] : 'N/A';
$phone_number      = !empty($case['phone_number']) ? $case['phone_number'] : 'N/A';
$gender            = !empty($case['gender']) ? $case['gender'] : 'N/A';
$guardian_name     = !empty($case['guardian_name']) ? $case['guardian_name'] : 'N/A';
$guardian_contact  = !empty($case['guardian_contact']) ? $case['guardian_contact'] : 'N/A';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .view-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .view-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
    .back-link { text-decoration: none; color: #6c757d; font-size: 0.9rem; margin-bottom: 10px; display: inline-block; transition: 0.3s; }
    .back-link:hover { color: #4267b2; }
    
    .action-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #eef2f7; }
    .action-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: flex-end; }
    
    .record-card { background: #fff; padding: 50px; border-radius: 5px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: relative; border-top: 5px solid #4267b2; }
    .record-header { border-bottom: 2px solid #f0f0f0; margin-bottom: 30px; padding-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
    
    .record-logo-wrapper { display: flex; align-items: center; gap: 15px; }
    .logo-circle { width: 50px; height: 50px; object-fit: contain; }
    .record-logo-text { font-weight: 800; font-size: 1.2rem; color: #1a1a1a; }
    
    .info-section { margin-bottom: 25px; }
    .info-label { font-size: 0.75rem; color: #8c92a0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; margin-bottom: 5px; }
    .info-value { font-size: 1rem; color: #2d3436; font-weight: 500; }
    
    .section-divider { border-bottom: 1px solid #eaeaea; padding-bottom: 10px; margin-top: 35px; margin-bottom: 20px; font-size: 1.1rem; color: #4267b2; font-weight: 700; }
    
    .description-box { background: #fcfcfc; padding: 20px; border-left: 4px solid #dee2e6; margin-top: 10px; min-height: 150px; line-height: 1.8; white-space: pre-wrap; color: #444; }
    
    .btn-update { background: #4267b2; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
    .btn-update:hover { background: #365899; }
    .btn-print { background: #f8f9fa; color: #333; border: 1px solid #dee2e6; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .record-badge { padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }

    /* PRINT SETTINGS */
    @media print {
        .action-card, .back-link, .top-nav, .btn-print, .btn-update, .view-header { 
            display: none !important; 
        }
        body { background: white; margin: 0; padding: 0; }
        .view-container { margin: 0; width: 100%; max-width: 100%; padding: 0; }
        .record-card { box-shadow: none; border: 1px solid #eee; padding: 30px; margin: 0; }
    }
</style>

<div class="view-container">
    <div class="view-header">
        <div>
            <h1 style="font-weight: 800; font-size: 2rem; margin:0;">Case Record</h1>
            <p style="color: #8c92a0; margin-top: 5px;">Official counseling documentation for CAS-<?php echo htmlspecialchars($case['case_id']); ?></p>
        </div>
        <div>
            <a href="cases.php" class="back-link" style="display: block; text-align: right;"><i class="fas fa-arrow-left"></i> Back to Directory</a>
            <button onclick="window.print();" class="btn-print"><i class="fas fa-print"></i> Print Report</button>
        </div>
    </div>

    <div class="action-card">
        <h3 style="font-size: 1rem; margin-bottom: 15px; color: #4a4a4a;"><i class="fas fa-edit"></i> Quick Update</h3>
        <?php if ($updateSuccess): ?>
            <div style="background: #eafaf1; color: #2ecc71; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem;">
                <i class="fas fa-check-circle"></i> Case updated successfully!
            </div>
        <?php endif; ?>

        <form method="POST" class="action-grid">
            <div class="form-group">
                <label class="info-label">Case Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($case['title']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
            </div>
            <div class="form-group">
                <label class="info-label">Priority</label>
                <select name="priority" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    <option value="Normal" <?php echo ($case['priority']=='Normal')?'selected':''; ?>>Normal</option>
                    <option value="Medium" <?php echo ($case['priority']=='Medium')?'selected':''; ?>>Medium</option>
                    <option value="High" <?php echo ($case['priority']=='High')?'selected':''; ?>>High</option>
                    <option value="Critical" <?php echo ($case['priority']=='Critical')?'selected':''; ?>>Critical</option>
                </select>
            </div>
            <div class="form-group">
                <label class="info-label">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    <option value="Pending" <?php echo ($case['status']=='Pending')?'selected':''; ?>>Pending</option>
                    <option value="In Progress" <?php echo ($case['status']=='In Progress')?'selected':''; ?>>In Progress</option>
                    <option value="Resolved" <?php echo ($case['status']=='Resolved')?'selected':''; ?>>Resolved</option>
                    <option value="Closed" <?php echo ($case['status']=='Closed')?'selected':''; ?>>Closed</option>
                </select>
            </div>
            <button type="submit" name="update_case" class="btn-update">Update</button>
        </form>
    </div>

    <div class="record-card">
        <div class="record-header">
            <div class="record-logo-wrapper">
                <img src="../assets/image/union_logo1.png" alt="Logo" class="logo-circle" onerror="this.style.display='none'">
                <div class="record-logo-text">PHINMA UCL COUNSELING</div>
            </div>
            <div style="text-align: right;">
                <div class="info-label">Document ID</div>
                <div style="font-weight: 700; color: #e74c3c;">#CAS-<?php echo htmlspecialchars($case['case_id']); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div class="info-section">
                <div class="info-label">Student Name</div>
                <div class="info-value" style="font-size: 1.2rem; font-weight: 700;"><?php echo htmlspecialchars($student_name); ?></div>
                <div style="font-size: 0.85rem; color: #8c92a0;"><?php echo htmlspecialchars($case['email'] ?? 'No email provided'); ?></div>
            </div>
            <div class="info-section">
                <div class="info-label">Student ID (Username)</div>
                <div class="info-value" style="font-size: 1.2rem; font-weight: 700; color: #4267b2;"><?php echo htmlspecialchars($student_id_display); ?></div>
            </div>
        </div>

        <h4 class="section-divider"><i class="fas fa-user-graduate"></i> Student Profile Information</h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="info-section">
                <div class="info-label">Program & Year</div>
                <div class="info-value"><?php echo htmlspecialchars($program) . ' - ' . htmlspecialchars($year_level); ?></div>
            </div>
            <div class="info-section">
                <div class="info-label">Gender</div>
                <div class="info-value"><?php echo htmlspecialchars($gender); ?></div>
            </div>
            <div class="info-section">
                <div class="info-label">Contact Number</div>
                <div class="info-value"><?php echo htmlspecialchars($phone_number); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="info-section" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div class="info-label">Emergency Guardian Name</div>
                <div class="info-value"><?php echo htmlspecialchars($guardian_name); ?></div>
            </div>
            <div class="info-section" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div class="info-label">Guardian Contact Info</div>
                <div class="info-value"><?php echo htmlspecialchars($guardian_contact); ?></div>
            </div>
        </div>

        <h4 class="section-divider"><i class="fas fa-folder-open"></i> Case Details</h4>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="info-section">
                <div class="info-label">Date Created</div>
                <div class="info-value" style="font-size: 0.9rem;"><?php echo $created_at; ?></div>
            </div>
            <div class="info-section">
                <div class="info-label">Priority Level</div>
                <div><span class="record-badge" style="background: #f8f9fa; border: 1px solid #ddd;"><?php echo htmlspecialchars($case['priority']); ?></span></div>
            </div>
            <div class="info-section">
                <div class="info-label">Status</div>
                <div><span class="record-badge" style="background: #4267b2; color: white;"><?php echo htmlspecialchars($case['status']); ?></span></div>
            </div>
        </div>

        <div class="info-section" style="margin-top: 10px;">
            <div class="info-label">Case Title / Subject</div>
            <div class="info-value" style="font-size: 1.2rem; font-weight: 700;"><?php echo htmlspecialchars($case['title']); ?></div>
        </div>

        <div class="info-section">
            <div class="info-label">Case Description & Findings</div>
            <div class="description-box"><?php echo nl2br(htmlspecialchars($case['description'])); ?></div>
        </div>

        <div style="margin-top: 60px; border-top: 1px dashed #dee2e6; padding-top: 20px; display: flex; justify-content: space-between;">
            <div style="font-size: 0.8rem; color: #8c92a0;">
                Generated by Counselor Dashboard System<br>
                Date Printed: <?php echo date("M d, Y"); ?>
            </div>
            <div style="text-align: center; border-top: 1px solid #333; width: 200px; padding-top: 5px; font-weight: 600; font-size: 0.9rem;">
                Counselor Signature
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>