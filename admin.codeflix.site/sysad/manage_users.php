<?php
// FILE: sysad/manage_users.php
require_once __DIR__ . '/../config/auth_guard.php';
require __DIR__ . '/../config/db.php';

// --- FILTER & SEARCH LOGIC ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$divisionFilter = isset($_GET['division']) ? trim($_GET['division']) : '';

$whereClauses = [];
$params = [];

// 1. Text Search Condition
if (!empty($search)) {
    $whereClauses[] = "(first_name LIKE :s1 
                        OR last_name LIKE :s2 
                        OR email LIKE :s3 
                        OR username LIKE :s4)";
    $term = "%$search%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
}

// 2. Role Dropdown Condition
if (!empty($roleFilter)) {
    $whereClauses[] = "role = :role";
    $params[':role'] = $roleFilter;
}

// 3. Division Dropdown Condition
if (!empty($divisionFilter)) {
    $whereClauses[] = "division = :division";
    $params[':division'] = $divisionFilter;
}

// Combine all conditions dynamically
$searchCondition = "";
if (count($whereClauses) > 0) {
    $searchCondition = "WHERE " . implode(" AND ", $whereClauses);
}

// --- PAGINATION LOGIC ---
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    // 1. Get Total Count
    $sqlCount = "SELECT COUNT(*) FROM users $searchCondition";
    $countStmt = $pdo->prepare($sqlCount);
    $countStmt->execute($params); 
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // 2. Fetch Users
    $sql = "SELECT * FROM users $searchCondition ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // Bind Search Params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind Pagination Params
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper to keep filters active in pagination links
$queryString = "&search=" . urlencode($search) . "&role=" . urlencode($roleFilter) . "&division=" . urlencode($divisionFilter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../assets/css/manage_users.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container">
        
        <div class="page-header">
            <h1>Manage Users</h1>
            <p>View, search, filter, and manage system accounts.</p>
        </div>

        <div class="data-card">
            <div class="top-controls">
                <form method="GET" action="" class="search-form">
                    
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input search-text" 
                               placeholder="Search name, email, or username..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <select name="role" class="search-input select-filter">
                        <option value="">All Roles</option>
                        <option value="Admin" <?= $roleFilter == 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Student" <?= $roleFilter == 'Student' ? 'selected' : '' ?>>Student</option>
                        <option value="Counselor" <?= $roleFilter == 'Counselor' ? 'selected' : '' ?>>Counselor</option>
                    </select>

                    <select name="division" class="search-input select-filter">
                        <option value="">All Divisions</option>
                        <option value="admin" <?= $divisionFilter == 'admin' ? 'selected' : '' ?>>System Admin</option>
                        <option value="counselor" <?= $divisionFilter == 'counselor' ? 'selected' : '' ?>>Counselor</option>
                        <option value="student" <?= $divisionFilter == 'student' ? 'selected' : '' ?>>Student</option>
                    </select>

                    <button type="submit" class="btn btn-search"><i class="fas fa-filter"></i> Filter</button>
                    
                    <?php if(!empty($search) || !empty($roleFilter) || !empty($divisionFilter)): ?>
                        <a href="manage_users.php" class="btn btn-reset"><i class="fas fa-undo"></i> Reset</a>
                    <?php endif; ?>
                </form>

                <a href="user_form.php" class="btn btn-add"><i class="fas fa-plus"></i> Add New User</a>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Role / Division</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-folder-open"></i><br>
                                    No users found matching your filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="<?= $user['status'] == 'INACTIVE' ? 'row-inactive' : '' ?>">
                                <td class="id-column">#<?= htmlspecialchars($user['user_id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></strong><br>
                                    <span class="sub-text"><?= htmlspecialchars($user['email']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-role"><?= htmlspecialchars($user['role']) ?></span><br>
                                    <span class="sub-text div-text"><?= htmlspecialchars($user['division']) ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                <td>
                                    <?php if($user['status'] == 'ACTIVE'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <a href="user_form.php?id=<?= $user['user_id'] ?>" class="action-btn btn-edit" title="Edit User">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if($user['status'] == 'ACTIVE'): ?>
                                        <a href="delete_user.php?id=<?= $user['user_id'] ?>" 
                                           class="action-btn btn-delete"
                                           onclick="return confirm('Are you sure you want to deactivate this user?');" title="Deactivate">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?page=<?= $page - 1 ?><?= $queryString ?>" 
                   class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
                
                <span class="page-info">Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong></span>
                
                <a href="?page=<?= $page + 1 ?><?= $queryString ?>" 
                   class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>