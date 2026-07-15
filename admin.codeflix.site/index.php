<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Login</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

  <a href="https://codeflix.site" class="back-btn">
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

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required />

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