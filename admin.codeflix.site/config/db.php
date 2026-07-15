<?php

$host = 'localhost';
$dbname = 'code2025_login_system';  
$username = 'code2025';             
$password = 'codeflixmoto2025';          
$charset = 'utf8mb4';

// 1. ESTABLISH CONNECTION
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Better security
        ]
    );
} catch (PDOException $e) {
    // Log the error to a server file instead of showing it to the user
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}

// 2. DEFINE AUDIT FUNCTION
/**
 * Inserts a log into the audit_logs table.
 * * @param PDO $pdo           The database connection object
 * @param string $action     Short text for action (e.g., 'LOGIN', 'DELETE')
 * @param string|null $table The table affected (e.g., 'users')
 * @param mixed $object_id   The specific ID of the row affected
 * @param mixed $changed_data Array or JSON of what changed
 * @param string|null $sql   The SQL query text (optional)
 * @param int|null $user_id  The ID of the user (NULL if not logged in)
 */
function insertAudit($pdo, $action, $table = null, $object_id = null, $changed_data = null, $sql_text = null, $user_id = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs 
            (user_id, action, table_name, object_id, changed_data, sql_text, ip_address) 
            VALUES 
            (:uid, :act, :tbl, :obj, :data, :sql, :ip)
        ");

        // Handle JSON Data
        $json_data = (is_array($changed_data) || is_object($changed_data)) 
            ? json_encode($changed_data) 
            : $changed_data;

        // Handle IP Address
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        $stmt->execute([
            ':uid'  => $user_id,
            ':act'  => $action,
            ':tbl'  => $table,
            ':obj'  => $object_id,
            ':data' => $json_data,
            ':sql'  => $sql_text,
            ':ip'   => $ip
        ]);

    } catch (Exception $e) {
        // We use error_log so the user doesn't see a broken page if logging fails
        error_log("Audit Log Failed: " . $e->getMessage());
    }
}
?>