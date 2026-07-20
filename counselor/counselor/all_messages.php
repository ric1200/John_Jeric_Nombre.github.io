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

// Kunin ang filter mula sa URL kung meron
$agenda_filter = $_GET['agenda'] ?? '';
?>

<style>
    .content-section { max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .header-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .header-title h1 { font-weight: 800; margin: 0; color: #333; }
    .header-title p { color: #8c92a0; margin: 5px 0 0 0; }
    .btn-back { background: #f0f2f5; color: #333; padding: 10px 15px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.2s; }
    .btn-back:hover { background: #e4e6e9; }
    
    .inbox-container { background: #fff; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eef2f7; overflow: hidden; }
    
    .filter-bar { background: #fafbfc; padding: 15px 20px; border-bottom: 1px solid #eef2f7; display: flex; justify-content: space-between; align-items: center; }
    .filter-form { display: flex; gap: 10px; align-items: center; }
    .form-select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 0.9rem; outline: none; min-width: 200px; }
    .btn-filter { background: #4267b2; color: white; border: none; padding: 10px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
    
    .msg-list { display: flex; flex-direction: column; }
    .msg-row { display: flex; align-items: center; padding: 20px; border-bottom: 1px solid #eef2f7; text-decoration: none; color: inherit; transition: all 0.2s; }
    .msg-row:last-child { border-bottom: none; }
    .msg-row:hover { background: #fcfdfe; }
    
    /* Styling para sa UNREAD messages */
    .msg-row.unread { background: #f4f7fb; border-left: 4px solid #4267b2; }
    .msg-row.unread .msg-name, .msg-row.unread .msg-content-text { font-weight: 700; color: #111; }
    
    .msg-details { flex-grow: 1; overflow: hidden; }
    .msg-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
    .msg-name { font-size: 1.05rem; color: #444; margin: 0; }
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #eaf0f9; color: #4267b2; white-space: nowrap; }
    
    .msg-content-text { font-size: 0.9rem; color: #666; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%; }
    
    .msg-meta { text-align: right; min-width: 120px; }
    .msg-date { font-size: 0.8rem; color: #888; font-weight: 500; }
    .msg-status { font-size: 0.75rem; margin-top: 5px; text-transform: uppercase; font-weight: 700; }
    .status-unread { color: #d93025; }
    .status-read { color: #1e8e3e; }
    
    .empty-state { text-align: center; padding: 50px 20px; color: #888; }
</style>

<div class="content-section">
    <div class="header-title">
        <div>
            <h1>All Messages</h1>
            <p>View and filter your complete inbox history</p>
        </div>
        <a href="messages.php" class="btn-back">&larr; Back to Chat</a>
    </div>

    <div class="inbox-container">
        <div class="filter-bar">
            <div style="font-weight: 600; color: #555;">Inbox List</div>
            <form method="GET" action="all_messages.php" class="filter-form">
                <select name="agenda" class="form-select">
                    <option value="">All Agendas</option>
                    <option value="Self-Referral" <?= $agenda_filter == 'Self-Referral' ? 'selected' : '' ?>>Self-Referral</option>
                    <option value="Academic Concerns" <?= $agenda_filter == 'Academic Concerns' ? 'selected' : '' ?>>Academic Concerns</option>
                    <option value="Referring a Friend" <?= $agenda_filter == 'Referring a Friend' ? 'selected' : '' ?>>Referring a Friend</option>
                    <option value="General Inquiry" <?= $agenda_filter == 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                    <option value="Follow-up" <?= $agenda_filter == 'Follow-up' ? 'selected' : '' ?>>Follow-up</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
            </form>
        </div>

        <div class="msg-list">
            <?php
            try {
                // Base SQL Query
                $sql = "SELECT m.*, u.first_name, u.last_name 
                        FROM messages m 
                        JOIN users u ON m.sender_id = u.user_id 
                        WHERE m.recipient_id = :counselor_id";
                
                $params = [':counselor_id' => $counselor_id];

                // Idagdag ang condition kung may piniling filter
                if (!empty($agenda_filter)) {
                    $sql .= " AND m.agenda = :agenda";
                    $params[':agenda'] = $agenda_filter;
                }

                // I-sort mula sa pinakabago
                $sql .= " ORDER BY m.created_at DESC";

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $messages = $stmt->fetchAll();

                if (count($messages) > 0) {
                    foreach ($messages as $msg) {
                        $full_name = htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']);
                        $agenda = !empty($msg['agenda']) ? htmlspecialchars($msg['agenda']) : 'No Agenda';
                        $content = htmlspecialchars($msg['content']);
                        $date = date("M d, Y h:i A", strtotime($msg['created_at']));
                        
                        // I-check ang status kung unread o read (Assuming may 'status' column ka base sa image mo kanina)
                        $status = strtolower($msg['status'] ?? 'unread'); 
                        $is_unread_class = ($status === 'unread') ? 'unread' : '';
                        $status_label_class = ($status === 'unread') ? 'status-unread' : 'status-read';

                        echo "
                        <a href='messages.php?student_id={$msg['sender_id']}' class='msg-row {$is_unread_class}'>
                            <div class='msg-details'>
                                <div class='msg-header'>
                                    <h4 class='msg-name'>{$full_name}</h4>
                                    <span class='badge'>{$agenda}</span>
                                </div>
                                <p class='msg-content-text'>{$content}</p>
                            </div>
                            <div class='msg-meta'>
                                <div class='msg-date'>{$date}</div>
                                <div class='msg-status {$status_label_class}'>" . ucfirst($status) . "</div>
                            </div>
                        </a>";
                    }
                } else {
                    echo "<div class='empty-state'>
                            <h3>No messages found.</h3>
                            <p>Try changing your filter or check back later.</p>
                          </div>";
                }
            } catch (PDOException $e) {
                echo "<div style='padding: 20px; color: red;'>Error loading messages: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>