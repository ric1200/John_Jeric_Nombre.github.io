<?php 
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php'; 
include __DIR__ . '/../includes/header.php'; 

$counselor_id = $_SESSION['user_id'] ?? null; 
$db = isset($pdo) ? $pdo : $conn;

if (!$counselor_id) {
    echo "<div style='text-align:center; padding:50px;'><h2>Access Denied</h2><p>Please log in as a counselor.</p></div>";
    exit;
}

// BAGONG CODE: Idinagdag ang broadcast check
$is_broadcast = isset($_GET['broadcast']) && $_GET['broadcast'] == '1';
$active_student_id = $_GET['student_id'] ?? null;
$active_student_name = "Select a Student";

if ($is_broadcast) {
    $active_student_name = "All Students (Broadcast)";
    $active_student_id = null; // Set to null para hindi mag-trigger ang normal one-on-one chat logic
} elseif ($active_student_id) {
    // 1. Kunin ang pangalan ng student
    $stmtName = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1");
    $stmtName->execute([$active_student_id]);
    $resName = $stmtName->fetch();
    if ($resName) {
        $active_student_name = $resName['first_name'] . " " . $resName['last_name'];
    }

    // 2. I-update ang status maging "read" AT lagyan ng timestamp ang "read_at"
    try {
        $updateStatus = $db->prepare("UPDATE messages SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE sender_id = ? AND recipient_id = ? AND status = 'unread'");
        $updateStatus->execute([$active_student_id, $counselor_id]);
    } catch (PDOException $e) {
        error_log("Failed to update message status: " . $e->getMessage());
    }
}
?>

