<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$report_id = $_POST['report_id'];
$title = $_POST['title'];
$report_type = $_POST['report_type'];
$description = $_POST['description'];
$report_date = $_POST['report_date'];
$status = $_POST['status'];

$query = "UPDATE church_reports SET 
          title = :title,
          report_type = :report_type,
          description = :description,
          report_date = :report_date,
          status = :status
          WHERE report_id = :report_id";

$stmt = $db->prepare($query);
$stmt->execute([
    ':title' => $title,
    ':report_type' => $report_type,
    ':description' => $description,
    ':report_date' => $report_date,
    ':status' => $status,
    ':report_id' => $report_id
]);

echo json_encode(['success' => true]);
?>