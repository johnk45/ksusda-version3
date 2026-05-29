<?php
// prayer_wall.php - Display all approved prayer requests
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle "I'm Praying" action
if(isset($_POST['praying']) && isset($_POST['prayer_id'])) {
    $prayer_id = $_POST['prayer_id'];
    $commenter_name = $_POST['commenter_name'] ?? 'Anonymous';
    
    // Update prayer count
    $query = "UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE prayer_id = :prayer_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':prayer_id' => $prayer_id]);
    
    // Add comment
    $query = "INSERT INTO prayer_comments (prayer_id, commenter_name, comment_text, is_praying) 
              VALUES (:prayer_id, :commenter_name, 'I am praying for this request!', 1)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':prayer_id' => $prayer_id,
        ':commenter_name' => $commenter_name
    ]);
    
    $pray_success = "Thank you for praying!";
}

// Handle comment submission
if(isset($_POST['submit_comment']) && isset($_POST['prayer_id'])) {
    $prayer_id = $_POST['prayer_id'];
    $commenter_name = $_POST['commenter_name'] ?: 'Anonymous';
    $comment_text = $_POST['comment_text'];
    
    $query = "INSERT INTO prayer_comments (prayer_id, commenter_name, comment_text) 
              VALUES (:prayer_id, :commenter_name, :comment_text)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':prayer_id' => $prayer_id,
        ':commenter_name' => $commenter_name,
        ':comment_text' => $comment_text
    ]);
    
    $comment_success = "Comment added successfully!";
}

// Get filter parameters
$category = $_GET['category'] ?? '';
$urgency = $_GET['urgency'] ?? '';
$answered = $_GET['answered'] ?? '';

// Build query
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM prayer_comments WHERE prayer_id = p.prayer_id) as comment_count
          FROM prayer_requests p 
          WHERE p.status = 'approved' AND p.is_public = 1";

if($category && $category != 'all') {
    $query .= " AND p.category = :category";
}
if($urgency && $urgency != 'all') {
    $query .= " AND p.urgency = :urgency";
}
if($answered == 'answered') {
    $query .= " AND p.is_answered = 1";
} elseif($answered == 'unanswered') {
    $query .= " AND p.is_answered = 0";
}

$query .= " ORDER BY 
            CASE p.urgency 
                WHEN 'Critical' THEN 1 
                WHEN 'High' THEN 2 
                WHEN 'Medium' THEN 3 
                WHEN 'Low' THEN 4 
            END, 
            p.created_at DESC";

