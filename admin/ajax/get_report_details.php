<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$report_id = $_GET['id'] ?? 0;

$query = "SELECT * FROM church_reports WHERE report_id = :report_id";
$stmt = $db->prepare($query);
$stmt->execute([':report_id' => $report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($report);
?>