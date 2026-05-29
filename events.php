<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// frontend_events.php - Public page for members to view events
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

if(session_status() == PHP_SESSION_NONE){
    session_start();
}

$database = new Database();
$db = $database->getConnection();

// Handle event registration from frontend
$registration_success = '';
$registration_error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_event'])) {
    $event_id = $_POST['event_id'];
    $member_name = $_POST['member_name'];
    $member_email = $_POST['member_email'];
    $member_phone = $_POST['member_phone'];
    
    // Check if member exists in database
    $query = "SELECT member_id FROM members WHERE email = :email OR phone = :phone";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':email' => $member_email,
        ':phone' => $member_phone
    ]);
    $existing_member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($existing_member) {
        $member_id = $existing_member['member_id'];
        //check if member is already registered for this event
        $check_registration = "SELECT * FROM event_registrations
        WHERE event_id = :event_id AND member_id = :member_id";
        $check_stmt = $db->prepare($check_registration);
        $check_stmt->execute([
            ':event_id' => $event_id,
            ':member_id' => $member_id
        ]);
        if($check_stmt->rowCount()>0){
            $registration_error = "You have already registered for this event!";
        }else{
            //Register for event
            $query = "INSERT INTO event_registrations(event_id,member_id,registration)
            VALUES(:event_id, :member_id, CURDATE())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':event_id' => $event_id,
                ':member_id' => $member_id
            ]);
            $registration_success = "You have sucessfully registered.";
        }
    } else {
        // Auto-create member profile for new registrant
        $membership_no = 'SDA/KSU/' . date('Y') . '/' . rand(1000, 9999);
        $query = "INSERT INTO members (membership_no, first_name, last_name, email, phone, join_date) 
                  VALUES (:membership_no, :first_name, :last_name, :email, :phone, CURDATE())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':membership_no' => $membership_no,
            ':first_name' => $member_name,
            ':last_name' => '',
            ':email' => $member_email,
            ':phone' => $member_phone
        ]);
        $member_id = $db->lastInsertId();
    }
    
    // Check if already registered
    $query = "SELECT * FROM event_registrations WHERE event_id = :event_id AND member_id = :member_id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':event_id' => $event_id,
        ':member_id' => $member_id
    ]);
    
    if($stmt->rowCount() == 0) {
        // Register for event
        $query = "INSERT INTO event_registrations (event_id, member_id, registration_date) 
                  VALUES (:event_id, :member_id, CURDATE())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':event_id' => $event_id,
            ':member_id' => $member_id
        ]);
        $registration_success = "You have successfully registered for this event!";
    } else {
        $registration_error = "You are already registered for this event!";
    }
}

// Get current date
$current_date = date('Y-m-d');

// Get upcoming events (not cancelled, future dates)
$query = "SELECT e.*, 
          CONCAT(m.first_name, ' ', m.last_name) as organizer_name,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count
          FROM events e
          LEFT JOIN members m ON e.organizer_id = m.member_id
          WHERE e.event_date >= :current_date 
          AND e.status != 'Cancelled'
          AND e.status != 'Completed'
          ORDER BY e.event_date ASC";
