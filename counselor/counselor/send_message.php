<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// I-connect ang database
require_once __DIR__ . '/../config/db.php'; 

// Siguraduhing may naka-login na counselor
$counselor_id = $_SESSION['user_id'] ?? null;

if (!$counselor_id) {
    die("Access Denied. Please log in first.");
}

// I-check kung galing sa form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = $_POST['recipient_id'] ?? ''; // Ito yung ID ng student
    $content = $_POST['content'] ?? '';           // Ito yung tinype na message sa chat box

    // Siguraduhing hindi blanko yung message at may padadalhan
    if (!empty($recipient_id) && !empty(trim($content))) {
        try {
            // I-save ang message sa database
            // Naglagay tayo ng default subject na 'Counselor Reply'
            $sql = "INSERT INTO messages (sender_id, recipient_id, subject, agenda, content, status) 
                    VALUES (:sender, :recipient, 'Counselor Reply', 'General Inquiry', :content, 'Unread')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':sender'    => $counselor_id,
                ':recipient' => $recipient_id,
                ':content'   => trim($content)
            ]);

            // Pagkatapos ma-send, ibabalik ka nito agad sa chat box para makita mo yung nireply mo
            header("Location: messages.php?student_id=" . urlencode($recipient_id));
            exit;

        } catch (PDOException $e) {
            die("<p style='color:red;'>Error sending message: " . htmlspecialchars($e->getMessage()) . "</p>");
        }
    } else {
        // Kapag pinindot ang send pero walang laman yung text box
        header("Location: messages.php?student_id=" . urlencode($recipient_id) . "&error=empty_message");
        exit;
    }
} else {
    // Kung in-access yung file directly nang walang sinasubmit na form
    header("Location: messages.php");
    exit;
}
?>