<?php
// ajax/get_notifications.php - Get notification count
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$count = 0;

// Count pending members
$stmt = $db->query("SELECT COUNT(*) FROM pending_members WHERE status = 'pending'");
$count += $stmt->fetchColumn();

// Count pending prayers
$stmt = $db->query("SELECT COUNT(*) FROM prayer_requests WHERE status = 'pending'");
$count += $stmt->fetchColumn();

// Count upcoming events this week
$stmt = $db->query("SELECT COUNT(*) FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'Planned'");
$count += $stmt->fetchColumn();

echo json_encode(['count' => $count]);
?>