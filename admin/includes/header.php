<?php

//include the databaase connection the query
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

//includes/header.php
if(!isset($_SESSION)) session_start();

//check if user is loggedin for  protected pages
 function isLoggedIn(){
    return isset($_SESSION['user_id']);

 }
 function redirect($url){
    header("Location: .$url");
    exit();
 }

 ?>
 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title><?php echo $SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .sidebar{
            min-height:100vh;
            background-color:#2c3e50;

        }
        .sidebar a{
            color:white;
            text-decoration:none;
            padding:10px 15px;
            display:block;
            transition:0.3s;
        }
        .sidebar a:hover{
            background-color:#34495e;
        }
        .sidebar .nav-link.active{
            background-color:#3498db;
        }
        .main-content{
            padding:20px;
        }
        .stat-card{
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:white;
            border-radius:10px;
            padding:20px;
            margin-bottom:20px;
        }
        </style>
 </head>
 <body>
    
 <?php if(isset($_SESSION['user_id'])): ?>
    <div class="container-fluid">
        <div class="row">
            <!---sidebar--->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py -3">
                    <h5 class="text-white fs-4">KSUSDA MS</h5>
                    <small class="text-light">Kisii University</small>
 </div>
 <nav class="nav flex-column">
    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="members.php" class="nav-link"><i class="fas fa-users"></i> Members</a>
    <a href="attendance.php" class="nav-link"><i class="fas fa-calendar-check"></i> Attendance </a>
    <a href="offerings.php" class="nav-link"><i class="fas fa-hand-holding-usd"></i> Offerings</a>
    <a href="events.php" class="nav-link"><i class="fas fa-calendar-alt me-2"></i>events</a>
    <a href="admin_reports.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a>
    <a href="prayerRequestDashboard.php" class="nav-link"><i class="fas fa-person-praying"></i> Prayer Requests</a>
    <a href="add_announcement.php" class="nav-link"><i class="fas fa-bullhorn"></i> Announcement</a>
    <a href="admin_sermons.php" class="nav-link"><i class="fas fa-microphone"></i> sermons</a>
      <a href="add_leader.php" class="nav-link"><i class="fas fa-users"></i> Leaders</a>

     <!-- ADD THE MEMBERSHIP REQUESTS LINK HERE -->
        <a href="admin_approve_members.php" class="nav-link">
            <i class="fas fa-user-plus"></i> Membership Requests
            <!-- Add to your sidebar navigation -->
<a href="admin_backup.php" class="nav-link">
    <i class="fas fa-database"></i> Database Backup
</a>
            <?php
            // Only run database query if user is logged in
            if(isset($_SESSION['user_id'])) {
                try {
                    $count_query = "SELECT COUNT(*) as count FROM pending_members WHERE status = 'pending'";
                    $count_stmt = $db->prepare($count_query);
                    $count_stmt->execute();
                    $pending_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    if($pending_count > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $pending_count; ?></span>
                    <?php endif;
                } catch(Exception $e) {
                    // Table might not exist yet - ignore error
                }
            }
            
            ?>
        </a>
        <!-- END OF ADDED CODE -->
    
<li class="nav-item">
    <a class="nav-link" href="admin_prayers.php">
        <i class="fas fa-praying-hands"></i> Prayer Requests
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="prayer_request.php">
        <i class="fas fa-pen"></i> Submit Prayer
    </a>
</li>

    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>Logout</a>
 </nav>
 </div>
 
 <div class="col-md-10 main-content">
    <?php endif; ?>
 </body>
 </html>

 

