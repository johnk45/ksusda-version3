<?php
// reports.php - Fixed preview functionality
require_once 'config/database.php';


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
      background: linear-gradient(-45deg, #e3f2fd, #cfd8dc, #bbdefb, #90caf9);
      background-size: 400% 400%;
        }
        header{
               background: linear-gradient(135deg, #001f3f 0%, #0f172a 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-content{
             display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
         
        }
.logo-text {
            margin-left:1rem;
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
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
            color: #f5f5f5;
        }

        .nav-links a.active {
            color: #f5f5f5;
        }
        .nav-links a:hover{color:#f9f9f9;}
        .nav-links a.active{
            color:#f9f9f9;
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
           .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #fff;
            cursor: pointer;
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
 <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">              
             <a class="logo-text" href="about.html">KSUSDA Church</a>
                </a>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                
                <nav class="nav-links" id="navLinks">
                    <a href="reports.php" class="active">Reports</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                    <a href="#Ministry.html">Ministries</a>
                    <a href="#about.html">About</a>
                    <a href="#contact32.html">Contact</a>
                </nav>
                
            </div>
        </div>
    </header>

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
                                <button class="btn btn-preview btn-sm" onclick="previewReport(<?php echo $report['report_id']; ?>, '<?php echo $report['file_type']; ?>', '<?php echo addslashes($report['file_path']); ?>')">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');

    mobileMenuBtn.addEventListener('click',()=>{
        navLinks.classList.toggle('active');
        mobileMenuBtn.innerHTML = navLinks.classList.contains('active')
        ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
    });

    
function previewReport(reportId, fileType, filePath) {
    // Update download link
    document.getElementById('downloadFromPreview').href = 'download_report.php?id=' + reportId;
    
    // Get the base URL for absolute paths
    const baseUrl = window.location.origin + window.location.pathname.replace('reports.php', '');
    const fullPath = filePath.startsWith('/') ? filePath : baseUrl + filePath;
    
    let previewHtml = '';
    
    // Handle different file types
    switch(fileType) {
        case 'pdf':
            previewHtml = `
                <iframe src="${fullPath}#toolbar=1&navpanes=1&scrollbar=1" 
                        class="preview-iframe" 
                        style="width:100%; height:75vh; border:none;">
                </iframe>
            `;
            break;
            
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
            previewHtml = `
                <div class="text-center p-4">
                    <img src="${fullPath}" class="preview-image" style="max-width:100%; max-height:75vh;" alt="Report Image">
                </div>
            `;
            break;
            
        case 'doc':
        case 'docx':
            // Try Google Docs Viewer for Word files
            const googleViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(fullPath)}&embedded=true`;
            previewHtml = `
                <iframe src="${googleViewerUrl}" 
                        class="preview-iframe" 
                        style="width:100%; height:75vh; border:none;">
                </iframe>
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle"></i> 
                    For the best viewing experience, 
                    <a href="${fullPath}" target="_blank">click here to open in a new tab</a> 
                    or download the file.
                </div>
            `;
            break;
            
        case 'xls':
        case 'xlsx':
            // Use Google Sheets Viewer for Excel files
            const sheetsViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(fullPath)}&embedded=true`;
            previewHtml = `
                <iframe src="${sheetsViewerUrl}" 
                        class="preview-iframe" 
                        style="width:100%; height:75vh; border:none;">
                </iframe>
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle"></i> 
                    <a href="${fullPath}" target="_blank">Click here to open in a new tab</a> 
                    for better viewing.
                </div>
            `;
            break;
            
        case 'txt':
            // Fetch and display text file content
            fetch(fullPath)
                .then(response => response.text())
                .then(text => {
                    const textContent = document.getElementById('previewContent');
                    textContent.innerHTML = `
                        <pre style="padding:20px; background:#f5f5f5; margin:0; white-space:pre-wrap; word-wrap:break-word; max-height:75vh; overflow:auto;">${escapeHtml(text)}</pre>
                    `;
                })
                .catch(error => {
                    document.getElementById('previewContent').innerHTML = `
                        <div class="preview-fallback">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h5>Unable to load preview</h5>
                            <p>There was an error loading this file.</p>
                            <a href="${fullPath}" class="btn btn-primary" download>Download File</a>
                        </div>
                    `;
                });
            return; // Exit early since we're using async fetch
            
        default:
            previewHtml = `
                <div class="preview-fallback">
                    <i class="fas fa-file-alt"></i>
                    <h5>Preview Not Available</h5>
                    <p>This file type (${fileType.toUpperCase()}) cannot be previewed in the browser.</p>
                    <a href="${fullPath}" class="btn btn-primary" download>
                        <i class="fas fa-download"></i> Download File
                    </a>
                </div>
            `;
    }
    
    if(previewHtml) {
        document.getElementById('previewContent').innerHTML = previewHtml;
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once 'includes/frontfooter.php'; ?>