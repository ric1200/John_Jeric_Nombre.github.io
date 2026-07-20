<?php
session_start();
// I-ON NATIN ANG ERRORS PARA HINDI WHITE SCREEN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. TAMANG DATABASE CONNECTION PATH (Galing sa cases.php mo)
require_once __DIR__ . '/../config/db.php'; 

// 2. CHECK KUNG GUMANA ANG CONNECTION (PDO Version)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("<div style='padding:20px; border:2px solid red; background:#ffeeee; color:red; margin:20px;'>
            <b>CRITICAL ERROR:</b> Hindi kumonekta sa database gamit ang PDO! <br>
            Paki-check ang db.php mo kung \$pdo ba ang ginagamit na variable doon.
         </div>");
}

if (!isset($_SESSION['user_id'])) {
    die("Access Denied: Please log in as a counselor.");
}

$counselor_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d');

    if (empty($subject) || empty($message) || empty($start_date) || empty($end_date)) {
        header("Location: messages.php?broadcast=1&error=empty_fields");
        exit;
    }

    try {
        // PDO Transaction
        $pdo->beginTransaction();

        // STEP 1: I-save sa announcements table
        $stmt1 = $pdo->prepare("INSERT INTO announcements (counselor_id, title, content, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, 'ACTIVE')");
        $stmt1->execute([$counselor_id, $subject, $message, $start_date, $end_date]);

        // STEP 2: Kunin lahat ng active students
        $stmt2 = $pdo->prepare("SELECT user_id FROM users WHERE role = 'STUDENT' AND status = 'ACTIVE'");
        $stmt2->execute();
        $students = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        // STEP 3: I-send ang message sa bawat student
        if (count($students) > 0) {
            $stmt3 = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content, status) VALUES (?, ?, ?, ?, 'unread')");
            
            foreach ($students as $student_id) {
                $stmt3->execute([$counselor_id, $student_id, $subject, $message]);
            }
        }

        $pdo->commit();
        header("Location: messages.php?broadcast=1&broadcast_success=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // DITO LALABAS ANG ERROR KUNG MAY MALI SA COLUMNS O DATABASE
        echo "<div style='font-family: sans-serif; padding: 20px; border: 2px solid red; background: #ffeeee; color: red; margin: 20px; border-radius: 8px;'>";
        echo "<h2>May Database Error!</h2>";
        echo "<p><b>Detalye ng Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<br><a href='messages.php?broadcast=1' style='background: red; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Bumalik sa Messages</a>";
        echo "</div>";
        exit;
    }
} else {
    header("Location: messages.php");
    exit;
}
?>