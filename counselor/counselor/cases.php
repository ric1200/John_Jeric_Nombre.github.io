<?php  
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php'; 
include __DIR__ . '/../includes/header.php'; 

// --- SEARCH & PAGINATION SETUP ---
$search = $_GET['search'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_month = $_GET['month'] ?? ''; 

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// --- BUILD QUERY CONDITIONS DYNAMICALLY ---
$whereConditions = [];
$params = [];

if (!empty($search)) {
    // Naghahanap na ngayon sa sp.student_id
    $whereConditions[] = "(u.first_name LIKE :s_fname OR u.last_name LIKE :s_lname OR sp.student_id LIKE :s_id)";
    $searchVal = "%$search%";
    $params['s_fname'] = $searchVal;
    $params['s_lname'] = $searchVal;
    $params['s_id']    = $searchVal;
}

if (!empty($filter_priority)) {
    $whereConditions[] = "c.priority = :priority";
    $params['priority'] = $filter_priority;
}

if (!empty($filter_status)) {
    $whereConditions[] = "c.status = :status";
    $params['status'] = $filter_status;
}

if (!empty($filter_month)) {
    $whereConditions[] = "DATE_FORMAT(c.created_at, '%Y-%m') = :month";
    $params['month'] = $filter_month;
}

$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// --- GET TOTAL ROWS FOR PAGINATION ---
try {
    // Idinagdag ang LEFT JOIN para sa student_profiles
    $countQuery = "SELECT COUNT(DISTINCT c.case_id) 
                   FROM cases c 
                   LEFT JOIN users u ON c.student_user_id = u.username OR c.student_user_id = u.user_id 
                   LEFT JOIN student_profiles sp ON c.student_user_id = sp.user_id
                   $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    
    // Auto-bind parameters for counting
    foreach ($params as $key => $val) {
        $countStmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
    }

    $countStmt->execute();
    $total_rows = $countStmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);
} catch (PDOException $e) {
    die("Error counting rows: " . $e->getMessage());
}

