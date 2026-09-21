<?php
// prayer-request.php - Public form for membership requests
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Check if email already has pending request
function hasPendingRequest($db, $email) {
    $query = "SELECT request_id, status, requested_at FROM pending_members 
              WHERE email = :email AND status IN ('pending', 'approved')";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    // Get form data
    $title = $_POST['title'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $request_type = $_POST['request_type'] ?? 'new';
    $previous_church = trim($_POST['previous_church'] ?? '');
    $previous_membership_no = trim($_POST['previous_membership_no'] ?? '');
    $additional_info = trim($_POST['additional_info'] ?? '');
    
    // Validation
    $errors = [];
    if(empty($first_name)) $errors[] = "First name is required";
    if(empty($last_name)) $errors[] = "Last name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(empty($phone)) $errors[] = "Phone number is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check for duplicate/pending request
    $existing = hasPendingRequest($db, $email);
    if($existing) {
        if($existing['status'] == 'pending') {
            $errors[] = "You already have a pending membership request submitted on " . 
                        date('M d, Y', strtotime($existing['requested_at'])) . 
                        ". The church will review it soon.";
        } elseif($existing['status'] == 'approved') {
            $errors[] = "You are already a registered member. Please login to your account.";
        }
    }
    
    if(empty($errors)) {
        // Generate membership number (temporary, will be updated on approval)
        $temp_membership_no = 'PENDING/' . date('Ymd') . '/' . rand(1000, 9999);
        
        $query = "INSERT INTO pending_members (title, first_name, last_name, email, phone, 
                  gender, date_of_birth, address, occupation, request_type, previous_church, 
                  previous_membership_no, additional_info, status, requested_at) 
                  VALUES (:title, :first_name, :last_name, :email, :phone, :gender, :dob, 
                  :address, :occupation, :request_type, :prev_church, :prev_membership, 
                  :additional_info, 'pending', NOW())";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':title' => $title,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':phone' => $phone,
            ':gender' => $gender,
            ':dob' => $date_of_birth,
            ':address' => $address,
            ':occupation' => $occupation,
            ':request_type' => $request_type,
            ':prev_church' => $previous_church,
            ':prev_membership' => $previous_membership_no,
            ':additional_info' => $additional_info
        ]);
        
        if($result) {
            // Send notification email to church admin
            $admin_email = "admin@kisiiuniversitysdachurch.org";
            $subject = "New Membership Request - $first_name $last_name";
            $message = "A new membership request has been submitted.\n\n";
            $message .= "Name: $first_name $last_name\n";
            $message .= "Email: $email\n";
            $message .= "Phone: $phone\n";
            $message .= "Type: $request_type\n";
            $message .= "Please login to admin panel to review and approve.\n";
            $message .= "http://" . $_SERVER['HTTP_HOST'] . "/churchms/admin_approve_members.php";
            
            @mail($admin_email, $subject, $message);
            
            $success = "
                <div class='text-center'>
                    <i class='fas fa-check-circle fa-3x text-success mb-3'></i>
                    <h4>Thank You, $first_name!</h4>
                    <p>Your membership request has been submitted successfully.</p>
                    <p>The church administration will review your request and contact you within 3-5 business days.</p>
                    <p class='text-muted'>A confirmation has been sent to <strong>$email</strong></p>
                    <a href='index.php' class='btn btn-primary mt-3'>Return to Home</a>
                </div>
            ";
        } else {
            $error = "Unable to submit request. Please try again later.";
        }
    } else {
        $error = '<ul class="mb-0">' . implode('', array_map(function($e) { return "<li>$e</li>"; }, $errors)) . '</ul>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Contact Us — Kisii University Seventh-day Adventist Church. Reach out to us via phone, email, or visit our campus.">
  <title>Contact Us — Kisii University SDA Church</title>
  <link rel="stylesheet" href="css/styles.css">
  <meta name="description" content="Request Church Membership — Kisii University Seventh-day Adventist Church">
  <title>Request Membership — Kisii University SDA Church</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/kisiilogo.png">
  <link rel="stylesheet" href="../UPGRADED KSUSDA WEBSITE/css/styles.css">
  
  <style>
    :root {
      --primary-green: #0f5a35;
      --primary-green-dark: #0a3d24;
      --primary-green-light: #1a7a4a;
      --secondary-teal: #164f43;
      --accent-gold: #c4a747;
      --gray-light: #f8f9fa;
      --gray-medium: #e9ecef;
      --gray-dark: #6c757d;
    }

    
    /* Request Card */
    .request-card {
      max-width: 950px;
      margin: 7rem auto;
      background: white;
      border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
      overflow: hidden;
      transition: transform 0.3s;
    }

    .request-card:hover {
      transform: translateY(-5px);
    }

    .request-header {
      background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-teal) 100%);
      color: white;
      padding: 2rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .request-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1%, transparent 1%);
      background-size: 30px 30px;
      opacity: 0.3;
    }

    .request-header i {
      font-size: 3rem;
      margin-bottom: 1rem;
    }

    .request-header h2 {
      font-size: 1.8rem;
      margin-bottom: 0.5rem;
    }

    .request-header p {
      opacity: 0.9;
    }

    .request-body {
      padding: 2.5rem;
    }

    .form-label {
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--gray-dark);
      margin-bottom: 0.5rem;
    }

    .form-control, .form-select {
      border-radius: 12px;
      padding: 0.75rem 1rem;
      border: 1px solid #dee2e6;
      transition: all 0.3s;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary-green);
      box-shadow: 0 0 0 3px rgba(15, 90, 53, 0.1);
    }

    .btn-submit {
      background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-teal) 100%);
      border: none;
      padding: 0.9rem;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(15, 90, 53, 0.3);
    }

    .required:after {
      content: " *";
      color: #dc3545;
    }

    /* Info Box */
    .info-box {
      background: #e8f5e9;
      border-left: 4px solid var(--primary-green);
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
    }

    .info-box i {
      color: var(--primary-green);
      margin-right: 0.5rem;
    }

    /* Progress Steps */
    .progress-steps {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2rem;
      padding: 0 1rem;
    }

    .step {
      text-align: center;
      flex: 1;
      position: relative;
    }

    .step-number {
      width: 35px;
      height: 35px;
      background: var(--gray-medium);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: var(--gray-dark);
      margin-bottom: 0.5rem;
    }

    .step.active .step-number {
      background: var(--primary-green);
      color: white;
    }

    .step.completed .step-number {
      background: #28a745;
      color: white;
    }

    .step-label {
      font-size: 0.75rem;
      color: var(--gray-dark);
    }

    .step.active .step-label {
      color: var(--primary-green);
      font-weight: 600;
    }

    /* Footer */
    .site-footer {
      background: #1a1a1a;
      color: #999;
      padding: 2rem 0;
      margin-top: 3rem;
    }

    .footer-main {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .footer-info h4 {
      color: white;
      font-size: 1rem;
      margin-bottom: 0.5rem;
    }

    .footer-info a {
      color: #999;
      text-decoration: none;
    }

    .footer-info a:hover {
      color: var(--accent-gold);
    }

    .footer-social {
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
    }

    .footer-social a {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      color: #999;
    }

    .footer-bottom {
      text-align: center;
      padding-top: 1.5rem;
      margin-top: 1.5rem;
      border-top: 1px solid #333;
      font-size: 0.75rem;
    }

    @media (max-width: 768px) {
      .request-body {
        padding: 1.5rem;
      }
      .progress-steps {
        display: none;
      }
      .header-top {
        padding: 0.75rem 1rem;
      }
      .logo-text {
        font-size: 0.9rem;
      }
    }

    /* Loading Spinner */
    .loading-spinner {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.7);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 4px solid white;
      border-top-color: var(--primary-green);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
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
            <a href="ministries/health.html">Health Ministry</a>
            <a href="ministries/new-zion.html">New Zion</a>
            <a href="ministries/christ-messengers.html">Christ Messengers</a>
            <a href="ministries/first-fruits.html">First Fruits</a>
           
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
        <div class="menu-submenu"><a href="ministries/community-outreach.html">Community Outreach</a><a href="ministries/health.html">Health Ministry</a>
          <a href="ministries/new-zion.html">New Zion</a>
          <a href="ministries/christ-messengers.html">Christ Messengers</a>
          <a href="ministries/first-fruits.html">First Fruits</a>
</div>
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


<!-- Loading Spinner -->
<div id="loadingSpinner" class="loading-spinner">
  <div class="spinner"></div>
</div>

<div class="request-card">
  <div class="request-header">
    <i class="fas fa-user-plus fa-3x mb-3"></i>
    <h2>Request Church Membership</h2>
    <p>Join Kisii University SDA Church family</p>
  </div>
  <div class="request-body">

    <!-- Info Box -->
    <div class="info-box">
      <i class="fas fa-info-circle"></i>
      <strong>Why become a member?</strong> Membership gives you the opportunity to serve, grow spiritually, and be part of a loving community.
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
      <div class="step active" id="step1">
        <div class="step-number">1</div>
        <div class="step-label">Your Info</div>
      </div>
      <div class="step" id="step2">
        <div class="step-number">2</div>
        <div class="step-label">Details</div>
      </div>
      <div class="step" id="step3">
        <div class="step-number">3</div>
        <div class="step-label">Submit</div>
      </div>
    </div>

    <?php if($success): ?>
      <?php echo $success; ?>
    <?php else: ?>
      <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo $error; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" id="requestForm">
        <!-- Section 1: Basic Info -->
        <div id="section1">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label">Title</label>
              <select name="title" class="form-select">
                <option value="Mr.">Mr.</option>
                <option value="Mrs.">Mrs.</option>
                <option value="Miss">Miss</option>
                <option value="Dr.">Dr.</option>
                <option value="Prof.">Prof.</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label required">First Name</label>
              <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="col-md-5 mb-3">
              <label class="form-label required">Last Name</label>
              <input type="text" name="last_name" class="form-control" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Email Address</label>
              <input type="email" name="email" class="form-control" required>
              <small class="text-muted">We'll send confirmation to this email</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label required">Phone Number</label>
              <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10,13}" title="Enter a valid phone number (10-13 digits)">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-select">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Date of Birth</label>
              <input type="date" name="date_of_birth" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Occupation</label>
            <input type="text" name="occupation" class="form-control">
          </div>
        </div>

        <!-- Section 2: Church Details -->
        <div id="section2" style="display: none;">
          <div class="mb-3">
            <label class="form-label">Request Type</label>
            <select name="request_type" id="request_type" class="form-select">
              <option value="new">New Member (First time joining)</option>
              <option value="transfer">Transfer from another SDA Church</option>
            </select>
          </div>

          <div id="transfer_fields" style="display: none;">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Previous Church Name</label>
                <input type="text" name="previous_church" class="form-control" placeholder="e.g., Nairobi Central SDA Church">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Previous Membership No</label>
                <input type="text" name="previous_membership_no" class="form-control" placeholder="If known">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Additional Information</label>
            <textarea name="additional_info" class="form-control" rows="3" 
                      placeholder="Any additional information you'd like to share... (e.g., spiritual journey, baptism date, etc.)"></textarea>
          </div>
        </div>

        <!-- Section 3: Submit -->
        <div id="section3" style="display: none;">
          <div class="info-box">
            <i class="fas fa-check-circle"></i>
            <strong>Review Your Information</strong>
            <p class="mb-0 mt-2 small">Please review your information before submitting. Once submitted, our church admin will review your request within 3-5 business days.</p>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="confirm" required>
            <label class="form-check-label" for="confirm">
              I confirm that the information provided is accurate and I wish to become a member of Kisii University SDA Church
            </label>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="newsletter">
            <label class="form-check-label" for="newsletter">
              I'd like to receive church updates and newsletters via email
            </label>
          </div>

          <div class="alert alert-light border small">
            <i class="fas fa-lock text-success"></i> Your information is secure and will only be used for church purposes.
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="d-flex justify-content-between mt-4">
          <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;" onclick="changeStep(-1)">
            <i class="fas fa-arrow-left"></i> Previous
          </button>
          <button type="button" id="nextBtn" class="btn btn-primary" onclick="changeStep(1)">
            Next <i class="fas fa-arrow-right"></i>
          </button>
          <button type="submit" id="submitBtn" name="submit_request" class="btn btn-submit" style="display: none;">
            <i class="fas fa-paper-plane"></i> Submit Request
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<footer class="site-footer" role="contentinfo">
  <div class="footer-main">
    <div class="footer-info">
      <h4>Kisii University Seventh-day Adventist Church</h4>
      <p><i class="fas fa-map-marker-alt"></i> <a href="https://www.google.com/maps/search/Kisii+University+Kenya" target="_blank">Kisii University Campus, Kisii, Kenya</a><br>
      <i class="fas fa-phone"></i> <a href="tel:+254700000000">+254 700 000 000</a><br>
      <i class="fas fa-envelope"></i> <a href="mailto:info@kisiiuniversitysdachurch.org">info@kisiiuniversitysdachurch.org</a></p>
      <div class="footer-social">
        <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank"><i class="fab fa-facebook-f"></i> Facebook</a>
        <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank"><i class="fab fa-youtube"></i> YouTube</a>
        <a href="https://wa.me/254700000000" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
      </div>
    </div>
    <div class="footer-sda-logo">
      <img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-black.svg" alt="Seventh-day Adventist Church" style="height: 55px;">
    </div>
  </div>
  <div class="footer-bottom">
    <p>Copyright &copy; <?php echo date('Y'); ?> Kisii University Seventh-day Adventist Church.</p>
    <p><a href="#">Privacy Policy</a> &nbsp;|&nbsp; <a href="#">Terms of Use</a></p>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Multi-step form navigation
  let currentStep = 1;
  const totalSteps = 3;

  function updateSteps() {
    // Show/hide sections
    document.getElementById('section1').style.display = currentStep === 1 ? 'block' : 'none';
    document.getElementById('section2').style.display = currentStep === 2 ? 'block' : 'none';
    document.getElementById('section3').style.display = currentStep === 3 ? 'block' : 'none';
    
    // Update buttons
    document.getElementById('prevBtn').style.display = currentStep > 1 ? 'inline-flex' : 'none';
    document.getElementById('nextBtn').style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
    document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    
    // Update step indicators
    for (let i = 1; i <= totalSteps; i++) {
      const stepEl = document.getElementById(`step${i}`);
      if (stepEl) {
        stepEl.classList.remove('active', 'completed');
        if (i < currentStep) {
          stepEl.classList.add('completed');
        } else if (i === currentStep) {
          stepEl.classList.add('active');
        }
      }
    }
  }

  function changeStep(direction) {
    // Validate current step before proceeding
    if (direction === 1) {
      if (currentStep === 1) {
        const firstName = document.querySelector('input[name="first_name"]').value.trim();
        const lastName = document.querySelector('input[name="last_name"]').value.trim();
        const email = document.querySelector('input[name="email"]').value.trim();
        const phone = document.querySelector('input[name="phone"]').value.trim();
        
        if (!firstName || !lastName || !email || !phone) {
          alert('Please fill in all required fields (First Name, Last Name, Email, Phone)');
          return;
        }
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
          alert('Please enter a valid email address');
          return;
        }
      }
      if (currentStep === 2) {
        // Optional validation for step 2 (no required fields)
      }
    }
    
    currentStep += direction;
    if (currentStep < 1) currentStep = 1;
    if (currentStep > totalSteps) currentStep = totalSteps;
    updateSteps();
  }

  // Transfer fields toggle
  document.getElementById('request_type').addEventListener('change', function() {
    const transferFields = document.getElementById('transfer_fields');
    transferFields.style.display = this.value === 'transfer' ? 'block' : 'none';
  });

  // Loading spinner on form submit
  document.getElementById('requestForm').addEventListener('submit', function() {
    if (document.getElementById('confirm').checked) {
      document.getElementById('loadingSpinner').style.display = 'flex';
    }
  });

  // Initialize
  updateSteps();

  // Phone number formatting
  document.querySelector('input[name="phone"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) value = value.slice(0, 10);
    e.target.value = value;
  });
</script>

</body>
</html>