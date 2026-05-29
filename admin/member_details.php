<?php
/**
 * member_details.php - View complete member profile
 * 
 * Shows: Personal info, attendance history, offerings, prayer requests, events
 * Accessed from members list, follow-up table, etc.
 */

require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($member_id <= 0) {
    $_SESSION['error'] = "Invalid member ID.";
    redirect('members.php');
}

// Fetch member details
$query = "SELECT * FROM members WHERE member_id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    $_SESSION['error'] = "Member not found.";
    redirect('members.php');
}

// Fetch attendance history (last 10 records)
$attendance_query = "SELECT * FROM attendance WHERE member_id = :id ORDER BY service_date DESC LIMIT 10";
$att_stmt = $db->prepare($attendance_query);
$att_stmt->execute([':id' => $member_id]);
$attendance_history = $att_stmt->fetchAll();

// Fetch offering history (last 10)
$offering_query = "SELECT * FROM offerings WHERE member_id = :id ORDER BY offering_date DESC LIMIT 10";
$off_stmt = $db->prepare($offering_query);
$off_stmt->execute([':id' => $member_id]);
$offering_history = $off_stmt->fetchAll();

// Fetch prayer requests
$prayer_query = "SELECT * FROM prayer_requests WHERE member_id = :id ORDER BY created_at DESC LIMIT 10";
$pray_stmt = $db->prepare($prayer_query);
$pray_stmt->execute([':id' => $member_id]);
$prayer_requests = $pray_stmt->fetchAll();

// Fetch registered events
$event_query = "SELECT e.*, er.registration_date, er.attendance_status 
                FROM event_registrations er
                JOIN events e ON er.event_id = e.event_id
                WHERE er.member_id = :id
                ORDER BY e.event_date DESC LIMIT 10";
$evt_stmt = $db->prepare($event_query);
$evt_stmt->execute([':id' => $member_id]);
$registered_events = $evt_stmt->fetchAll();

