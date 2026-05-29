
<?php
// reports.php - Fixed preview functionality
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';


$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$report_type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';

// Build query
$query = "SELECT r.*, u.username as uploaded_by_name 
          FROM church_reports r
          LEFT JOIN users u ON r.uploaded_by = u.user_id
          WHERE r.status = 'Published'";

if($report_type) {
    $query .= " AND r.report_type = :report_type";
}
if($search) {
    $query .= " AND (r.title LIKE :search OR r.description LIKE :search)";
}
if($year) {
    $query .= " AND YEAR(r.report_date) = :year";
}

$query .= " ORDER BY r.report_date DESC";

$stmt = $db->prepare($query);

$params = [];
if($report_type) $params[':report_type'] = $report_type;
if($search) $params[':search'] = "%$search%";
if($year) $params[':year'] = $year;

$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get report types for filter
$type_query = "SELECT DISTINCT report_type FROM church_reports WHERE status = 'Published'";
$type_stmt = $db->prepare($type_query);
$type_stmt->execute();
$report_types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get available years
$year_query = "SELECT DISTINCT YEAR(report_date) as year FROM church_reports WHERE status = 'Published' ORDER BY year DESC";
$year_stmt = $db->prepare($year_query);
$year_stmt->execute();
$years = $year_stmt->fetchAll(PDO::FETCH_COLUMN);

// Helper function to get file icon class
function getFileIconClass($file_type) {
    $icons = [
        'pdf' => 'fa-file-pdf text-danger',
        'doc' => 'fa-file-word text-primary',
        'docx' => 'fa-file-word text-primary',
        'xls' => 'fa-file-excel text-success',
        'xlsx' => 'fa-file-excel text-success',
        'ppt' => 'fa-file-powerpoint text-warning',
        'pptx' => 'fa-file-powerpoint text-warning',
        'jpg' => 'fa-file-image text-info',
        'jpeg' => 'fa-file-image text-info',
        'png' => 'fa-file-image text-info',
        'txt' => 'fa-file-alt text-secondary'
    ];
    return $icons[$file_type] ?? 'fa-file-alt';
}

