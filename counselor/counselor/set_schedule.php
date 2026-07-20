<?php
session_start();
require_once __DIR__ . '/../config/db.php'; 
$db = isset($pdo) ? $pdo : $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $counselor_id = $_SESSION['user_id'] ?? null;
    $student_user_id = $_POST['student_user_id'] ?? null;
    $date = $_POST['schedule_date'] ?? null;
    $time = $_POST['schedule_time'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if ($counselor_id && $student_user_id && $date && $time) {
        try {
            $sql = "INSERT INTO counseling_schedules 
                    (counselor_id, student_user_id, schedule_date, schedule_time, status, notes) 
                    VALUES (:cid, :suid, :sdate, :stime, 'Pending', :notes)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':cid'   => $counselor_id,
                ':suid'  => $student_user_id,
                ':sdate' => $date,
                ':stime' => $time,
                ':notes' => $notes
            ]);

            header("Location: messages.php?student_id=$student_user_id&sched=success");
            exit;
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
header("Location: messages.php");
exit;