$stmt = $db->prepare($query);
if($category && $category != 'all') {
    $stmt->bindParam(':category', $category);
}
if($urgency && $urgency != 'all') {
    $stmt->bindParam(':urgency', $urgency);
}
$stmt->execute();
$prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$cat_query = "SELECT DISTINCT category, COUNT(*) as count FROM prayer_requests WHERE status = 'approved' GROUP BY category";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Church Reports — Kisii University Seventh-day Adventist Church. Access our latest reports and publications.">
  <title>Reports — Kisii University SDA Church</title>
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
    <title>Prayer Wall - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {xswe
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }
        
        .prayer-header {
            background:#005A63;
            background-size:cover;
            color: white;
            padding: 20px 0;
            text-align: center;
            
        }
        
        .prayer-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: transform 0.2s;
            overflow: hidden;
        }
        
        .prayer-card:hover {
            transform: translateY(-3px);
        }
        
        .prayer-header-card {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .prayer-body-card {
            padding: 20px 25px;
        }
        
        .urgency-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .urgency-Critical { background: #dc3545; color: white; }
        .urgency-High { background: #fd7e14; color: white; }
        .urgency-Medium { background: #ffc107; color: #333; }
        .urgency-Low { background: #28a745; color: white; }
        
        .prayer-count {
            background: darkgreen;
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
        }
        
        .comment-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .filter-btn {
            margin: 5px;
            border-radius: 25px;
            padding: 8px 20px;
        }
        
        .btn-pray {
            background: linear-gradient(135deg, #0b1339 0%, #0f172a 100%);
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            color:#fff;
        }
        
        .btn-pray:hover {
            transform: scale(1.05);
        }
        
        .answer-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            color:white;
        }
        
        @media (max-width: 768px) {
            .prayer-header-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
 
    
    <!-- Header -->
    <div class="prayer-header">
        <div class="container">
            <i class="fas fa-hands-praying fa-4x mb-3"></i>
            <h1>Prayer Wall</h1>
            <p class="lead">"Pray without ceasing" - 1 Thessalonians 5:17</p>
            <a href="prayer_request.php" class="btn btn-light btn-lg mt-3">
                <i class="fas fa-pen"></i> Submit Prayer Request
            </a>
        </div>
    </div>
    
    <div class="container mt-4">
        <?php if(isset($pray_success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-heart"></i> <?php echo $pray_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($comment_success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-comment"></i> <?php echo $comment_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <h6><i class="fas fa-filter"></i> Filter Prayer Requests</h6>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['category']; ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo $cat['category']; ?> (<?php echo $cat['count']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="urgency" class="form-select">
                            <option value="all">All Urgency</option>
                            <option value="Critical" <?php echo $urgency == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="High" <?php echo $urgency == 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Medium" <?php echo $urgency == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Low" <?php echo $urgency == 'Low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="answered" class="form-select">
                            <option value="all">All Requests</option>
                            <option value="unanswered" <?php echo $answered == 'unanswered' ? 'selected' : ''; ?>>Unanswered</option>
                            <option value="answered" <?php echo $answered == 'answered' ? 'selected' : ''; ?>>Answered</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Prayer Requests -->
        <?php if(count($prayers) > 0): ?>
            <?php foreach($prayers as $prayer): ?>
            <div class="prayer-card" id="prayer-<?php echo $prayer['prayer_id']; ?>">
                <div class="prayer-header-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($prayer['prayer_title']); ?></h5>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($prayer['requester_name']); ?>
                                <i class="fas fa-calendar ms-2"></i> <?php echo date('M d, Y', strtotime($prayer['created_at'])); ?>
                            </small>
                        </div>
                        <div>
                            <span class="urgency-badge urgency-<?php echo $prayer['urgency']; ?> me-2">
                                <?php echo $prayer['urgency']; ?>
                            </span>
                            <span class="badge bg-secondary"><?php echo $prayer['category']; ?></span>
                            <?php if($prayer['is_answered']): ?>
                            <span class="answer-badge ms-2">
                                <i class="fas fa-check-circle"></i> Answered
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="prayer-body-card">
                    <p><?php echo nl2br(htmlspecialchars($prayer['prayer_content'])); ?></p>
                    
                    <?php if($prayer['answer_notes']): ?>
                    <div class="alert alert-success mt-2">
                        <strong><i class="fas fa-pray"></i> Prayer Answered:</strong>
                        <?php echo htmlspecialchars($prayer['answer_notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <div>
                            <span class="prayer-count">
                                <i class="fas fa-praying-hands"></i> <?php echo $prayer['prayer_count']; ?> people praying
                            </span>
                            <span class="ms-3 text-muted">
                                <i class="fas fa-comments"></i> <?php echo $prayer['comment_count']; ?> comments
                            </span>
                        </div>
                        <button class="btn btn-pray btn-sm" onclick="showPrayerForm(<?php echo $prayer['prayer_id']; ?>)">
                            <i class="fas fa-hands-praying"></i> I'm Praying
                        </button>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="comment-section mt-3">
                        <button class="btn btn-link p-0" onclick="toggleComments(<?php echo $prayer['prayer_id']; ?>)">
                            <i class="fas fa-comments"></i> Show Comments
                        </button>
                        
                        <div id="comments-<?php echo $prayer['prayer_id']; ?>" style="display: none;">
                            <?php
                            $comment_query = "SELECT * FROM prayer_comments WHERE prayer_id = :prayer_id ORDER BY created_at DESC LIMIT 10";
                            $comment_stmt = $db->prepare($comment_query);
                            $comment_stmt->execute([':prayer_id' => $prayer['prayer_id']]);
                            $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php foreach($comments as $comment): ?>
                            <div class="border-bottom py-2">
                                <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                <?php if($comment['is_praying']): ?>
                                <span class="badge bg-success">Praying</span>
                                <?php endif; ?>
                                <small class="text-muted"><?php echo date('M d, H:i', strtotime($comment['created_at'])); ?></small>
                                <p class="mb-0"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                            </div>
                            <?php endforeach; ?>
                            
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="prayer_id" value="<?php echo $prayer['prayer_id']; ?>">
                                <div class="input-group">
                                    <input type="text" name="commenter_name" class="form-control form-control-sm" placeholder="Your name" style="width: 120px;">
                                    <input type="text" name="comment_text" class="form-control form-control-sm" placeholder="Leave a comment or encouragement...">
                                    <button type="submit" name="submit_comment" class="btn btn-sm btn-primary">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-pray fa-3x mb-3"></i>
                <h5>No prayer requests found</h5>
                <p>Be the first to submit a prayer request!</p>
                <a href="prayer_request.php" class="btn btn-primary">Submit Prayer Request</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Praying Modal -->
    <div class="modal fade" id="prayerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">I'm Praying for This Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="prayer_id" id="prayer_id">
                        <div class="mb-3">
                            <label>Your Name (Optional)</label>
                            <input type="text" name="commenter_name" class="form-control" placeholder="Anonymous">
                        </div>
                        <p class="text-muted">Your prayer will be counted and you'll be added to the prayer list.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="praying" class="btn btn-primary">
                            <i class="fas fa-praying-hands"></i> Yes, I'm Praying
                        </button>
                    </div>
                </form>
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
        function showPrayerForm(prayerId) {
            document.getElementById('prayer_id').value = prayerId;
            new bootstrap.Modal(document.getElementById('prayerModal')).show();
        }
        
        function toggleComments(prayerId) {
            const commentsDiv = document.getElementById(`comments-${prayerId}`);
            if(commentsDiv.style.display === 'none') {
                commentsDiv.style.display = 'block';
            } else {
                commentsDiv.style.display = 'none';
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>