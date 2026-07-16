<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Login</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link rel="stylesheet" href="assets/css/style.css" />
  
  <style>
    /* Dagdag na simpleng style para sa error message */
    .error-msg {
      background-color: #ffe6e6;
      color: #d9534f;
      border: 1px solid #d9534f;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
      font-size: 14px;
      text-align: center;
    }
  </style>
</head>
<body>

  <a href="https://ric1200.github.io/John_Jeric_Nombre.github.io/index.html" class="back-btn">
    <i class="fa-solid fa-arrow-left"></i> Back to Home
  </a>

  <div class="login-container">
    
    <div class="left-section">
      <div class="overlay">
        <h1>UCL Counseling Office</h1>
        <p>Guiding students towards mental wellness and success.</p>
      </div>
    </div>

    <div class="right-section">
      <form action="login_process.php" method="POST" class="login-form">
        <h2>Welcome Back</h2>
        <p class="subtitle">Login to access your dashboard</p>

        <!-- DITO LALABAS ANG ERROR MESSAGE MULA SA LOGIN PROCESS -->
        <?php
        if (isset($_SESSION['login_error'])) {
            echo '<div class="error-msg">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
            // Tanggalin ang error sa session pagkatapos ipakita
            unset($_SESSION['login_error']);
        }
        ?>

        <!-- Pinalitan ang label at type bilang Email dahil Supabase Auth ang gamit -->
        <label for="username">Email Address</label>
        <input type="email" id="username" name="username" required />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />

        <button type="submit" class="login-btn">Login</button>
      
        <div class="links">
            <a href="forgot-password.php">Forgot your password?</a>
        </div>

        <div class="footer">
          © 2026 UCL Counseling Office. All rights reserved.
        </div>
      </form>
    </div>

  </div>

</body>
</html>