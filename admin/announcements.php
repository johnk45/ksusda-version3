<?php
// frontend_announcements.php - Public page for church members
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get active announcements
$query = "SELECT * FROM announcements 
          WHERE status = 'Active' 
          AND announcement_date >= CURDATE()
          ORDER BY 
              CASE priority 
                  WHEN 'High' THEN 1 
                  WHEN 'Medium' THEN 2 
                  WHEN 'Low' THEN 3 
              END,
              announcement_date ASC,
              announcement_time ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group announcements by category for filtering
$categories = [];
foreach($announcements as $ann) {
    if(!in_array($ann['category'], $categories)) {
        $categories[] = $ann['category'];
    }
}

// Get upcoming announcements (next 7 days)
$query = "SELECT * FROM announcements 
          WHERE status = 'Active' 
          AND announcement_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          ORDER BY announcement_date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8f9fa;
        }
        html{
            scroll-behavior:smooth;
        }
        
        /* Header/Navbar */
        .navbar {
            background: #0f172a;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 15px 0;
        }
        
        .navbar-brand {
              font-family: 'Poppins', sans-serif;
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            font-weight: 700;
            background: linear-gradient(135deg, #3a7ca5 0%, #ff9b42 50%, #2e8b57 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .nav-item{
            color:#fff;
        }
        
        /* Hero Section */
        .hero-announcements {
            background: linear-gradient(135deg, #3a7ca5 0%, #ff9b42 50%, #2e8b57 100%),url('https://i.pinimg.com/736x/a9/2a/af/a92aaf7afdcc998ac1f4170d0ce4f3bf.jpg') no-repeat center center;
            background-size: cover;
            
            color: white;
            padding: 60px 0;
            text-align: center;
            opacity:0.9;
            min-height:50vh;
        }
        
        .hero-announcements h1 {
            font-size: clamp(1.5rem, 5vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .hero-announcements {
            padding: clamp(30px, 8vw, 60px) 0;
            min-height: clamp(35vh, 50vh, 60vh);
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .filter-btn {
            background: transparent;
            border: 2px solid #e0e0e0;
            padding: clamp(6px, 2vw, 8px) clamp(12px, 3vw, 20px);
            border-radius: 25px;
            margin: 3px 2px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: clamp(0.75rem, 2vw, 0.9rem);
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        /* Announcement Card - EXACTLY as specified */
        .announcement-card {
            background: white;
            border-radius: 16px;
            padding: clamp(15px, 4vw, 20px);
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid;
        }
        
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        /* Priority borders */
        .priority-High { border-left-color: #e74c3c; }
        .priority-Medium { border-left-color: #f39c12; }
        .priority-Low { border-left-color: #27ae60; }
        
        /* Category colors */
        .category-Choir { border-left-color: #e74c3c; }
        .category-Ministry { border-left-color: #3498db; }
        .category-Church-Board { border-left-color: #9b59b6; }
        .category-Elders { border-left-color: #1abc9c; }
        .category-Leaders { border-left-color: #f39c12; }
        .category-General { border-left-color: #95a5a6; }
        
        /* Info row - single line layout */
        .announcement-info {
            display: flex;
            flex-wrap: wrap;
            gap: clamp(8px, 3vw, 20px);
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }
        
        .info-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }
        
        .info-item i {
            width: 20px;
            color: #3498db;
        }
        
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .category-Choir-badge { background: #e74c3c; color: white; }
        .category-Ministry-badge { background: #3498db; color: white; }
        .category-Church-Board-badge { background: #9b59b6; color: white; }
        .category-Elders-badge { background: #1abc9c; color: white; }
        .category-Leaders-badge { background: #f39c12; color: white; }
        .category-General-badge { background: #95a5a6; color: white; }
        
        .announcement-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        
        .announcement-message {
            color: #555;
            line-height: 1.6;
            margin-top: 12px;
        }
        
        .ministry-tag {
            display: inline-block;
            background: #f8f9fa;
            padding: 2px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            color: #666;
            margin-left: 10px;
        }
        
        /* Upcoming Section */
        .upcoming-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: clamp(20px, 5vw, 40px);
            border-radius: 20px;
            margin-bottom: 40px;
        }
        
        .upcoming-item {
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 10px;
            backdrop-filter: blur(10px);
        }
        
        /* Responsive - Tablet (768px and below) */
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .announcement-info {
                flex-direction: column;
                gap: 8px;
            }
            
            .category-badge {
                font-size: 0.75rem;
                padding: 3px 10px;
            }
            
            .info-item {
                font-size: 0.85rem;
            }
            
            .announcement-title {
                font-size: 1.1rem;
            }
            
            .announcement-message {
                font-size: 0.95rem;
            }
            
            .filter-bar {
                padding: 10px 0;
            }
            
            .no-results {
                padding: 40px 15px;
            }
        }
        
        /* Responsive - Small phones (576px and below) */
        @media (max-width: 576px) {
            .container {
                padding-left: 12px;
                padding-right: 12px;
            }
            
            .navbar {
                padding: 10px 0 !important;
            }
            
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .hero-announcements {
                padding: 25px 0 !important;
                min-height: 35vh !important;
            }
            
            .hero-announcements p {
                font-size: 0.9rem;
            }
            
            .announcement-card {
                padding: 12px;
                margin-bottom: 15px;
            }
            
            .announcement-title {
                font-size: 1rem;
                margin-bottom: 10px;
            }
            
            .announcement-message {
                font-size: 0.9rem;
                margin-top: 10px;
            }
            
            .category-badge {
                font-size: 0.7rem;
                padding: 2px 8px;
            }
            
            .info-item {
                font-size: 0.8rem;
                gap: 5px;
            }
            
            .info-item i {
                width: 16px;
            }
            
            .ministry-tag {
                font-size: 0.7rem;
                padding: 2px 8px;
                margin-left: 5px;
            }
            
            .upcoming-section {
                padding: 15px !important;
                margin-bottom: 25px;
            }
            
            .upcoming-section h3 {
                font-size: 1.1rem;
                margin-bottom: 12px;
            }
            
            .upcoming-item {
                padding: 10px 12px;
                font-size: 0.85rem;
                margin-bottom: 8px;
            }
            
            .filter-bar {
                padding: 8px 0;
            }
            
            .d-flex.flex-wrap {
                justify-content: flex-start !important;
            }
            
            .filter-btn {
                padding: 5px 10px;
                font-size: 0.75rem;
                margin: 2px;
            }
            
            .footer {
                padding: 40px 15px 15px !important;
            }
            
            .no-results {
                padding: 30px 12px;
            }
            
            .no-results i {
                font-size: 3rem;
            }
        }
        
        /* Responsive - Extra small phones (480px and below) */
        @media (max-width: 480px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .announcement-card {
                padding: 10px;
            }
            
            .announcement-title {
                font-size: 0.95rem;
            }
            
            .announcement-message {
                font-size: 0.85rem;
            }
            
            .filter-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
        
        /* No results */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
        }
        
        /* Footer */
        .footer {
            background: #0f172a;
            color: #e2e8f0;
            padding: 60px 20px 20px;
            font-family: 'Poppins', sans-serif;
        }
        
        .footer h5 {
            color: #38bdf8;
            font-size: clamp(1rem, 3vw, 1.2rem);
        }
        
        .footer p, .footer ul li {
            font-size: clamp(0.85rem, 2vw, 0.95rem);
        }
        
        .navbar-toggler .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="frontend_events.php">
        KSUSDA Church
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto ">
                <li class="nav-item"><a class="nav-link" href="frontend_events.php" style="color:white">Events</a></li>
                <li class="nav-item"><a class="nav-link active" href="announcements.php" style="color:white">Announcements</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php" style="color:white">Admin</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-announcements">
    <div class="container">
        <h1><i class="fas fa-bullhorn"></i> Church Announcements</h1>
        <p class="lead mb-0">Get the latest updates and information from our church.</p>
        <p class="text-muted">Stay connected with upcoming events, ministry news, and important notices.</p>
    </div>
</section>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-center">
            <button class="filter-btn active" data-filter="all">All</button>
            <?php foreach($categories as $cat): ?>
            <button class="filter-btn" data-filter="<?php echo $cat; ?>">
                <?php echo $cat; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container mt-4">
    <!-- Upcoming Events Section -->
    <?php if(count($upcoming) > 0): ?>
    <div class="upcoming-section">
        <h3 class="mb-3"><i class="fas fa-calendar-week"></i> This Week's Announcements</h3>
        <?php foreach($upcoming as $item): ?>
        <div class="upcoming-item">
            <strong><?php echo date('l, M j', strtotime($item['announcement_date'])); ?></strong> - 
            <?php echo htmlspecialchars($item['title']); ?>
            <?php if($item['announcement_time']): ?>
                at <?php echo date('g:i A', strtotime($item['announcement_time'])); ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Announcements List -->
    <div id="announcementsContainer">
        <?php if(count($announcements) > 0): ?>
            <?php foreach($announcements as $ann): ?>
            <div class="announcement-card priority-<?php echo $ann['priority']; ?> category-<?php echo $ann['category']; ?>" 
                 data-category="<?php echo $ann['category']; ?>">
                
                <!-- EXACT FORMAT: Category, Date, Time, Venue, Organizer in one line -->
                <div class="announcement-info">
                    <span class="category-badge category-<?php echo $ann['category']; ?>-badge">
                        <i class="fas <?php 
                            echo $ann['category'] == 'Choir' ? 'fa-music' : 
                                ($ann['category'] == 'Ministry' ? 'fa-hands-helping' :
                                ($ann['category'] == 'Church Board' ? 'fa-gavel' :
                                ($ann['category'] == 'Elders' ? 'fa-user-tie' :
                                ($ann['category'] == 'Leaders' ? 'fa-crown' : 'fa-church')))); ?>">
                        </i>
                        <?php echo $ann['category']; ?>
                    </span>
                    
                    <span class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('l, M j, Y', strtotime($ann['announcement_date'])); ?>
                    </span>
                    
                    <?php if($ann['announcement_time']): ?>
                    <span class="info-item">
                        <i class="fas fa-clock"></i>
                        <?php echo date('g:i A', strtotime($ann['announcement_time'])); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if($ann['venue']): ?>
                    <span class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($ann['venue']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if($ann['organizer']): ?>
                    <span class="info-item">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($ann['organizer']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if($ann['ministry']): ?>
                    <span class="ministry-tag">
                        <i class="fas fa-users"></i> <?php echo htmlspecialchars($ann['ministry']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Title -->
                <div class="announcement-title">
                    <?php echo htmlspecialchars($ann['title']); ?>
                </div>
                
                <!-- Message -->
                <div class="announcement-message">
                    <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                <h4>No Announcements at the Moment</h4>
                <p class="text-muted">Please check back later for updates from the church.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <h5><i class="fas fa-church"></i> Kisii University SDA Church</h5>
                <p>Growing together in faith, love, and service to God and humanity.</p>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="frontend_events.php" class="text-white">Events</a></li>
                    <li><a href="frontend_announcements.php" class="text-white">Announcements</a></li>
                </ul>
            </div>
            <div class="col-12 col-sm-6 col-md-4 mb-4">
                <h5>Contact</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt"></i> Kisii University, Kisii, Kenya</li>
                    <li><i class="fas fa-envelope"></i> info@kisiiuniversitysdachurch.org</li>
                </ul>
            </div>
        </div>
        <hr class="bg-light">
        <div class="text-center">
            <p class="mb-0">&copy; 2025 - <?php echo date('Y'); ?> Kisii University SDA Church. All rights reserved.</p>
        </div>
    </div>
</footer>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Filter functionality
$(document).ready(function() {
    $('.filter-btn').click(function() {
        var filter = $(this).data('filter');
        
        // Update active button
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        // Filter announcements
        if(filter === 'all') {
            $('.announcement-card').show();
        } else {
            $('.announcement-card').hide();
            $('.announcement-card[data-category="' + filter + '"]').show();
        }
    });
});
</script>