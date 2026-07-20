<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
session_start();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';
$db = isset($pdo) ? $pdo : $conn;
$c_id = $_SESSION['user_id'] ?? null;

if (!$c_id) die("Access Denied.");

// In-update ang query para makuha ang email at profile info kung kailangan
$schedules = $db->prepare("SELECT s.*, u.first_name, u.last_name, u.email 
                           FROM counseling_schedules s 
                           JOIN users u ON s.student_user_id = u.user_id 
                           WHERE s.counselor_id = ? 
                           ORDER BY s.schedule_date DESC, s.schedule_time DESC");
$schedules->execute([$c_id]);
$list = $schedules->fetchAll();
?>

<style>
    :root { --primary: #4267b2; --success: #2ecc71; --warning: #f1c40f; --danger: #e74c3c; --text: #333; }
    .content-section { max-width: 1100px; margin: 40px auto; font-family: 'Inter', sans-serif; color: var(--text); }
    
    /* Table Styling */
    .schedule-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #eee; }
    .styled-table { width: 100%; border-collapse: collapse; }
    .styled-table thead tr { background-color: #f8f9fa; color: #888; text-align: left; font-size: 0.85rem; text-transform: uppercase; }
    .styled-table th, .styled-table td { padding: 18px 20px; border-bottom: 1px solid #f2f2f2; }
    .styled-table tbody tr:hover { background-color: #fcfdfe; }

    /* Badge & Select */
    .status-select { padding: 6px 10px; border-radius: 8px; border: 1px solid #ddd; font-size: 0.85rem; outline: none; cursor: pointer; }
    .btn-print { background: #f0f2f5; color: var(--primary); border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
    .btn-print:hover { background: var(--primary); color: white; }

    /* --- PRINT TICKET STYLES (Hidden on screen) --- */
    #print-area { display: none; }
    @media print {
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area { 
            display: block !important; 
            position: absolute; left: 0; top: 0; width: 100%; 
            padding: 40px; font-family: 'Courier New', Courier, monospace;
        }
        .ticket { 
            border: 2px dashed #000; padding: 30px; max-width: 500px; margin: auto;
            text-align: center; line-height: 1.6;
        }
        .ticket h2 { margin-bottom: 5px; text-transform: uppercase; }
        .ticket-info { text-align: left; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        .ticket-footer { margin-top: 30px; font-size: 0.8rem; font-style: italic; }
    }
    
</style>

<div class="content-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin:0; font-weight: 800;">Appointment Records</h2>
            <p style="color: #888; margin: 5px 0 0 0;">Manage and track student counseling sessions.</p>
        </div>
        <button onclick="window.location.href='messages.php'" class="btn-print" style="background: var(--primary); color:white;">+ New Schedule</button>
    </div>

    <div class="schedule-card">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Date & Time</th>
                    <th>Current Status</th>
                    <th>Notes / Topic</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 50px; color: #ccc;">No schedules found.</td></tr>
                <?php endif; ?>

                <?php foreach ($list as $row): ?>
                <tr>
                    <td>
                        <div style="font-weight: 700;"><?= htmlspecialchars($row['first_name'] . " " . $row['last_name']) ?></div>
                        <div style="font-size: 0.75rem; color: #999;"><?= htmlspecialchars($row['email']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #444;"><?= date("M d, Y", strtotime($row['schedule_date'])) ?></div>
                        <div style="font-size: 0.85rem; color: #777;"><?= date("h:i A", strtotime($row['schedule_time'])) ?></div>
                    </td>
                    <td>
                        <form action="update_status.php" method="POST" style="margin:0;">
                            <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                            <select name="status" onchange="this.form.submit()" class="status-select">
                                <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Confirmed" <?= $row['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $row['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </form>
                    </td>
                    <td style="max-width: 200px;">
                        <div style="font-size: 0.85rem; color: #666; font-style: italic;">
                            <?= htmlspecialchars($row['notes'] ?: 'No notes provided') ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <button class="btn-print" onclick="printTicket(<?= htmlspecialchars(json_encode($row)) ?>)">
                            🖨️ Print Ticket
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="print-area">
    <div class="ticket">
        <h2>Counseling Slip</h2>
        <p>Official Appointment Ticket</p>
        <div class="ticket-info">
            <p><strong>Student:</strong> <span id="p-name"></span></p>
            <p><strong>Date:</strong> <span id="p-date"></span></p>
            <p><strong>Time:</strong> <span id="p-time"></span></p>
            <p><strong>Topic:</strong> <span id="p-notes"></span></p>
            <p><strong>Status:</strong> <span id="p-status"></span></p>
        </div>
        <div class="ticket-footer">
            <p>Please arrive 5 minutes before your schedule.</p>
            <p>Generated on: <?= date("Y-m-d H:i") ?></p>
        </div>
    </div>
</div>

<script>
function printTicket(data) {
    // Fill the hidden ticket with data
    document.getElementById('p-name').innerText = data.first_name + " " + data.last_name;
    document.getElementById('p-date').innerText = data.schedule_date;
    document.getElementById('p-time').innerText = data.schedule_time;
    document.getElementById('p-notes').innerText = data.notes || 'General Inquiry';
    document.getElementById('p-status').innerText = data.status;

    // Trigger Print
    window.print();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>