// Calculate summary stats
$total_offerings = array_sum(array_column($offering_history, 'amount'));
$last_attendance = !empty($attendance_history) ? $attendance_history[0]['service_date'] : 'Never';
$days_absent = ($last_attendance != 'Never') ? (new DateTime())->diff(new DateTime($last_attendance))->days : null;
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 16px;
        margin-bottom: 25px;
    }
    .info-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .info-card .card-header {
        background: #f8f9fa;
        padding: 12px 20px;
        font-weight: bold;
        border-bottom: 1px solid #e0e0e0;
    }
    .stat-badge {
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .attendance-absent { background: #dc3545; color: white; }
    .attendance-present { background: #28a745; color: white; }
    .attendance-late { background: #ffc107; color: #333; }
</style>

<div class="container-fluid">
    <!-- Back button -->
    <div class="mb-3">
        <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="edit_member.php?id=<?php echo $member_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Member
        </a>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><?php echo htmlspecialchars($member['title'] . ' ' . $member['first_name'] . ' ' . $member['last_name']); ?></h2>
                <p class="mb-1"><i class="fas fa-id-card"></i> Membership No: <?php echo $member['membership_no']; ?></p>
                <p><i class="fas fa-calendar-alt"></i> Joined: <?php echo date('F j, Y', strtotime($member['join_date'])); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-light text-dark fs-6 p-2">
                    Status: <?php echo $member['membership_status']; ?>
                </span>
                <?php if ($days_absent !== null && $days_absent > 21): ?>
                    <br><span class="badge bg-danger mt-2"><?php echo $days_absent; ?> days absent</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Personal Info -->
        <div class="col-md-4">
            <div class="info-card">
                <div class="card-header"><i class="fas fa-user"></i> Personal Information</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="40%">Gender:</th><td><?php echo $member['gender'] ?: 'Not specified'; ?></td></tr>
                        <tr><th>Date of Birth:</th><td><?php echo $member['date_of_birth'] ? date('F j, Y', strtotime($member['date_of_birth'])) : 'Not specified'; ?></td></tr>
                        <tr><th>Marital Status:</th><td><?php echo $member['marital_status'] ?: 'Not specified'; ?></td></tr>
                        <tr><th>Occupation:</th><td><?php echo htmlspecialchars($member['occupation']) ?: 'Not specified'; ?></td></tr>
                        <tr><th>Address:</th><td><?php echo nl2br(htmlspecialchars($member['address'])) ?: 'Not specified'; ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="info-card">
                <div class="card-header"><i class="fas fa-phone"></i> Contact</div>
                <div class="card-body">
                    <p><i class="fas fa-phone-alt"></i> <?php echo $member['phone'] ?: 'Not provided'; ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo $member['email'] ?: 'Not provided'; ?></p>
                    <?php if ($member['emergency_contact_name']): ?>
                        <hr><strong>Emergency Contact:</strong><br>
                        <?php echo htmlspecialchars($member['emergency_contact_name']); ?><br>
                        <?php echo $member['emergency_contact_phone']; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-card">
                <div class="card-header"><i class="fas fa-church"></i> Church Info</div>
                <div class="card-body">
                    <p><strong>Baptism Date:</strong> <?php echo $member['baptism_date'] ? date('F j, Y', strtotime($member['baptism_date'])) : 'Not recorded'; ?></p>
                    <p><strong>Join Date:</strong> <?php echo date('F j, Y', strtotime($member['join_date'])); ?></p>
                    <p><strong>Membership Status:</strong> <?php echo $member['membership_status']; ?></p>
                </div>
            </div>
        </div>

        <!-- Right Column: History -->
        <div class="col-md-8">
            <!-- Attendance History -->
            <div class="info-card">
                <div class="card-header"><i class="fas fa-calendar-check"></i> Recent Attendance</div>
                <div class="card-body">
                    <?php if (count($attendance_history) > 0): ?>
                        <table class="table table-sm">
                            <thead><tr><th>Date</th><th>Service</th><th>Status</th><th>Check-in Time</th></tr></thead>
                            <tbody>
                                <?php foreach ($attendance_history as $att): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($att['service_date'])); ?></td>
                                    <td><?php echo $att['service_type']; ?></td>
                                    <td><span class="badge <?php echo $att['status'] == 'Present' ? 'bg-success' : ($att['status'] == 'Late' ? 'bg-warning' : 'bg-danger'); ?>"><?php echo $att['status']; ?></span></td>
                                    <td><?php echo $att['check_in_time'] ?: '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($attendance_history) == 10): ?>
                            <a href="attendance.php?view=records&member_id=<?php echo $member_id; ?>" class="btn btn-sm btn-link">View all attendance</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">No attendance records found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Offerings -->
            <div class="info-card">
                <div class="card-header"><i class="fas fa-hand-holding-usd"></i> Recent Offerings</div>
                <div class="card-body">
                    <?php if (count($offering_history) > 0): ?>
                        <table class="table table-sm">
                            <thead><tr><th>Date</th><th>Type</th><th>Amount (KES)</th><th>Receipt No</th></tr></thead>
                            <tbody>
                                <?php foreach ($offering_history as $off): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($off['offering_date'])); ?></td>
                                    <td><?php echo $off['offering_type']; ?></td>
                                    <td><?php echo number_format($off['amount'], 2); ?></td>
                                    <td><?php echo $off['receipt_no']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p><strong>Total given:</strong> KES <?php echo number_format($total_offerings, 2); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No offering records found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prayer Requests -->
            <div class="info-card">
                <div class="card-header"><i class="fas fa-praying-hands"></i> Prayer Requests</div>
                <div class="card-body">
                    <?php if (count($prayer_requests) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($prayer_requests as $pray): ?>
                            <li class="list-group-item">
                                <strong><?php echo htmlspecialchars($pray['prayer_title']); ?></strong><br>
                                <small><?php echo date('M d, Y', strtotime($pray['created_at'])); ?></small>
                                <p class="mb-0 small"><?php echo htmlspecialchars(substr($pray['prayer_content'], 0, 100)); ?>...</p>
                                <span class="badge bg-<?php echo $pray['status'] == 'answered' ? 'success' : 'secondary'; ?>"><?php echo $pray['status']; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No prayer requests submitted.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Registered Events -->
            <div class="info-card">
                <div class="card-header"><i class="fas fa-calendar-alt"></i> Registered Events</div>
                <div class="card-body">
                    <?php if (count($registered_events) > 0): ?>
                        <table class="table table-sm">
                            <thead><tr><th>Event</th><th>Date</th><th>Attendance Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($registered_events as $evt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($evt['event_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($evt['event_date'])); ?></td>
                                    <td><span class="badge bg-<?php echo $evt['attendance_status'] == 'Checked In' ? 'success' : 'secondary'; ?>"><?php echo $evt['attendance_status']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No event registrations.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>