$queryParams = $_GET;
unset($queryParams['page']); 
$queryString = http_build_query($queryParams);
$searchQuery = !empty($queryString) ? '&' . $queryString : ''; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://codeflix.site/counselor/assets/css/cases.css">
<style>
    .controls-container { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .filter-group { display: flex; flex-wrap: wrap; gap: 10px; flex-grow: 1; align-items: center;}
    .search-input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; }
    .search-input.text-search { min-width: 200px; }
    .search-btn { background: #6c757d; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; transition: 0.3s;}
    .search-btn:hover { background: #5a6268; }
    .action-buttons { display: flex; gap: 10px; margin-top: 2px;}
    .btn-print { background: #6c757d; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; text-decoration: none; font-size: 0.9rem; transition: 0.3s;}
    .btn-print:hover { background: #5a6268; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; flex-wrap: wrap;}
    .page-link { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; transition: 0.3s; }
    .page-link:hover { background: #f0f0f0; }
    .page-link.active { background: #4267b2; color: white; border-color: #4267b2; pointer-events: none; }
    
    /* FIX PARA SA VIEW DETAILS BUTTON */
    .btn-sm { white-space: nowrap; display: inline-block; }
    .data-table th:last-child, .data-table td:last-child { white-space: nowrap; }

    /* PRINT SPECIFIC STYLES - FIX PARA HINDI MAPUTOL */
    @media print {
        @page {
            size: landscape; /* Auto-selects Landscape mode sa Print Dialog */
            margin: 10mm; /* Nagbibigay ng breathing room sa gilid */
        }
        header, nav, .navbar, footer, .controls-container, .header-title p, .pagination, .hide-on-print { display: none !important; }
        body { background: white; margin: 0; padding: 0; }
        .content-section, .card { box-shadow: none; margin: 0; border: none; width: 100%; max-width: 100%; padding: 0; }
        
        .data-table { 
            border-collapse: collapse; 
            width: 100%; 
            table-layout: fixed; /* Pinipigilan ang table na mag-stretch lagpas ng 100% */
        }
        .data-table th, .data-table td { 
            border: 1px solid #000; 
            padding: 5px; /* Pinaliit na padding para maka-save ng space */
            font-size: 10px; /* Pinaliit na font size para magkasya lahat */
            word-wrap: break-word; /* Pipilitin na bumaba ang text kung masyadong mahaba */
            overflow-wrap: break-word;
        }
        
        /* Optional: Pwedeng paliitin ang width ng specific columns kung kailangan */
        .data-table th:nth-child(1), .data-table td:nth-child(1) { width: 8%; } /* Case ID */
        .data-table th:nth-child(2), .data-table td:nth-child(2) { width: 10%; } /* System User ID */
        .data-table th:nth-child(6), .data-table td:nth-child(6) { width: 10%; } /* Priority */
        .data-table th:nth-child(7), .data-table td:nth-child(7) { width: 10%; } /* Status */
    }
</style>

<div class="content-section">
    <div class="header-title">
        <h1>Case Management</h1>
        <p>Track, update, and manage student counseling records securely</p>
    </div>

    <div class="card">
        <div class="controls-container">
            <form method="GET" class="filter-group">
                <input type="text" name="search" class="search-input text-search" placeholder="Search name or ID..." value="<?= htmlspecialchars($search) ?>">
                
                <select name="priority" class="search-input">
                    <option value="">All Priorities</option>
                    <option value="Low" <?= $filter_priority === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $filter_priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $filter_priority === 'High' ? 'selected' : '' ?>>High</option>
                    <option value="Urgent" <?= $filter_priority === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>

                <select name="status" class="search-input">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Resolved" <?= $filter_status === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="Closed" <?= $filter_status === 'Closed' ? 'selected' : '' ?>>Closed</option>
                </select>

                <input type="month" name="month" class="search-input" value="<?= htmlspecialchars($filter_month) ?>" title="Filter by Month">

                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Filter</button>
                <?php if (!empty($search) || !empty($filter_priority) || !empty($filter_status) || !empty($filter_month)): ?>
                    <a href="cases.php" class="search-btn" style="background:#dc3545; text-decoration:none;" title="Clear Filters"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Print</button>
                <a href="add_case.php" class="btn-primary" style="padding: 10px 15px; border-radius: 6px; text-decoration:none;"><i class="fas fa-plus"></i> Add Case</a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Case ID</th>
                        <th>System User ID</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th class="hide-on-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Idinagdag ang sp.student_id at LEFT JOIN sa student_profiles
                        $query = "SELECT c.*, u.first_name, u.last_name, sp.student_id AS profile_student_id
                                  FROM cases c
                                  LEFT JOIN users u ON c.student_user_id = u.username OR c.student_user_id = u.user_id
                                  LEFT JOIN student_profiles sp ON c.student_user_id = sp.user_id
                                  $whereClause
                                  GROUP BY c.case_id 
                                  ORDER BY c.created_at DESC
                                  LIMIT :limit OFFSET :offset";
                        
                        $stmt = $pdo->prepare($query);
                        
                        // Bind search/filter parameters
                        foreach ($params as $key => $val) {
                            $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
                        }
                        
                        // Bind pagination (must be INT)
                        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
                        
                        $stmt->execute();
                        $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($cases) {
                            foreach ($cases as $row) {
                                $case_id       = $row['case_id'] ?? 'N/A';
                                $system_id     = $row['student_user_id'] ?? 'N/A'; // Yung ID na nasa cases table
                                $actual_student_id = $row['profile_student_id'] ?? 'N/A'; // Yung ID galing student_profiles
                                
                                $student_name  = (!empty($row['first_name']) || !empty($row['last_name'])) 
                                                 ? $row['first_name'] . ' ' . $row['last_name'] 
                                                 : 'N/A';
                                $title         = $row['title'] ?? 'N/A';
                                $priority      = $row['priority'] ?? 'Low';
                                $status        = $row['status'] ?? 'Pending';
                                $created       = $row['created_at'] ?? null;

                                $priorityClass = match($priority) {
                                    'High', 'Urgent', 'Critical' => 'badge-high',
                                    'Medium' => 'badge-progress',
                                    default => 'badge-normal'
                                };

                                $statusClass = match($status) {
                                    'Resolved', 'Closed' => 'badge-resolved',
                                    'In Progress' => 'badge-progress',
                                    'Pending' => 'badge-high',
                                    default => 'badge-normal'
                                };

                                $dateCreated = !empty($created) ? date("M d, Y", strtotime($created)) : 'N/A';

                                // Kumpleto na ang 9 columns
                                echo "<tr>";
                                echo "<td><strong>CAS-" . htmlspecialchars($case_id) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($system_id) . "</td>";
                                echo "<td>" . htmlspecialchars($actual_student_id) . "</td>";
                                echo "<td>" . htmlspecialchars($student_name) . "</td>";
                                echo "<td>" . htmlspecialchars($title) . "</td>";
                                echo "<td><span class='badge $priorityClass'>" . htmlspecialchars($priority) . "</span></td>";
                                echo "<td><span class='badge $statusClass'>" . htmlspecialchars($status) . "</span></td>";
                                echo "<td>" . $dateCreated . "</td>";
                                echo "<td class='hide-on-print'><a href='view_case.php?id=" . urlencode($case_id) . "' class='btn-sm'>View Details</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            // Ginawang colspan='9' para sakop buong table pag walang data
                            echo "<tr><td colspan='9' style='text-align:center; padding: 40px; color: #8c92a0;'>No cases found matching your filters.</td></tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='9' style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $searchQuery ?>" class="page-link">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $searchQuery ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= $searchQuery ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>