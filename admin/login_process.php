<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Siguraduhing tama ang path ng db.php mo base sa folder structure mo
require __DIR__ . '/config/db.php'; 

// --- SUPABASE CONFIGURATION ---
// Basahin ang .env file para kunin ang API keys nang ligtas
$envPath = __DIR__ . '/.env'; 

if (file_exists($envPath)) {
    $envVariables = parse_ini_file($envPath);
    $supabase_url = $envVariables['SUPABASE_URL'] ?? '';
    $supabase_anon_key = $envVariables['SUPABASE_ANON_KEY'] ?? '';
} else {
    // Fallback kung nasa live server at naka-set sa environment variables ng server
    $supabase_url = getenv('SUPABASE_URL');
    $supabase_anon_key = getenv('SUPABASE_ANON_KEY');
}

/**
 * Helper function para mag-insert sa bagong audit_logs table.
 * Naka-format ito para tumugma sa JSONB column ng Supabase.
 */
function logAudit($pdo, $userId, $action, $detailsArray) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $detailsJson = json_encode($detailsArray);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, details, ip_address) 
            VALUES (:user_id, :action, :details, :ip_address)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => $detailsJson,
            ':ip_address' => $ipAddress
        ]);
    } catch (PDOException $e) {
        error_log("Audit Log Error: " . $e->getMessage());
    }
}

if (!empty($_POST['username']) && !empty($_POST['password'])) {
    
    $email = $_POST['username'];
    $password = $_POST['password'];

    // 1. I-verify ang User gamit ang Supabase REST API (cURL)
    $ch = curl_init("$supabase_url/auth/v1/token?grant_type=password");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_anon_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $password
    ]));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $auth_data = json_decode($response, true);

    // 2. I-check kung successful ang authentication sa Supabase
    if ($http_code === 200 && isset($auth_data['access_token'])) {
        
        $user_id = $auth_data['user']['id']; 

        // 3. I-check kung ang user na ito ay nasa `admin_profiles` table
        $stmt = $pdo->prepare("SELECT department, access_level FROM admin_profiles WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $admin_profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin_profile) {
            // SUCCESSFUL LOGIN & AUTHORIZED ADMIN
            logAudit($pdo, $user_id, 'LOGIN_SUCCESS', [
                'message' => 'Admin logged in successfully',
                'email' => $email,
                'access_level' => $admin_profile['access_level']
            ]);

            // Set up Session Variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['access_token'] = $auth_data['access_token']; 
            $_SESSION['role'] = $admin_profile['access_level']; 
            $_SESSION['division'] = $admin_profile['department'];

            // Redirect sa dashboard
            header("Location: /sysad/dashboard.php");
            exit;

        } else {
            // Naka-login sa Supabase, PERO wala sa admin_profiles table
            logAudit($pdo, $user_id, 'LOGIN_FAILED_UNAUTHORIZED', [
                'email' => $email,
                'reason' => 'User exists but is not an Admin.'
            ]);
            
            $_SESSION['login_error'] = "Unauthorized access. You do not have admin privileges.";
            header("Location: index.php");
            exit;
        }

    } else {
        // FAILED LOGIN (Mali ang email o password)
        $error_msg = $auth_data['error_description'] ?? 'Invalid credentials';
        
        logAudit($pdo, null, 'LOGIN_FAILED', [
            'attempted_email' => $email,
            'error_reason' => $error_msg
        ]);

        $_SESSION['login_error'] = "Unauthorized access. Check your email and password.";
        header("Location: index.php");
        exit;
    }

} else {
    // WALANG LAMAN ANG INPUT
    $_SESSION['login_error'] = "Please enter email and password.";
    header("Location: index.php");
    exit;
}
?>