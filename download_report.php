<?php
// download_report.php - Handle file downloads and track statistics
require_once '../UPGRADED KSUSDA WEBSITE/admin/config/database.php';

$database = new Database();
$db = $database->getConnection();

$report_id = $_GET['id'] ?? 0;

// Update download count
$query = "UPDATE church_reports SET download_count = download_count + 1, view_count = view_count + 1 WHERE report_id = :report_id";
$stmt = $db->prepare($query);
$stmt->execute([':report_id' => $report_id]);

// Get file path
$query = "SELECT file_path, file_name FROM church_reports WHERE report_id = :report_id";
$stmt = $db->prepare($query);
$stmt->execute([':report_id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if($report && file_exists($report['file_path'])) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($report['file_name']) . '"');
    header('Content-Length: ' . filesize($report['file_path']));
    readfile($report['file_path']);
    exit();
} else {
    header("Location: reports.php");
    exit();
}
?>