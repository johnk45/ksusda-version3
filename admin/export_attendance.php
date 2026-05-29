<?php
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$date = $_GET['date'] ?? date('Y-m-d');
$service_type = $_GET['service_type'] ?? '';

$query = "SELECT a.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.membership_no, m.phone, m.email
          FROM attendance a
          JOIN members m ON a.member_id = m.member_id
          WHERE a.service_date = :service_date";

if($service_type) {
    $query .= " AND a.service_type = :service_type";
}

$query .= " ORDER BY m.first_name ASC";

$stmt = $db->prepare($query);
$params = [':service_date' => $date];
if($service_type) {
    $params[':service_type'] = $service_type;
}
$stmt->execute($params);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_' . $date . '.csv"');

$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, ['Membership No', 'Member Name', 'Phone', 'Email', 'Service Type', 'Status', 'Check-in Time']);

// Add data rows
foreach($attendance as $row) {
    fputcsv($output, [
        $row['membership_no'],
        $row['member_name'],
        $row['phone'],
        $row['email'],
        $row['service_type'],
        $row['status'],
        $row['check_in_time']
    ]);
}

fclose($output);
exit();
?>