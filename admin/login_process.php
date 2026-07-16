<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Siguraduhing nandito pa rin ang connection mo sa PostgreSQL via PDO
require __DIR__ . '/config/db.php'; 

// --- SUPABASE CONFIGURATION ---
// Kunin ito sa Supabase Dashboard -> Project Settings -> API
$supabase_url = 'https://YOUR_PROJECT_ID.supabase.co';
$supabase_anon_key = 'YOUR_ANON_KEY_HERE';

/**
 * Helper function para mag-insert sa bagong audit_logs table.
 * Naka-format ito para tumugma sa JSONB column ng Supabase.
 */
function logAudit($pdo, $userId, $action, $detailsArray) {
    // Kunin ang IP address ng user
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // I-convert ang PHP array sa JSON string para sa JSONB column
    $detailsJson = json_encode($detailsArray);

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, details, ip_address) 
        VALUES (:user_id, :action, :details, :ip_address)
    ");
    
    $stmt->execute([
        ':user_id' => $userId, // Maaaring NULL kung failed login ng non-existent user
        ':action' => $action,
        ':details' => $detailsJson,
        ':ip_address' => $ipAddress
    ]);
}

if (!empty($_POST['username']) && !empty($_POST['password'])) {
    
    // Note: Ang Supabase ay gumagamit ng Email para sa login.
    // I-a-assume natin na ang 'username' input form ay naglalaman ng email address.
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
        
        // Nakuha natin ang UUID ng user mula sa Supabase auth.users
        $user_id = $auth_data['user']['id']; 

        // 3. I-check kung ang user na ito ay nasa `admin_profiles` table
        $stmt = $pdo->prepare("SELECT department, access_level FROM admin_profiles WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $admin_profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin_profile) {
            // SUCCESSFUL LOGIN & AUTHORIZED ADMIN
            
            // I-log ang tagumpay sa audit_logs
            logAudit($pdo, $user_id, 'LOGIN_SUCCESS', [
                'message' => 'Admin logged in successfully',
                'email' => $email,
                'access_level' => $admin_profile['access_level']
            ]);

            // Set up Session Variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['access_token'] = $auth_data['access_token']; // Magandang i-save kung tatawag ka ng iba pang Supabase APIs
            
            // I-map natin ang data sa mga ginagamit ng lumang system mo
            $_SESSION['role'] = $admin_profile['access_level']; 
            $_SESSION['division'] = $admin_profile['department'];

            // Redirect sa dashboard
            header("Location: /sysad/dashboard.php");
            exit;

        } else {
            // Naka-login sa Supabase, PERO wala sa admin_profiles table (Baka student o counselor)
            
            logAudit($pdo, $user_id, 'LOGIN_FAILED_UNAUTHORIZED', [
                'email' => $email,
                'reason' => 'User exists but is not an Admin.'
            ]);
            
            die("Unauthorized access. You do not have admin privileges.");
        }

    } else {
        // FAILED LOGIN (Mali ang email o password)
        
        $error_msg = $auth_data['error_description'] ?? 'Invalid credentials';
        
        logAudit($pdo, null, 'LOGIN_FAILED', [
            'attempted_email' => $email,
            'error_reason' => $error_msg
        ]);

        die("Unauthorized access. Check your email and password."); 
    }

} else {
    die("Please enter email and password.");
}
?>