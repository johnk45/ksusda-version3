<?php
// preview_report.php - Dedicated preview page
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

$report_id = $_GET['id'] ?? 0;

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM church_reports WHERE report_id = :report_id";
$stmt = $db->prepare($query);
$stmt->execute([':report_id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$report) {
    die('Report not found');
}

// Update view count
$update = "UPDATE church_reports SET view_count = view_count + 1 WHERE report_id = :report_id";
$stmt = $db->prepare($update);
$stmt->execute([':report_id' => $report_id]);

$file_path = $report['file_path'];
$file_type = $report['file_type'];
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
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .preview-toolbar {
            background: #2c3e50;
            color: white;
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .preview-container {
            width: 100%;
            height: calc(100vh - 60px);
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="preview-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <strong><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($report['title']); ?></strong>
                </div>
                <div class="col text-end">
                    <a href="<?php echo $file_path; ?>" class="btn btn-sm btn-success" download>
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
        <?php
        if($file_type == 'pdf') {
            echo '<iframe src="' . $file_path . '#toolbar=1&navpanes=1"></iframe>';
        } elseif(in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo '<div style="text-align:center; padding:20px;"><img src="' . $file_path . '" alt="Report Image" style="max-width:100%;"></div>';
        } elseif(in_array($file_type, ['doc', 'docx', 'xls', 'xlsx'])) {
            $google_url = 'https://docs.google.com/gview?url=' . urlencode((isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . '/' . $file_path) . '&embedded=true';
            echo '<iframe src="' . $google_url . '"></iframe>';
        } else {
            echo '<div class="alert alert-warning m-5 text-center">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                    <h4>Preview Not Available</h4>
                    <p>This file type cannot be previewed. Please download the file to view it.</p>
                    <a href="' . $file_path . '" class="btn btn-primary" download>Download File</a>
                  </div>';
        }
        ?>
    </div>
</body>
</html>