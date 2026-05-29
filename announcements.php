

<?php
// frontend_announcements.php - Public page for church members
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

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
  <meta name="description" content="Announcements — Kisii University Seventh-day Adventist Church. Stay informed about upcoming programs, events, and church news.">
  <title>Announcements — Kisii University SDA Church</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛪</text></svg>">
</head>
<body>

  <header class="site-header header-solid" role="banner">
    <div class="header-top">
      <div class="header-logo">
        <a href="index.html" aria-label="Kisii University SDA Church Home">
          <img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-white.svg" alt="Seventh-day Adventist Logo" class="sda-logo-img">
          <span class="logo-text">Kisii University Seventh-day Adventist Church</span>
        </a>
      </div>
      <div class="header-right-top">
        <a href="giving.html" class="giving-pill giving-pill-desktop">❤️ Giving</a>
        <div class="header-social-icons">
          <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
          <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
        </div>
      </div>
    </div>
    <nav class="header-nav" aria-label="Primary Navigation">
      <div class="desktop-nav">
        <div class="nav-item">
          <a href="about/index.html">About Us <span class="chevron-down">▾</span></a>
          <div class="desktop-dropdown">
            <a href="about/giving.html">Giving</a><a href="about/history.html">History</a><a href="about/in-the-news.html">In the News</a><a href="about/our-pastor.html">Our Pastor</a><a href="about/sabbath-services.html">Sabbath Services</a><a href="about/what-sda-believe.html">What SDAs Believe</a><a href="about/worship-with-us.html">Worship With Us</a><a href="about/potluck.html">Potluck &amp; Fellowship</a><a href="about/special-events.html">Special Events</a><a href="about/schools.html">Schools &amp; Education</a><a href="about/nearby-churches.html">Nearby SDA Churches</a>
          </div>
        </div>
        <div class="nav-item"><a href="events.html">Calendar</a></div>
        <div class="nav-item">
          <a href="ministries/index.html">Ministries <span class="chevron-down">▾</span></a>
          <div class="desktop-dropdown">
            <a href="ministries/community-outreach.html">Community Outreach</a><a href="ministries/health.html">Health Ministry</a><a href="ministries/youth-and-children.html">Youth &amp; Children</a><a href="ministries/bible-studies.html">Bible Studies</a><a href="ministries/evangelism.html">Evangelism</a>
            <a href="ministries/new-zion.html">New Zion</a>
            <a href="ministries/christ-messengers.html">Christ Messengers</a>
            <a href="ministries/first-fruits.html">First Fruits</a>
            <a href="ministries/the-sentinels.html">The Sentinels</a>
            <a href="ministries/hom.html">HOM — Hands On Mission</a>
          </div>
        </div>
        <div class="nav-item"><a href="giving.html">Online Giving</a></div>
        <div class="nav-item"><a href="contact.html">Contact Us</a></div>
      </div>
      <div class="nav-actions">
        <button class="nav-icon-btn hamburger-btn" aria-label="Open Menu" title="Menu"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
        <button class="nav-icon-btn header-search-btn" aria-label="Search" title="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
      </div>
      <div class="header-search-inline" role="search">
        <form action="search.html" method="get"><input type="text" name="s" placeholder="Search..." aria-label="Search"><button type="submit" aria-label="Search">🔍</button></form>
      </div>
    </nav>
  </header>
  <div class="menu-overlay" aria-hidden="true"></div>
  <nav class="slide-menu" role="navigation" aria-label="Mobile Navigation">
    <div class="menu-header"><button class="menu-close-btn" aria-label="Close Menu">&times;</button></div>
    <div class="menu-search"><form action="search.html" method="get"><input type="text" name="s" placeholder="Search..." aria-label="Search"><button type="submit" aria-label="Search">🔍</button></form></div>
    <div class="menu-giving"><a href="giving.html" class="giving-pill">❤️ Giving</a></div>
    <div class="menu-nav">
      <div class="menu-nav-item">
        <div class="menu-nav-link"><a href="about/index.html" style="color:inherit;text-decoration:none;flex:1;">About Us</a><button class="menu-chevron" aria-label="Expand About Us submenu">▼</button></div>
        <div class="menu-submenu"><a href="about/giving.html">Giving</a><a href="about/history.html">History</a><a href="about/in-the-news.html">In the News</a><a href="about/our-pastor.html">Our Pastor</a><a href="about/sabbath-services.html">Sabbath Services</a><a href="about/what-sda-believe.html">What SDAs Believe</a><a href="about/worship-with-us.html">Worship With Us</a><a href="about/potluck.html">Potluck &amp; Fellowship</a><a href="about/special-events.html">Special Events</a><a href="about/schools.html">Schools &amp; Education</a><a href="about/nearby-churches.html">Nearby SDA Churches</a></div>
      </div>
      <div class="menu-nav-item"><a href="events.html" class="menu-nav-link">Calendar</a></div>
      <div class="menu-nav-item">
        <div class="menu-nav-link"><a href="ministries/index.html" style="color:inherit;text-decoration:none;flex:1;">Ministries</a><button class="menu-chevron" aria-label="Expand Ministries submenu">▼</button></div>
        <div class="menu-submenu"><a href="ministries/community-outreach.html">Community Outreach</a><a href="ministries/health.html">Health Ministry</a><a href="ministries/youth-and-children.html">Youth &amp; Children</a><a href="ministries/bible-studies.html">Bible Studies</a><a href="ministries/evangelism.html">Evangelism</a>
          <a href="ministries/new-zion.html">New Zion</a>
          <a href="ministries/christ-messengers.html">Christ Messengers</a>
          <a href="ministries/first-fruits.html">First Fruits</a>
          <a href="ministries/the-sentinels.html">The Sentinels</a>
          <a href="ministries/hom.html">HOM — Hands On Mission</a></div>
      </div>
      <div class="menu-nav-item"><a href="giving.html" class="menu-nav-link">Online Giving</a></div>
      <div class="menu-nav-item"><a href="contact.html" class="menu-nav-link">Contact Us</a></div>
      <div class="menu-nav-item"><a href="announcements.html" class="menu-nav-link">Announcements</a></div>
      <div class="menu-nav-item"><a href="livestream.html" class="menu-nav-link">Livestream</a></div>
      <div class="menu-nav-item"><a href="bulletin.html" class="menu-nav-link">Bulletin</a></div>
      <div class="menu-nav-item"><a href="food.html" class="menu-nav-link">Food Assistance</a></div>
    </div>
    <div class="menu-footer">
      <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
      <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
    </div>
  </nav>

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


<footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-main">
        <div class="footer-info">
          <h4>Kisii University Seventh-day Adventist Church</h4>
          <p><a href="https://www.google.com/maps/search/Kisii+University+Kenya" target="_blank">Kisii University Campus, Kisii, Kenya</a><br><a href="tel:+254700000000">+254 700 000 000</a></p>
          <p style="margin-top: 0.25rem;"><a href="mailto:info@kisiiuniversitysdachurch.org">info@kisiiuniversitysdachurch.org</a></p>
          <div class="footer-social">
            <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Facebook</a>
            <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> YouTube</a>
          </div>
        </div>
        <div class="footer-sda-logo"><img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-black.svg" alt="Seventh-day Adventist Church" style="height: 55px;"></div>
      </div>
      <div class="footer-bottom">
        <p>Copyright &copy; 2026 Kisii University Seventh-day Adventist Church.</p>
        <p><a href="#">Privacy Policy</a> &nbsp;|&nbsp; <a href="#">Copyright Policy</a></p>
      </div>
    </div>
  </footer>
  <div class="cookie-banner" role="alert">
    <div class="cookie-inner">
      <p>This site uses cookies to provide you with the best web experience.</p>
      <div class="cookie-buttons"><button class="cookie-accept">Accept</button><button class="cookie-reject">Reject</button></div>
    </div>
  </div>
  <script src="js/main.js"></script>
</body>
</html>


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