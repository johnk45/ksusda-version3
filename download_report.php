<?php
// download_report.php - Fixed working version
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

// Clear any output buffers that might interfere
if (ob_get_level()) ob_end_clean();

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($report_id <= 0) {
    die('Invalid report ID');
}

$database = new Database();
$db = $database->getConnection();

// Get file info from database
$query = "SELECT * FROM church_reports WHERE report_id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die('Report not found in database');
}

// 🔥 BUILD THE CORRECT FILE PATH
$file_path = __DIR__ . '/uploads/reports/' . basename($report['file_name']);

// Alternative if you store full path in database
if (file_exists($report['file_path'])) {
    $file_path = $report['file_path'];
}

// 🔥 CHECK IF FILE EXISTS
if (!file_exists($file_path)) {
    die('File not found on server: ' . $file_path);
}

// 🔥 CHECK IF FILE IS READABLE
if (!is_readable($file_path)) {
    die('File is not readable. Please check file permissions.');
}

// Get file extension
$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set correct content type
$content_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$content_type = $content_types[$ext] ?? 'application/octet-stream';

// 🔥 FORCE DOWNLOAD HEADERS
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// 🔥 SEND FILE
readfile($file_path);
exit();
?>