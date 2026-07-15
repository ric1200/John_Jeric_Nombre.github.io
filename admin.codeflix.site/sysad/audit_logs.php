<?php
require_once __DIR__ . '/../config/auth_guard.php';
// FILE: sysad/audit_logs.php
require __DIR__ . '/../config/db.php';

// --- PAGINATION SETTINGS ---
$limit = 10; // Logs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- BUILD FILTERS ---
$where = [];
$params = [];

// 1. Filter: action
if (!empty($_GET['action'])) {
    $where[] = "a.action = ?";
    $params[] = $_GET['action'];
}

// 2. Search logic (Inalis na ang table filter dito)
if (!empty($_GET['q'])) {
    $where[] = "(a.changed_data LIKE ? OR a.sql_text LIKE ? OR u.username LIKE ?)";
    $q = "%" . $_GET['q'] . "%";
    $params[] = $q;
    $params[] = $q;
    $params[] = $q;
}

// --- STEP 1: COUNT TOTAL RECORDS (For Pagination) ---
$countSql = "SELECT COUNT(*) 
             FROM audit_logs a 
             LEFT JOIN users u ON a.user_id = u.user_id";

if ($where) {
    $countSql .= " WHERE " . implode(" AND ", $where);
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// --- STEP 2: FETCH ACTUAL DATA ---
$sql = "SELECT a.*, u.username 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.user_id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key + 1, $val);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getPageUrl($newPage) {
    $queryParams = $_GET;
    $queryParams['page'] = $newPage;
    return '?' . http_build_query($queryParams);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | System Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/audit_logs.css?v=<?php echo time(); ?>">
</head>
<body>
  
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content"> 
    <div class="container">
        
        <div class="page-header">
            <div>
                <h2><i class="fas fa-history text-primary"></i> System Audit Logs</h2>
                <p class="subtitle">Track and monitor all system activities and data changes.</p>
            </div>
            <button onclick="window.print()" type="button" class="btn btn-secondary no-print">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <div class="filter-card no-print">
            <form method="get" class="filter-form">
                <div class="input-group">
                    <i class="fas fa-bolt"></i>
                    <input type="text" id="action" name="action" placeholder="Action (e.g. LOGIN)" value="<?= htmlspecialchars($_GET['action'] ?? '') ?>">
                </div>

                <div class="input-group search-group">
                    <i class="fas fa-search"></i>
                    <input type="text" id="q" name="q" placeholder="Search keywords..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="audit_logs.php" class="btn btn-light" title="Clear Filters"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="12%">Date & Time</th>
                            <th width="15%">User</th>
                            <th width="12%">Action</th>
                            <th width="10%">Table</th>
                            <th width="10%">Obj ID</th>
                            <th width="28%">Data Changes</th>
                            <th width="13%">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>No logs found matching your criteria.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="time-cell">
                                    <?= date('M d, Y', strtotime($log['created_at'])) ?><br>
                                    <small><?= date('h:i A', strtotime($log['created_at'])) ?></small>
                                </td>
                                
                                <td>
                                    <div class="user-cell">
                                        <strong><?= htmlspecialchars($log['username'] ?? 'System / Guest') ?></strong>
                                        <?php if($log['user_id']): ?>
                                            <span class="user-meta">ID: <?= htmlspecialchars($log['user_id']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php 
                                        $badgeClass = 'badge-info';
                                        if (strpos($log['action'], 'DELETE') !== false || strpos($log['action'], 'FAIL') !== false) {
                                            $badgeClass = 'badge-danger';
                                        } elseif (strpos($log['action'], 'SUCCESS') !== false || strpos($log['action'], 'LOGIN') !== false || strpos($log['action'], 'CREATE') !== false) {
                                            $badgeClass = 'badge-success';
                                        } elseif (strpos($log['action'], 'UPDATE') !== false) {
                                            $badgeClass = 'badge-warning';
                                        }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                
                                <td><span class="table-name"><?= htmlspecialchars($log['table_name'] ?? '-') ?></span></td>
                                <td><strong><?= htmlspecialchars($log['object_id'] ?? '-') ?></strong></td>
                                
                                <td class="data-cell">
                                    <?php 
                                        $data = json_decode($log['changed_data'], true);
                                        if (is_array($data) && !empty($data)) {
                                            echo '<div class="data-list">';
                                            foreach ($data as $key => $val) {
                                                if (in_array($key, ['password', 'password_hash', 'token'])) {
                                                    $val = '********'; 
                                                }
                                                if (is_array($val)) {
                                                    $val = json_encode($val);
                                                }
                                                $formattedKey = ucwords(str_replace('_', ' ', $key));
                                                echo "<div class='data-item'>
                                                        <span class='data-key'>{$formattedKey}:</span> 
                                                        <span class='data-val'>" . htmlspecialchars($val) . "</span>
                                                      </div>";
                                            }
                                            echo '</div>';
                                        } else {
                                            echo '<span class="text-muted">' . htmlspecialchars($log['changed_data'] ?? '-') . '</span>';
                                        }
                                    ?>
                                </td>
                                
                                <td class="ip-cell"><i class="fas fa-network-wired"></i> <?= htmlspecialchars($log['ip_address'] ?? 'Unknown') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination no-print">
            <a href="<?= getPageUrl($page - 1) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                <i class="fas fa-chevron-left"></i> Prev
            </a>

            <div class="page-info">
                <span>Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong></span>
            </div>

            <a href="<?= getPageUrl($page + 1) ?>" class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>

    </div>
  </div>
</body>
</html>