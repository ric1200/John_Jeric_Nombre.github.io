<?php 
require_once __DIR__ . '/../config/counselor_auth_guard.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Hook sa Database (PDO) at Header
require_once __DIR__ . '/../config/db.php'; 
include __DIR__ . '/../includes/header.php'; 

// Dito natin gagamitin ang $pdo galing sa db.php mo
$total_cases = 0;
$in_progress = 0;
$resolved = 0;
$upcoming = 0;
$months = [];
$counts = [];

try {
    // A. Bilangin LAHAT ng cases (Base sa image mo, dapat 10 ito)
    $total_cases = $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();

    // B. Bilangin ang 'In Progress'
    $stmt_prog = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status = ?");
    $stmt_prog->execute(['In Progress']);
    $in_progress = $stmt_prog->fetchColumn();

    // C. Bilangin ang 'Resolved' at 'Closed'
    $stmt_res = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status IN ('Resolved', 'Closed')");
    $stmt_res->execute();
    $resolved = $stmt_res->fetchColumn();

    // D. Bilangin ang 'Open' at 'Pending'
    $stmt_up = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status IN ('Open', 'Pending')");
    $stmt_up->execute();
    $upcoming = $stmt_up->fetchColumn();

    // E. Data para sa Chart (Cases per Month)
    $chart_data = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b') as m, COUNT(*) as c 
        FROM cases 
        GROUP BY MONTH(created_at) 
        ORDER BY MONTH(created_at)
    ")->fetchAll();

    foreach ($chart_data as $row) {
        $months[] = $row['m'];
        $counts[] = $row['c'];
    }

} catch (PDOException $e) {
    echo "Query Error: " . $e->getMessage();
}
?>

<link rel="stylesheet" href="https://codeflix.site/counselor/assets/css/dashboard.css">

<div class="container">
    <div class="page-header">
        <h1>Counselor Dashboard</h1>
        <p>System overview and statistics</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Cases</h3>
            <div class="stat-value"><?php echo $total_cases; ?></div>
        </div>
        <div class="stat-card">
            <h3>In Progress</h3>
            <div class="stat-value"><?php echo $in_progress; ?></div>
        </div>
        <div class="stat-card">
            <h3>Resolved / Closed</h3>
            <div class="stat-value"><?php echo $resolved; ?></div>
        </div>
        <div class="stat-card">
            <h3>Open / Pending</h3>
            <div class="stat-value"><?php echo $upcoming; ?></div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <h3>Cases per Month</h3>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="myChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3>Status Distribution</h3>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Bar Chart
    new Chart(document.getElementById('myChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Cases',
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: '#90caf9',
                borderColor: '#42a5f5',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: ['In Progress', 'Resolved', 'Open/Pending'],
            datasets: [{
                data: [<?php echo "$in_progress, $resolved, $upcoming"; ?>],
                backgroundColor: ['#ffcc80', '#a5d6a7', '#90caf9']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>