<style>
    .content-section { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .header-title h1 { font-weight: 800; margin: 0; color: #333; }
    .header-title p { color: #8c92a0; margin-bottom: 30px; }
    .messaging-grid { display: grid; grid-template-columns: 320px 1fr 300px; gap: 20px; align-items: start; height: 700px; }
    .form-card { background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eef2f7; display: flex; flex-direction: column; overflow: hidden; height: 100%; }
    .message-list { overflow-y: auto; padding: 15px; flex-grow: 1; }
    .message-item { padding: 15px; border-bottom: 1px solid #eef2f7; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: 0.2s; text-decoration: none; display: block; color: inherit; }
    .message-item:hover { background: #f8f9fa; }
    .message-item.active { background: #eaf0f9; border-left: 4px solid #4267b2; }
    .message-item h4 { margin: 0; font-size: 0.95rem; color: #333; }
    .message-item p { margin: 5px 0; font-size: 0.85rem; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chat-header { padding: 20px; border-bottom: 1px solid #eef2f7; background: #fff; }
    .chat-history { flex-grow: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; background: #fcfdfe; }
    .chat-bubble { max-width: 75%; padding: 12px 18px; border-radius: 20px; font-size: 0.95rem; position: relative; line-height: 1.4; }
    .chat-bubble.sent { background: #4267b2; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
    .chat-bubble.received { background: #f0f2f5; color: #333; align-self: flex-start; border-bottom-left-radius: 4px; }
    .chat-input { padding: 15px; border-top: 1px solid #eef2f7; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; box-sizing: border-box; margin-bottom: 10px; }
    .btn-primary { background: #4267b2; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; }
    .btn-send { border-radius: 50%; width: 45px; height: 45px; padding: 0; display: flex; align-items: center; justify-content: center; }
    .time-divider { text-align: center; font-size: 0.75rem; color: #8c92a0; margin: 10px 0; font-weight: 500; }
    
    /* BAGONG CSS PARA SA BROADCAST BUTTON */
    .broadcast-item { background: #fff8e1; border-left: 4px solid #f39c12; }
    .broadcast-item:hover { background: #ffecb3; }
    .broadcast-item.active { background: #ffecb3; border-left: 4px solid #d35400; }
</style>

<div class="content-section">
    <div class="header-title">
        <h1>Messages & Scheduling</h1>
        <p>Communicate with students and arrange counseling appointments</p>
    </div>

    <div class="messaging-grid">
        <div class="form-card">
            <div style="padding: 20px; border-bottom: 1px solid #eee;"><strong>Inbox</strong></div>
            <div class="message-list">
                <?php
                // BAGONG CODE: Pinned Broadcast Button sa taas ng list
                $broadcast_active = $is_broadcast ? 'active' : '';
                echo "<a href='messages.php?broadcast=1' class='message-item broadcast-item {$broadcast_active}'>
                        <h4>📢 Announce to All</h4>
                        <p style='color: #d35400; font-size: 0.75rem; font-weight: 600;'>Send a broadcast message</p>
                      </a>";

                // EXISTING CODE: Normal Inbox
                try {
                    $inbox_sql = "SELECT m.*, u.first_name, u.last_name FROM messages m
                                  INNER JOIN (SELECT sender_id, MAX(created_at) AS MaxTime FROM messages WHERE recipient_id = :c_id1 GROUP BY sender_id) 
                                  grouped_m ON m.sender_id = grouped_m.sender_id AND m.created_at = grouped_m.MaxTime
                                  JOIN users u ON m.sender_id = u.user_id WHERE m.recipient_id = :c_id2 ORDER BY m.created_at DESC";
                    $inboxStmt = $db->prepare($inbox_sql);
                    $inboxStmt->execute([':c_id1' => $counselor_id, ':c_id2' => $counselor_id]);
                    
                    while ($msg = $inboxStmt->fetch()) {
                        $is_active = ($active_student_id == $msg['sender_id']) ? 'active' : '';
                        $full_name = $msg['first_name'] . ' ' . $msg['last_name'];
                        $agenda = !empty($msg['agenda']) ? $msg['agenda'] : 'No Agenda';

                        echo "<a href='messages.php?student_id={$msg['sender_id']}' class='message-item {$is_active}'>
                                <h4>".htmlspecialchars($full_name)."</h4>
                                <div style='font-size: 0.75rem; color: #4267b2; font-weight: 600; margin: 4px 0;'>[" . htmlspecialchars($agenda) . "]</div>
                                <p>".htmlspecialchars($msg['content'])."</p>
                              </a>";
                    }
                } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
                ?>
            </div>
            <div style="padding: 15px; border-top: 1px solid #eee; text-align: center; background: #fafbfc;">
                <a href="all_messages.php" style="color:#4267b2; font-size:0.85rem; text-decoration:none; font-weight: 600;">View All Inbox &rarr;</a>
            </div>
        </div>

        <div class="form-card">
            <?php if ($is_broadcast): ?>
                <div class="chat-header">
                    <h3 style="color:#d35400; margin:0;">📢 Broadcast Announcement</h3>
                    <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #666;">This message will be sent to all active students.</p>
                </div>
                <div style="padding: 20px; flex-grow: 1; overflow-y: auto; background: #fcfdfe;">
                    <?php if(isset($_GET['broadcast_success'])): ?>
                        <div style="background:#eafaf1; color:#2ecc71; padding:15px; border-radius:8px; margin-bottom:20px; text-align: center; font-weight: bold;">
                            ✔ Announcement sent successfully!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="send_broadcast.php">
                        <label style="font-size:0.85rem; font-weight:bold; color:#333;">Subject / Title</label>
                        <input type="text" name="subject" class="form-control" placeholder="E.g., Reminders for Prelim Exams" required style="margin-bottom: 15px;">
                        
                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="font-size:0.85rem; font-weight:bold; color:#333;">Start Date</label>
                                <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size:0.85rem; font-weight:bold; color:#333;">End Date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <label style="font-size:0.85rem; font-weight:bold; color:#333;">Announcement Message</label>
                        <textarea name="message" class="form-control" rows="10" placeholder="Write your announcement details here..." required style="resize:none; padding: 15px;"></textarea>
                        
                        <div style="margin-top: 15px; text-align: right;">
                            <button type="submit" class="btn-primary" style="width: auto; padding: 12px 30px; background: #d35400; font-size: 1rem;">Send to All Students ➔</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($active_student_id): ?>
                <div class="chat-header"><h3 style="margin:0;"><?= htmlspecialchars($active_student_name) ?></h3></div>
                <div class="chat-history">
                    <?php
                    $chatStmt = $db->prepare("SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at ASC");
                    $chatStmt->execute([$counselor_id, $active_student_id, $active_student_id, $counselor_id]);
                    
                    $last_time = null; 
                    
                    while($chat = $chatStmt->fetch()) {
                        $side = ($chat['sender_id'] == $counselor_id) ? 'sent' : 'received';
                        $current_time = strtotime($chat['created_at']);
                        
                        if ($last_time === null || ($current_time - $last_time) > 1800) {
                            $formatted_time = date('D g:i A', $current_time); 
                            echo "<div class='time-divider'>{$formatted_time}</div>";
                        }
                        $last_time = $current_time;
                        
                        $exact_time = date('M d, Y g:i A', $current_time);
                        echo "<div class='chat-bubble {$side}' title='{$exact_time}'>".htmlspecialchars($chat['content'])."</div>";
                    }
                    ?>
                </div>
                <div class="chat-input">
                    <form method="POST" action="send_message.php" style="display: flex; gap: 10px;">
                        <input type="hidden" name="recipient_id" value="<?= $active_student_id ?>">
                        <input type="text" name="content" class="form-control" placeholder="Write a message..." required>
                        <button type="submit" class="btn-primary btn-send">➔</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align:center; margin-top:200px; color:#ccc;"><p>Select a student to start or create a broadcast</p></div>
            <?php endif; ?>
        </div>

        <div class="form-card" style="padding: 20px;">
            <h3 style="margin-top:0;">Schedule Session</h3>
            <?php if(isset($_GET['sched'])): ?>
                <div style="background:#eafaf1; color:#2ecc71; padding:10px; border-radius:8px; margin-bottom:10px;">✔ Saved!</div>
            <?php endif; ?>
            
            <form action="set_schedule.php" method="POST">
                <label style="font-size:0.75rem; font-weight:bold;">STUDENT</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($active_student_name) ?>" readonly style="background:#f8f9fa;">
                <input type="hidden" name="student_user_id" value="<?= $active_student_id ?>">

                <label style="font-size:0.75rem; font-weight:bold;">DATE</label>
                <input type="date" name="schedule_date" class="form-control" required <?= !$active_student_id ? 'disabled' : '' ?>>

                <label style="font-size:0.75rem; font-weight:bold;">TIME</label>
                <input type="time" name="schedule_time" class="form-control" required <?= !$active_student_id ? 'disabled' : '' ?>>

                <label style="font-size:0.75rem; font-weight:bold;">NOTES / REASON</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Enter session notes..." <?= !$active_student_id ? 'disabled' : '' ?>></textarea>

                <button type="submit" class="btn-primary" <?= !$active_student_id ? 'disabled style="opacity:0.5;"' : '' ?>>Save Schedule</button>
            </form>
            <div style="margin-top:20px; border-top:1px solid #eee; padding-top:10px;">
                <a href="view_schedules.php" style="color:#4267b2; font-size:0.85rem; text-decoration:none; font-weight: 600;">View All Schedules &rarr;</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>