<?php
require_once __DIR__ . '/../config/auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Connect to Database
require __DIR__ . '/../config/db.php';

// 2. Fetch Total Users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// 3. Fetch Counts by Role
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Fetch Recent Audit Logs (Last 5 actions)
$logStmt = $pdo->query("
    SELECT a.*, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.user_id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$recentLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
  
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <div class="main-content">
    <div class="container">
        
        <div class="header">
          <h1>System Administrator Dashboard</h1>
          <p>Welcome back, Administrator</p>
        </div>

        <div class="dashboard-grid">
          <div class="stat-card primary">
            <div class="stat-content">
                <h3><?= $totalUsers ?></h3>
                <p>Total Users</p>
            </div>
          </div>

          <div class="stat-card warning">
            <div class="stat-content">
                <h3><?= $roleCounts['ADMIN'] ?? 0 ?></h3>
                <p>Admins</p>
            </div>
          </div>

          <div class="stat-card success">
            <div class="stat-content">
                <h3><?= $roleCounts['STUDENT'] ?? 0 ?></h3>
                <p>Students</p>
            </div>
          </div>
          
          <div class="stat-card danger">
            <div class="stat-content">
                <h3><?= $roleCounts['COUNSELOR'] ?? 0 ?></h3>
                <p>Counselors</p>
            </div>
          </div>
        </div>

        <div class="recent-activity">
            <div class="header-row">
                <h3>Recent System Activity</h3>
                <a href="audit_logs.php" class="btn-secondary">View All Logs</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="4" style="text-align:center;">No recent activity.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?= date('M d, H:i', strtotime($log['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong></td>
                                <td>
                                    <?php 
                                        $badge = 'badge-info';
                                        if(strpos($log['action'], 'DELETE') !== false) $badge = 'badge-danger';
                                        if(strpos($log['action'], 'CREATE') !== false) $badge = 'badge-success';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td style="color:#666;">
                                    <?= htmlspecialchars($log['table_name']) ?> (ID: <?= $log['object_id'] ?>)
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
  </div>

</body>
</html>