<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/header.css">
    
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-brand">
            <img src="../assets/image/union_logo1.png" alt="Admin Logo" class="logo-circle">
            Phinma UCL Counselor
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="cases.php">Case Management</a>
            <a href="messages.php">Messages</a>
            <a href="view_schedules.php">Schedules</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </nav>