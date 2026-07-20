<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$db = isset($pdo) ? $pdo : $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE counseling_schedules SET status = ? WHERE schedule_id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['schedule_id']]);
    header("Location: view_schedules.php?update=success");
    exit;
}