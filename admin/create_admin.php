<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Isama ang Database Connection
require __DIR__ . '/config/db.php'; 

// 2. Kunin ang Supabase Credentials mula sa .env
$envPath = __DIR__ . '/.env'; 
if (file_exists($envPath)) {
    $envVariables = parse_ini_file($envPath);
    $supabase_url = $envVariables['SUPABASE_URL'] ?? '';
    $supabase_anon_key = $envVariables['SUPABASE_ANON_KEY'] ?? '';
} else {
    $supabase_url = getenv('SUPABASE_URL');
    $supabase_anon_key = getenv('SUPABASE_ANON_KEY');
}

$message = '';

// Kapag sinubmit na ang form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && !empty($_POST['password'])) {
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    $department = $_POST['department'] ?? 'General';
    $access_level = $_POST['access_level'] ?? 'Standard';

    // HAKBANG 1: GUMAWA NG USER SA SUPABASE AUTH (Gamit ang cURL)
    $ch = curl_init("$supabase_url/auth/v1/signup");
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

    // I-check kung successful ang paggawa sa Supabase
    if ($http_code === 200 && isset($auth_data['user']['id'])) {
        
        $new_user_id = $auth_data['user']['id']; // Ito ang UUID natin

        // HAKBANG 2: I-SAVE ANG UUID AT PROFILE SA admin_profiles TABLE GAMIT ANG PDO
        try {
            $stmt = $pdo->prepare("
                INSERT INTO admin_profiles (id, department, access_level) 
                VALUES (:id, :department, :access_level)
            ");
            
            $stmt->execute([
                ':id' => $new_user_id,
                ':department' => $department,
                ':access_level' => $access_level
            ]);

            $message = "<div style='color: green; font-weight: bold;'>Admin user successfully created! (UUID: $new_user_id)</div>";

        } catch (PDOException $e) {
            // Kung magka-error sa PDO (halimbawa, duplicate), ipapakita rito
            $message = "<div style='color: red;'>Database Error: " . $e->getMessage() . "</div>";
        }

    } else {
        // Kapag nabigo ang paggawa ng user sa Supabase (hal. email already exists)
        $error_msg = $auth_data['error_description'] ?? $auth_data['msg'] ?? 'Failed to create user in Supabase.';
        $message = "<div style='color: red;'>Supabase Error: $error_msg</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 50px; }
        .container { background: #fff; padding: 30px; border-radius: 8px; max-width: 400px; margin: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { margin-top: 20px; width: 100%; padding: 10px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #004494; }
        .message-box { margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Register New Admin</h2>
        
        <div class="message-box">
            <?php echo $message; ?>
        </div>

        <form action="create_admin.php" method="POST">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password (Min 6 chars)</label>
            <input type="password" id="password" name="password" minlength="6" required>

            <label for="department">Department</label>
            <input type="text" id="department" name="department" placeholder="e.g., IT, Counseling" required>

            <label for="access_level">Access Level</label>
            <select id="access_level" name="access_level" required>
                <option value="Standard">Standard</option>
                <option value="Super Admin">Super Admin</option>
            </select>

            <button type="submit">Create Admin Account</button>
        </form>
    </div>

</body>
</html>