$stmt = $db->prepare($query);
$stmt->execute([':current_date' => $current_date]);
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get past events (for archive)
$query = "SELECT e.*, 
          CONCAT(m.first_name, ' ', m.last_name) as organizer_name,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count
          FROM events e
          LEFT JOIN members m ON e.organizer_id = m.member_id
          WHERE e.event_date < :current_date 
          OR e.status = 'Completed'
          ORDER BY e.event_date DESC
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute([':current_date' => $current_date]);
$past_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured/upcoming event for hero section
$featured_event = !empty($upcoming_events) ? $upcoming_events[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Calendar & Events — Kisii University Seventh-day Adventist Church. Stay up to date with Sabbath services, prayer meetings, and special programs.">
  <title>Calendar & Events — Kisii University SDA Church</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛪</text></svg>">
  <style>
    .site-footer a {
      text-decoration: none;
    }
    .site-footer a:hover {
      text-decoration: underline;
    }
  </style>
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
    <title>Events - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family: 'Poppins', sans-serif;
        }
        :root{
            --primary: #3a7ca5;
            --primary-light: #5fa8d3;
            --secondary: #ff9b42;
            --dark: #0f172a;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #2e8b57;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        body {
            background: #f8f9fa;
        }
        .container{
            max-width: 100%;
            margin: 0 auto;
            padding: 0 clamp(15px, 3vw, 20px);
        }
        
        @media (min-width: 576px) {
            .container { padding: 0 20px; }
        }
        @media (min-width: 768px) {
            .container { padding: 0 30px; }
        }
        @media (min-width: 992px) {
            .container { max-width: 960px; padding: 0 40px; }
        }
        @media (min-width: 1200px) {
            .container { max-width: 1140px; }
        }
        @media (min-width: 1400px) {
            .container { max-width: 1320px; }
        }
        /* header styles*/
         header {
            background: linear-gradient(135deg, #098507 0%, #094612 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
}

        
        /* Navbar Styles */
        .logo-text {
            margin-left: 1rem;
            font-family: 'Poppins', sans-serif;
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            font-weight: 700;
            background: linear-gradient(135deg, #3a7ca5 0%, #ff9b42 50%, #2e8b57 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }
        .logo-text:hover {
            background: linear-gradient(135deg, #089cf1 0%, #f37406 50%, #07f068 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color:transparent;

        }

       

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a.active {
            color: var(--primary);
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 3px;
            background-color: var(--primary);
            bottom: -8px;
            left: 0;
            border-radius: 3px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            padding: 10px 22px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background-color: rgba(58, 124, 165, 0.05);
        }

        .btn-accent {
            background-color: var(--secondary);
            color: white;
        }

        .btn-accent:hover {
            background-color: #ff8c29;
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #fff;
            cursor: pointer;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(22,66,91,0.85) 0%, rgba(22,66,91,0.9) 100%),url('');
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
            margin-bottom:50px;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 4rem;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            
        }
        
        .hero-section h1 {
            font-size: clamp(1.8rem, 5vw, 3.2rem);
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .hero p {
            font-size: clamp(0.95rem, 2.5vw, 1.2rem);
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.5;
            max-width: 600px;
        }
        
        /* Event Cards */
        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .event-date-badge {
            background: linear-gradient(135deg, #060e34 0%, #0f172a 100%);
            color: white;
            padding: 15px;
            text-align: center;
            min-width: 100px;
        }
        
        .event-date-badge .day {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .event-date-badge .month {
            font-size: 1rem;
            text-transform: uppercase;
        }
        
        .event-details {
            padding: 20px;
        }
        
        .event-title {
            font-size: clamp(1.1rem, 2.5vw, 1.3rem);
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .event-meta {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .event-meta i {
            margin-right: 8px;
            color: #3498db;
        }
        
        .event-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #09133d 0%, #0b0421 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            transition: 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-view-details {
            background: transparent;
            border: 2px solid #3498db;
            color: #3498db;
            padding: 8px 20px;
            border-radius: 25px;
            transition: 0.3s;
        }
        
        .btn-view-details:hover {
            background: #3498db;
            color: white;
        }
        
        /* Featured Event */
        .featured-event {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 60px;
        }
        
        .featured-event-content {
            padding: 40px;
        }
        
        .featured-badge {
            background: #e74c3c;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .featured-title {
            font-size: clamp(1.5rem, 3.5vw, 2rem);
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .countdown-timer {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }
        
        .countdown-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
        }
        
        /* Section Title */
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .section-title .divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0 auto;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #0c184d 0%, #150623 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .btn-close-white {
            filter: brightness(0) invert(1);
        }
        
        /* Footer */
        .footer {
            background: #0f172a;
            color: #e2e8f0;
            padding: 40px 10px 10px;
            margin-top: 50px;
            font-family: 'Poppins', sans-serif;
        }
        .footer h3{
            color: #38bdf8;
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            margin-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        
        .footer p, .footer li {
            font-size: clamp(0.85rem, 2vw, 0.95rem);
        }
        .footer a {
            color: #ecf0f1;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .footer a:hover {
            color: #3498db;
        }
        
        /* Responsive - Tablet (768px and below) */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            .header-content {
                flex-wrap: wrap;
            }
            .nav-links {
                order: 3;
                width: 100%;
                margin-top: 20px;
                display: none;
                flex-direction: column;
                gap: 15px;
            }
            .nav-links.active {
                display: flex;
            }
            .header-actions {
                display: none;
            }
            .logo-text {
                font-size: 1.4rem;
            }
            .mobile-menu-btn {
                display: block;
            }
            .hero-section {
                padding: 40px 0;
            }
            .featured-event-content {
                padding: 25px;
            }
            .event-date-badge {
                min-width: 80px;
                padding: 10px;
            }
            .section-title {
                margin-bottom: 30px;
            }
            .alert-custom {
                min-width: 250px;
                right: 10px;
                left: 10px;
            }
        }
        
        /* Responsive - Small phones (576px and below) */
        @media (max-width: 576px) {
            .container {
                padding: 0 12px;
            }
            header {
                padding: 10px 0;
            }
            .header-content {
                padding: 10px 0;
            }
            .logo-text {
                font-size: 1.1rem;
            }
            .hero-section {
                padding: 30px 0;
                margin-bottom: 25px;
            }
            .hero-section .col-lg-4 i {
                font-size: 80px !important;
            }
            .hero-section .btn {
                display: block;
                width: 100%;
                margin-bottom: 10px;
            }
            .featured-event {
                margin-bottom: 30px;
            }
            .featured-event .row {
                flex-direction: column;
            }
            .featured-event-content {
                padding: 15px;
                order: -1;
            }
            .featured-event .col-md-5 {
                min-height: 200px !important;
            }
            .featured-title {
                font-size: 1.2rem;
            }
            .event-card {
                margin-bottom: 15px;
            }
            .event-date-badge {
                min-width: 70px;
                padding: 8px;
            }
            .event-date-badge .day {
                font-size: 1.5rem;
            }
            .event-details {
                padding: 12px;
            }
            .event-title {
                font-size: 1rem;
            }
            .event-meta {
                font-size: 0.8rem;
            }
            .event-description {
                font-size: 0.9rem;
            }
            .btn-register, .btn-view-details {
                padding: 5px 12px;
                font-size: 0.8rem;
            }
            .countdown-timer {
                padding: 12px;
                margin-top: 12px;
            }
            .countdown-number {
                font-size: 1.5rem;
            }
            .section-title {
                margin-bottom: 20px;
            }
            .footer {
                padding: 25px 10px 10px;
            }
            .footer .col-md-4 {
                margin-bottom: 15px;
            }
            .social-links a {
                font-size: 1rem;
            }
            .modal {
                padding-top: 40px !important;
            }
            .modal-dialog {
                margin-top: 20px;
            }
            .alert-custom {
                min-width: auto;
                width: 90vw;
                right: 5vw;
                left: auto;
                top: 10px;
                max-width: 300px;
            }
        }
        
        /* Responsive - Extra small phones (480px and below) */
        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            .logo-text {
                font-size: 1rem;
            }
            .hero-section {
                padding: 20px 0;
            }
            .hero-section h1 {
                font-size: 1.4rem;
                margin-bottom: 10px;
            }
            .hero p {
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            .featured-title {
                font-size: 1rem;
            }
            .event-title {
                font-size: 0.95rem;
            }
            .btn-register, .btn-view-details {
                padding: 4px 10px;
                font-size: 0.75rem;
            }
            .countdown-timer {
                padding: 8px;
            }
            .countdown-number {
                font-size: 1.2rem;
            }
            .footer h3 {
                font-size: 1rem;
            }
        }
        
        /* Responsive - Very small phones (320px and below) */
        @media (max-width: 320px) {
            .container {
                padding: 0 8px;
            }
            .logo-text {
                font-size: 0.9rem;
            }
            .hero-section .btn-lg {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
        }
        
        /* Alert Styles */
        .alert-custom {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        /* assets/css/style.css */
/* Add any additional custom styles here */

.event-card {
    transition: all 0.3s ease;
}

.btn-register {
    background: linear-gradient(135deg, #13246d 0%, #160229 100%);
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Loading spinner */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* Print styles */
@media print {
    .navbar, .footer, .btn-register, .btn-view-details {
        display: none;
    }
}
    </style>
</head>
<body>



<!-- Alert Messages -->
<?php if($registration_success): ?>
<div class="alert-custom alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo $registration_success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if($registration_error): ?>
<div class="alert-custom alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?php echo $registration_error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Hero Section -->
<section class="hero-section" id="home">
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1>Church Events & Gatherings</h1>
                <p class="lead mb-4">Join our community for worship, fellowship, and service.
                    Explore upcoming events and find opportunities to connect and grow in faith together.</p>
                <a href="#upcoming-events" class="btn btn-light btn-lg">
                    <i class="fas fa-calendar-alt"></i> View Events
                </a>
                <a href="#register" class="btn btn-outline-light btn-lg ms-2">
                    <i class="fas fa-user-plus"></i> Register Now
                </a>
            </div>
            <div class="col-lg-4 text-center">
                <i class="fas fa-church" style="font-size: 150px; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</section>

<div class="container mt-5">
    <!-- Featured Event -->
    <?php if($featured_event): ?>
    <div class="featured-event">
        <div class="row g-0">
            <div class="col-md-7 featured-event-content">
                <span class="featured-badge">
                    <i class="fas fa-star"></i> Featured Event
                </span>
                <h2 class="featured-title"><?php echo htmlspecialchars($featured_event['event_name']); ?></h2>
                <div class="event-meta">
                    <p><i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y', strtotime($featured_event['event_date'])); ?></p>
                    <?php if($featured_event['start_time']): ?>
                    <p><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($featured_event['start_time'])); ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($featured_event['venue']) ?: 'TBD'; ?></p>
                </div>
                <p class="event-description">
                    <?php echo nl2br(htmlspecialchars(substr($featured_event['description'], 0, 300))); ?>
                </p>
                <button class="btn btn-register" onclick="openRegistrationModal(<?php echo $featured_event['event_id']; ?>, '<?php echo addslashes($featured_event['event_name']); ?>')">
                    <i class="fas fa-user-plus"></i> Register Now
                </button>
                
                <div class="countdown-timer" id="countdown">
                    <h6>Event Starts In:</h6>
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="countdown-number" id="days">00</div>
                            <small>Days</small>
                        </div>
                        <div class="col-3">
                            <div class="countdown-number" id="hours">00</div>
                            <small>Hours</small>
                        </div>
                        <div class="col-3">
                            <div class="countdown-number" id="minutes">00</div>
                            <small>Minutes</small>
                        </div>
                        <div class="col-3">
                            <div class="countdown-number" id="seconds">00</div>
                            <small>Seconds</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5" style="background: linear-gradient(135deg, #091443 0%, #0f172a 100%); min-height: 300px;">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <i class="fas fa-calendar-check" style="font-size: 100px; color: rgba(255,255,255,0.8);"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Upcoming Events Section -->
    <section id="upcoming-events">
        <div class="section-title">
            <h2>Upcoming Events</h2>
            <div class="divider"></div>
            <p class="text-muted mt-3">Don't miss out on these exciting events happening soon</p>
        </div>
        
        <?php if(count($upcoming_events) > 0): ?>
        <div class="row">
            <?php foreach($upcoming_events as $index => $event): ?>
            <?php if($index == 0 && $featured_event) continue; // Skip featured event if it's the first one ?>
            <div class="col-md-6 col-lg-4">
                <div class="event-card">
                    <div class="event-date-badge">
                        <div class="day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                        <div class="month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                        <div class="year"><?php echo date('Y', strtotime($event['event_date'])); ?></div>
                    </div>
                    <div class="event-details">
                        <h5 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                        <div class="event-meta">
                            <i class="fas fa-clock"></i> <?php echo $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : 'Time TBD'; ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']) ?: 'Venue TBD'; ?><br>
                            <i class="fas fa-users"></i> <?php echo $event['registered_count']; ?> registered
                        </div>
                        <p class="event-description">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                        </p>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-register btn-sm" onclick="openRegistrationModal(<?php echo $event['event_id']; ?>, '<?php echo addslashes($event['event_name']); ?>')">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                            <button class="btn btn-view-details btn-sm" onclick="viewEventDetails(<?php echo $event['event_id']; ?>)">
                                <i class="fas fa-info-circle"></i> Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-calendar-times fa-3x mb-3"></i>
            <h5>No Upcoming Events at the Moment</h5>
            <p>Please check back later for updates on our church events and activities.</p>
        </div>
        <?php endif; ?>
    </section>
    
    <!-- Past Events Section -->
    <?php if(count($past_events) > 0): ?>
    <section id="past-events" class="mt-5">
        <div class="section-title">
            <h2>Past Events</h2>
            <div class="divider"></div>
            <p class="text-muted mt-3">Relive the memories of our previous gatherings</p>
        </div>
        
        <div class="row">
            <?php foreach($past_events as $event): ?>
            <div class="col-md-6 col-lg-4">
                <div class="event-card" style="opacity: 0.8;">
                    <div class="event-date-badge" style="background: #95a5a6;">
                        <div class="day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                        <div class="month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                        <div class="year"><?php echo date('Y', strtotime($event['event_date'])); ?></div>
                    </div>
                    <div class="event-details">
                        <h5 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                        <div class="event-meta">
                            <i class="fas fa-clock"></i> <?php echo $event['start_time'] ? date('g:i A', strtotime($event['start_time'])) : 'Time TBD'; ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']) ?: 'Venue TBD'; ?>
                        </div>
                        <p class="event-description">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                        </p>
                        <span class="badge bg-secondary">Completed</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- Registration Modal -->
<div class="modal fade" id="registrationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Register for Event
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Registering for: <strong id="modalEventName"></strong>
                    </div>
                    
                    <input type="hidden" name="event_id" id="modalEventId">
                    
                    <div class="mb-3">
                        <label for="member_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="member_name" name="member_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="member_email" name="member_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="member_phone" name="member_phone" required>
                    </div>
                    
                    <div class="form-text">
                        <i class="fas fa-lock"></i> Your information will be kept confidential and used only for event coordination.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="register_event" class="btn btn-primary">
                        <i class="fas fa-check"></i> Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- Event details loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

    //menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');

    mobileMenuBtn.addEventListener('click',()=>{
        navLinks.classList.toggle('active');
        mobileMenuBtn.innerHTML = navLinks.classList.contains('active')
          ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-bars"></i>';
    });
// Countdown timer for featured event
<?php if($featured_event): ?>
function updateCountdown() {
    const eventDate = new Date('<?php echo $featured_event['event_date'] . ' ' . ($featured_event['start_time'] ?: '00:00:00'); ?>').getTime();
    const now = new Date().getTime();
    const distance = eventDate - now;
    
    if(distance < 0) {
        document.getElementById('countdown').innerHTML = '<h6>Event has started!</h6>';
        return;
    }
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('days').innerHTML = String(days).padStart(2, '0');
    document.getElementById('hours').innerHTML = String(hours).padStart(2, '0');
    document.getElementById('minutes').innerHTML = String(minutes).padStart(2, '0');
    document.getElementById('seconds').innerHTML = String(seconds).padStart(2, '0');
}

setInterval(updateCountdown, 1000);
updateCountdown();
<?php endif; ?>

// Open registration modal
function openRegistrationModal(eventId, eventName) {
    document.getElementById('modalEventId').value = eventId;
    document.getElementById('modalEventName').innerText = eventName;
    new bootstrap.Modal(document.getElementById('registrationModal')).show();
}

// View event details
function viewEventDetails(eventId) {
    $.ajax({
        url: 'ajax/get_event_details.php',
        method: 'GET',
        data: { event_id: eventId },
        success: function(response) {
            $('#eventDetailsContent').html(response);
            new bootstrap.Modal(document.getElementById('eventDetailsModal')).show();
        }
    });
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    $('.alert-custom').fadeOut('slow');
}, 5000);

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
</script>
</body>
</html>