// Get site URL for absolute paths
$site_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . '/church_ms/';
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
    <title>Church Reports - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8f9fa;
             font-family: 'Poppins', sans-serif;
      color: #222;
      line-height: 1.6;
      background: #f9f9f9;
      background-size: 400% 400%;
}
      


       

        
        .page-header {
            background: linear-gradient(rgba(22, 66, 91, 0.85), rgba(22, 66, 91, 0.9)), url('image/church\ audotorium.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            margin-bottom: 50px;
        }
        
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .report-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .report-meta {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .report-meta i {
            margin-right: 5px;
            color: #3498db;
        }
        
        .report-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #111d54 0%, #0f172a 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            transition: 0.3s;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 16, 58, 0.4);
            color: white;
        }
        
        .btn-preview {
            background: transparent;
            border: 2px solid #3498db;
            color: #3498db;
            padding: 8px 20px;
            border-radius: 25px;
            transition: 0.3s;
        }
        
        .btn-preview:hover {
            background: #3498db;
            color: white;
        }
        
        .filter-sidebar {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            position: sticky;
            top: 20px;
        }
        
        .filter-title {
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-right: 40px;
        }
        
        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #3498db;
        }
        
        .stats-badge {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
        }
        
        /* Preview Modal Styles */
        .modal-xl {
            max-width: 90%;
        }
        
        .preview-container {
            width: 100%;
            min-height: 70vh;
            background: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .preview-iframe {
            width: 100%;
            height: 70vh;
            border: none;
        }
        
        .preview-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        .preview-fallback {
            text-align: center;
            padding: 50px;
        }
        
        .preview-fallback i {
            font-size: 4rem;
            color: #95a5a6;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .header-content{
                flex-wrap:wrap;
            }
            .nav-links{
                order:3;
                width:100%;
                margin-top:20px;
                display:none;
                flex-direction:column;
                gap:15px;
            }
            .nav-links.active{
                display:flex;
            }
            .logo-text{font-size:1.5rem;}
            .mobile-menu-btn{
                display:block;
            }
            .hero h1{
                font-size:2.3rem;
            }
            .filter-sidebar {
                margin-bottom: 20px;
                position: static;
            }
            
            .modal-xl {
                max-width: 95%;
            }
            
            .preview-iframe {
                height: 50vh;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
 

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1>KSUSDA Church Reports</h1>
                <p class="lead mb-0">Access annual reports, financial statements, mission updates, and other important church documents</p>
            </div>
        </div>
    </div>
</section>

<div class="container mb-5">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <h5 class="filter-title"><i class="fas fa-filter"></i> Filter Reports</h5>
                
                <!-- Search -->
                <form method="GET" class="mb-4">
                    <div class="search-box">
                        <input type="text" name="search" class="form-control" placeholder="Search reports..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                
                <!-- Report Type Filter -->
                <div class="mb-4">
                    <h6><i class="fas fa-tag"></i> Report Type</h6>
                    <div class="list-group list-group-flush">
                        <a href="reports.php" class="list-group-item list-group-item-action <?php echo !$report_type ? 'active' : ''; ?>">
                            All Reports
                        </a>
                        <?php foreach($report_types as $type): ?>
                        <a href="?type=<?php echo urlencode($type); ?>" class="list-group-item list-group-item-action <?php echo $report_type == $type ? 'active' : ''; ?>">
                            <?php echo $type; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Year Filter -->
                <?php if(count($years) > 0): ?>
                <div class="mb-4">
                    <h6><i class="fas fa-calendar"></i> Year</h6>
                    <select class="form-control" onchange="window.location.href='?year=' + this.value + '<?php echo $report_type ? '&type=' . urlencode($report_type) : ''; ?>'">
                        <option value="">All Years</option>
                        <?php foreach($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="stats-badge">
                    <div class="stats-number"><?php echo count($reports); ?></div>
                    <div class="text-muted">Available Reports</div>
                </div>
                
                <!-- Clear Filters -->
                <?php if($report_type || $search || $year): ?>
                <a href="reports.php" class="btn btn-outline-secondary w-100 mt-3">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reports List -->
        <div class="col-lg-9">
            <?php if(count($reports) > 0): ?>
                <?php foreach($reports as $report): ?>
                <div class="report-card">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="file-icon">
                                <i class="fas <?php echo getFileIconClass($report['file_type']); ?> fa-3x"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="report-title"><?php echo htmlspecialchars($report['title']); ?></h5>
                            <div class="report-meta">
                                <i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($report['report_date'])); ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-tag"></i> <?php echo $report['report_type']; ?>
                                <span class="mx-2">•</span>
                                <i class="fas fa-file"></i> <?php echo strtoupper($report['file_type']); ?> (<?php echo round($report['file_size'] / 1024); ?> KB)
                                <span class="mx-2">•</span>
                                <i class="fas fa-eye"></i> <?php echo number_format($report['view_count']); ?> views
                                <span class="mx-2">•</span>
                                <i class="fas fa-download"></i> <?php echo number_format($report['download_count']); ?> downloads
                            </div>
                            <?php if($report['description']): ?>
                            <p class="report-description"><?php echo nl2br(htmlspecialchars(substr($report['description'], 0, 150))); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <button class="btn btn-preview btn-sm" onclick="previewReport(<?php echo (int)$report['report_id']; ?>, <?php echo json_encode($report['file_type']); ?>, <?php echo json_encode($report['file_path']); ?>)">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <a href="download_report.php?id=<?php echo $report['report_id']; ?>" class="btn btn-download btn-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h4>No Reports Found</h4>
                    <p class="text-muted">No reports match your search criteria. Try adjusting your filters.</p>
                    <a href="reports.php" class="btn btn-primary">View All Reports</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Report Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="previewContent" class="preview-container">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading preview...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="downloadFromPreview" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
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
      const mobileMenuBtn = document.getElementById('mobileMenuBtn');
      const navLinks = document.getElementById('navLinks');

      if (mobileMenuBtn && navLinks) {
          mobileMenuBtn.addEventListener('click', () => {
              navLinks.classList.toggle('active');
              mobileMenuBtn.innerHTML = navLinks.classList.contains('active')
                  ? '<i class="fas fa-times"></i>'
                  : '<i class="fas fa-bars"></i>';
          });
      }

  
  function previewReport(reportId, fileType, filePath) {
      document.getElementById('downloadFromPreview').href = 'download_report.php?id=' + reportId;
      const fullPath = new URL(filePath, window.location.href).href;
      let previewHtml = '';

      switch(fileType.toLowerCase()) {
          case 'pdf': {
              const pdfUrl = `${encodeURI(fullPath)}#toolbar=1&navpanes=1&scrollbar=1`;
              previewHtml = `
                  <iframe src="${pdfUrl}" class="preview-iframe" style="width:100%; height:75vh; border:none;"></iframe>
              `;
              break;
          }

          case 'jpg':
          case 'jpeg':
          case 'png':
          case 'gif':
          case 'webp':
              previewHtml = `
                  <div class="text-center p-4">
                      <img src="${encodeURI(fullPath)}" class="preview-image" style="max-width:100%; max-height:75vh;" alt="Report Image">
                  </div>
              `;
              break;

          case 'doc':
          case 'docx': {
              const googleViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(fullPath)}&embedded=true`;
              previewHtml = `
                  <iframe src="${googleViewerUrl}" class="preview-iframe" style="width:100%; height:75vh; border:none;"></iframe>
                  <div class="alert alert-info m-3">
                      <i class="fas fa-info-circle"></i>
                      For the best viewing experience, <a href="${fullPath}" target="_blank">open in a new tab</a> or download the file.
                  </div>
              `;
              break;
          }

          case 'xls':
          case 'xlsx': {
              const sheetsViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(fullPath)}&embedded=true`;
              previewHtml = `
                  <iframe src="${sheetsViewerUrl}" class="preview-iframe" style="width:100%; height:75vh; border:none;"></iframe>
                  <div class="alert alert-info m-3">
                      <i class="fas fa-info-circle"></i>
                      <a href="${fullPath}" target="_blank">Click here to open in a new tab</a> for better viewing.
                  </div>
              `;
              break;
          }

          case 'txt':
              fetch(encodeURI(fullPath))
                  .then(response => response.text())
                  .then(text => {
                      const textContent = document.getElementById('previewContent');
                      textContent.innerHTML = `
                          <pre style="padding:20px; background:#f5f5f5; margin:0; white-space:pre-wrap; word-wrap:break-word; max-height:75vh; overflow:auto;">${escapeHtml(text)}</pre>
                      `;
                  })
                  .catch(() => {
                      document.getElementById('previewContent').innerHTML = `
                          <div class="preview-fallback">
                              <i class="fas fa-exclamation-triangle"></i>
                              <h5>Unable to load preview</h5>
                              <p>There was an error loading this file.</p>
                              <a href="${encodeURI(fullPath)}" class="btn btn-primary" download>Download File</a>
                          </div>
                      `;
                  });
              new bootstrap.Modal(document.getElementById('previewModal')).show();
              return;

          default:
              previewHtml = `
                  <div class="preview-fallback">
                      <i class="fas fa-file-alt"></i>
                      <h5>Preview Not Available</h5>
                      <p>This file type (${fileType.toUpperCase()}) cannot be previewed in the browser.</p>
                      <a href="${encodeURI(fullPath)}" class="btn btn-primary" download>
                          <i class="fas fa-download"></i> Download File
                      </a>
                  </div>
              `;
      }

      document.getElementById('previewContent').innerHTML = previewHtml;
      new bootstrap.Modal(document.getElementById('previewModal')).show();
  }

  function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
  }
  </script>
</body>
</html>
