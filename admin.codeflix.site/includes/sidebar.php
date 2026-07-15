<?php
// Kunin natin kung anong pangalan ng kasalukuyang file na nakabukas
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/includes/sidebar.css?v=<?php echo time(); ?>">

<div class="sidebar">
  <div class="sidebar-header">
    <img src="/assets/image/union_logo1.png" alt="Admin Logo" class="sidebar-logo">
    <h2>UCL Counseling System</h2>
  </div>

  <div class="nav-links">
      <a href="/sysad/dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
          <i class="fas fa-border-all"></i> Dashboard
      </a>
      
      <a href="/sysad/manage_users.php" class="<?= ($current_page == 'manage_users.php' || $current_page == 'user_form.php') ? 'active' : '' ?>">
          <i class="far fa-user"></i> Manage Users
      </a>
      
      <a href="/sysad/audit_logs.php" class="<?= ($current_page == 'audit_logs.php') ? 'active' : '' ?>">
          <i class="fas fa-history"></i> Audit Logs
      </a>
      
      <a href="/sysad/profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : '' ?>">
          <i class="fas fa-cog"></i> Profile
      </a>
  </div>

  <div class="logout-section">
      <h3>Log Out</h3>
      <p>End your admin session and return to login.</p>
      <a href="/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log Out</a>
  </div>
</div>