<?php
// preview_report.php - Fixed working version
require_once 'config/database.php';

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($report_id <= 0) {
    die('Invalid report ID');
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM church_reports WHERE report_id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die('Report not found');
}

// 🔥 BUILD CORRECT FILE PATH
$file_path = __DIR__ . '/uploads/reports/' . basename($report['file_name']);

if (file_exists($report['file_path'])) {
    $file_path = $report['file_path'];
}

// Check if file exists
$file_exists = file_exists($file_path);
$file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Update view count
$update = "UPDATE church_reports SET view_count = view_count + 1 WHERE report_id = :id";
$stmt = $db->prepare($update);
$stmt->execute([':id' => $report_id]);

// Build URL for browser
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/UPGRADED KSUSDA WEBSITE/';
$file_url = $base_url . 'uploads/reports/' . basename($report['file_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - <?php echo htmlspecialchars($report['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { margin: 0; padding: 0; background: #f5f5f5; }
        .preview-toolbar { background: #2c3e50; color: white; padding: 10px 20px; position: sticky; top: 0; z-index: 1000; }
        .preview-container { width: 100%; height: calc(100vh - 60px); }
        iframe { width: 100%; height: 100%; border: none; }
        img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
        .error-box { text-align: center; padding: 50px; background: white; margin: 50px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="preview-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <strong><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($report['title']); ?></strong>
                    <?php if (!$file_exists): ?>
                        <span class="badge bg-danger ms-2">File Missing</span>
                    <?php endif; ?>
                </div>
                <div class="col text-end">
                    <a href="download_report.php?id=<?php echo $report_id; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <button class="btn btn-sm btn-secondary" onclick="window.close()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="preview-container">
        <?php if (!$file_exists): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                <h4 class="mt-3">File Not Found</h4>
                <p>The requested file could not be found on the server.</p>
                <p><strong>Expected location:</strong> uploads/reports/<?php echo basename($report['file_name']); ?></p>
                <a href="upload_report.php" class="btn btn-primary mt-3">Upload New Report</a>
            </div>
        <?php elseif ($file_type == 'pdf'): ?>
            <iframe src="<?php echo $file_url; ?>#toolbar=1&navpanes=1"></iframe>
        <?php elseif (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <div style="text-align:center; padding:20px; background:#fff;">
                <img src="<?php echo $file_url; ?>" alt="<?php echo htmlspecialchars($report['title']); ?>">
            </div>
        <?php elseif (in_array($file_type, ['doc', 'docx', 'xls', 'xlsx'])): ?>
            <?php $google_url = 'https://docs.google.com/gview?url=' . urlencode($file_url) . '&embedded=true'; ?>
            <iframe src="<?php echo $google_url; ?>"></iframe>
        <?php else: ?>
            <div class="error-box">
                <i class="fas fa-file-alt fa-4x text-info"></i>
                <h4>Preview Not Available</h4>
                <p>This file type cannot be previewed.</p>
                <a href="download_report.php?id=<?php echo $report_id; ?>" class="btn btn-primary">Download File</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>