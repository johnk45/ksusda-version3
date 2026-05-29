<?php
// includes/header.php - Modified to not close HTML tags
if(!isset($_SESSION)) session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME ?? 'Church Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        /* Sidebar Styles (for admin pages) */
        .sidebar {
            min-height: 100vh;
            background-color: #0f172a;
        }
        
        .sidebar a {
            color: #94a3b8;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
        }
        
        .sidebar a:hover {
            background-color: #1e293b;
            color: white;
        }
        
        .sidebar .nav-link.active {
            background-color: #E74C3C;
            color: white;
        }
        
        .main-content {
            padding: 20px;
            min-height: calc(100vh - 60px);
        }
        
        /* Stat Card Styles */
        .stat-card {
            border-radius: 16px;
            padding: 20px;
            color: white;
            transition: transform 0.2s;
            cursor: pointer;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

<!-- For admin pages with sidebar -->
<?php if(isset($_SESSION['user_id'])): ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-0 sidebar">
            <div class="text-center py-3">
                <i class="fas fa-church fa-2x text-white"></i>
                <h5 class="text-white mt-2">SDA Church MS</h5>
                <small class="text-muted">Kisii University</small>
            </div>
            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="members.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Members
                </a>
                <a href="attendance.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
                <a href="offerings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'offerings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding-usd"></i> Offerings
                </a>
                <a href="events.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Events
                </a>
                <a href="departments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i> Departments
                </a>
                <a href="admin_sermons.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_sermons.php' ? 'active' : ''; ?>">
                    <i class="fab fa-youtube"></i> Sermons
                </a>
                <a href="admin_prayers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_prayers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-praying-hands"></i> Prayer Requests
                </a>
                <a href="admin_approve_members.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_approve_members.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i> Member Approvals
                </a>
                <a href="manage_leaders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_leaders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i> Church Leaders
                </a>
                <a href="admin_backup.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_backup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i> Database Backup
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        <div class="col-md-10 main-content">
<?php else: ?>
<!-- For public pages (no sidebar) -->
<div class="main-content">
<?php endif; ?>