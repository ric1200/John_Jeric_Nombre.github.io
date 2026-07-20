<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Ensure these paths match your project structure
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../public_html/shared/config/mail_config.php';

$token   = $_GET['token'] ?? '';
$error   = '';
$success = false;
$reset   = null;

// 1. Validate Token Presence
if (!$token) {
    $error = "Invalid or missing reset link.";
} else {
    // 2. Verify Token in Database
    $stmt = $pdo->prepare("
        SELECT pr.id AS reset_id, pr.user_id, pr.expires_at, pr.used
        FROM password_resets pr
        WHERE pr.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Check Expiry and Usage
    if (!$reset) {
        $error = "Invalid reset token.";
    } elseif ($reset['used']) {
        $error = "This reset link has already been used.";
    } elseif (strtotime($reset['expires_at']) < time()) {
        $error = "This link has expired. Please request a new one.";
    }
}

// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic Validation
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // Update user password
            // Note: Ensure 'password_hash' matches your actual column name (sometimes it is just 'password')
            $stmt = $pdo->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE user_id = ? 
            ");
            $stmt->execute([$hash, $reset['user_id']]);

            // Mark token as used
            $stmt = $pdo->prepare("
                UPDATE password_resets
                SET used = 1
                WHERE id = ?
            ");
            $stmt->execute([$reset['reset_id']]);

            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred while saving. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password | Counseling Office</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f3f4f6;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 12px;
            --error-bg: #fef2f2;
            --error-text: #991b1b;
            --success-bg: #ecfdf5;
            --success-text: #065f46;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-dark);
            padding: 1rem;
        }

        .container {
            width: 100%;
            max-width: 400px;
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .icon-wrap {
            width: 50px;
            height: 50px;
            background-color: #e0e7ff;
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
        }

        .icon-wrap svg { width: 24px; height: 24px; }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        p.subtitle {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: var(--text-dark);
        }

        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
        }

        input[type="password"]:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 1rem;
            transition: background-color 0.2s;
        }

        button:hover { background-color: var(--primary-hover); }

        .btn-secondary {
            background-color: #e5e7eb;
            color: var(--text-dark);
        }
        .btn-secondary:hover { background-color: #d1d5db; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .alert-error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #a7f3d0;
            text-align: center;
        }

        a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="icon-wrap">
        <?php if ($success): ?>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <h2>All set!</h2>
        <div class="alert alert-success">
            Your password has been successfully reset.
        </div>
        <a href="https://codeflix.site/counselor">
            <button class="btn-primary">Return to Login</button>
        </a>
    
    <?php elseif ($error): ?>
        <h2>Reset Failed</h2>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
        <p class="subtitle">Please request a new reset link.</p>
        <a href="forgot-password.php">
            <button class="btn-secondary">Request New Link</button>
        </a>

    <?php else: ?>
        <h2>Set new password</h2>
        <p class="subtitle">Your new password must be at least 8 characters.</p>

        <form method="post">
            <div class="form-group">
                <label for="password">New Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>

</div>

</body>
</html>