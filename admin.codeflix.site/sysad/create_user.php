<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../config/auth_guard.php';
require __DIR__ . '/../config/role_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create User</title>
  <link rel="stylesheet" href="../assets/css/create_user.css">
</head>
<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main">
  <div class="header">
    <h1>Create New User</h1>
    <p>Fill out the form below to add a new user.</p>
  </div>

  <div class="form-card">
    <form method="POST" action="save_user.php">
      <input name="first_name" required placeholder="First Name">
      <input name="middle_name" required placeholder="Middle Name">
      <input name="last_name" required placeholder="Last Name">
      <input name="email" type="email" required placeholder="Email">
      <input name="username" required placeholder="Username">

      <select name="division" required>
        <option value="">Select Division</option>
        <option>ADMIN</option>
        <option>COUNSELOR</option>
        <option>STUDENT</option>
      </select>

      <select name="role" required>
        <option value="">Select Role</option>
        <option>ADMIN</option>
        <option>COUNSELOR</option>
        <option>STUDENT</option>
        <option>ADMIN_REPORT</option>
      </select>

      <button type="submit">Create User</button>
    </form>
  </div>
</div>

</body>
</html>
