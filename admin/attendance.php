<?php
/**
 * attendance.php - Complete Attendance Management System
 * 
 * Features:
 * - Mark individual attendance
 * - Bulk attendance marking
 * - View filtered attendance records
 * - Follow-up list (members absent for X+ days, dynamic threshold)
 * - Attendance statistics & charts
 * - Recent records with "Show More" toggle
 * 
 * @author Kisii University SDA Church
 * @version 2.0
 */

require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// ============================================
// CONFIGURATION
// ============================================

// Get dynamic absence threshold from URL (default: 3 days)
$threshold_days = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 3;
if ($threshold_days < 1) $threshold_days = 3; // safety
define('ABSENT_THRESHOLD', $threshold_days);

// Determine which view to show (mark, records, or absent)
$view = $_GET['view'] ?? 'mark';

// For recent attendance "Show More" logic
$recent_limit = isset($_GET['recent_limit']) ? (int)$_GET['recent_limit'] : 20;
if (!in_array($recent_limit, [20, 50, 100])) $recent_limit = 20;

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get members who need follow-up (absent for >= threshold days)
 * 
 * @param PDO $db Database connection
 * @param int $days Minimum days absent
 * @return array List of members with last attendance and days absent
 */
function getAbsentMembers($db, $days) {
    $sql = "SELECT m.member_id, m.membership_no, m.first_name, m.last_name, 
                   m.phone, m.email, m.address,
                   MAX(a.service_date) as last_attendance,
                   DATEDIFF(NOW(), MAX(a.service_date)) as days_absent,
                   COUNT(a.attendance_id) as total_attendance
            FROM members m
            LEFT JOIN attendance a ON m.member_id = a.member_id
            WHERE m.membership_status = 'Active'
            GROUP BY m.member_id
            HAVING last_attendance IS NULL OR days_absent >= :days
            ORDER BY days_absent DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':days' => $days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================
// POST HANDLERS (Attendance Operations)
// ============================================

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Individual attendance marking
    if (isset($_POST['mark_attendance'])) {
        $member_id = $_POST['member_id'];
        $service_date = $_POST['service_date'];
        $service_type = $_POST['service_type'];
        $status = $_POST['status'];
        $check_in_time = $_POST['check_in_time'] ?: date('H:i:s');
        
        // Prevent duplicate
        $check_query = "SELECT * FROM attendance WHERE member_id = :member_id 
                        AND service_date = :service_date AND service_type = :service_type";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([
            ':member_id' => $member_id,
            ':service_date' => $service_date,
            ':service_type' => $service_type
        ]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Attendance already marked for this member on this service!";
        } else {
            $insert = "INSERT INTO attendance (member_id, service_date, service_type, status, check_in_time, created_by)
                       VALUES (:member_id, :service_date, :service_type, :status, :check_in_time, :created_by)";
            $stmt = $db->prepare($insert);
            $stmt->execute([
                ':member_id' => $member_id,
                ':service_date' => $service_date,
                ':service_type' => $service_type,
                ':status' => $status,
                ':check_in_time' => $check_in_time,
                ':created_by' => $_SESSION['user_id']
            ]);
            $success = "Attendance marked successfully!";
        }
    }
    
    // Bulk attendance
    if (isset($_POST['bulk_attendance'])) {
        $service_date = $_POST['service_date'];
        $service_type = $_POST['service_type'];
        $members = $_POST['members'] ?? [];
        $count = 0;
        
        foreach ($members as $member_id => $status) {
            if ($status != 'absent') {
                $check = "SELECT * FROM attendance WHERE member_id = :mid 
                          AND service_date = :sdate AND service_type = :stype";
                $check_stmt = $db->prepare($check);
                $check_stmt->execute([
                    ':mid' => $member_id,
                    ':sdate' => $service_date,
                    ':stype' => $service_type
                ]);
                
                if ($check_stmt->rowCount() == 0) {
                    $insert = "INSERT INTO attendance (member_id, service_date, service_type, status, check_in_time, created_by)
                               VALUES (:mid, :sdate, :stype, :status, NOW(), :user)";
                    $stmt = $db->prepare($insert);
                    $stmt->execute([
                        ':mid' => $member_id,
                        ':sdate' => $service_date,
                        ':stype' => $service_type,
                        ':status' => $status,
                        ':user' => $_SESSION['user_id']
                    ]);
                    $count++;
                }
            }
        }
        $success = "Bulk attendance marked for $count members!";
    }
    
    // Update existing attendance record
    if (isset($_POST['update_attendance'])) {
        $attendance_id = $_POST['attendance_id'];
        $status = $_POST['status'];
        
        $update = "UPDATE attendance SET status = :status WHERE attendance_id = :id";
        $stmt = $db->prepare($update);
        $stmt->execute([':status' => $status, ':id' => $attendance_id]);
        $success = "Attendance updated successfully!";
    }
}

// ============================================
// FETCH DATA FOR DISPLAY
// ============================================

// --- Statistics ---
$today = date('Y-m-d');

// Today's attendance
$stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE service_date = :today AND status = 'Present'");
$stmt->execute([':today' => $today]);
$today_attendance = $stmt->fetchColumn();

// This week's attendance
$stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE WEEK(service_date) = WEEK(CURDATE()) AND status = 'Present'");
$stmt->execute();
$weekly_attendance = $stmt->fetchColumn();

// This month's attendance
$stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE MONTH(service_date) = MONTH(CURDATE()) 
                      AND YEAR(service_date) = YEAR(CURDATE()) AND status = 'Present'");
$stmt->execute();
$monthly_attendance = $stmt->fetchColumn();

// Total active members
$stmt = $db->prepare("SELECT COUNT(*) FROM members WHERE membership_status = 'Active'");
$stmt->execute();
$total_members = $stmt->fetchColumn();

// Service statistics (current month)
$stats_query = "SELECT service_type, COUNT(*) as total, 
                AVG(CASE WHEN status = 'Present' THEN 100 ELSE 0 END) as avg_attendance
                FROM attendance 
                WHERE MONTH(service_date) = MONTH(CURDATE())
                GROUP BY service_type";
$service_stats = $db->query($stats_query)->fetchAll(PDO::FETCH_ASSOC);

// --- Active members list for dropdowns ---
$members_list = $db->query("SELECT member_id, membership_no, CONCAT(first_name, ' ', last_name) as name 
                            FROM members WHERE membership_status = 'Active' ORDER BY first_name")->fetchAll();

// --- Filtered attendance records (by date and service type) ---
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_service = $_GET['service_type'] ?? '';

$attendance_query = "SELECT a.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.membership_no
                     FROM attendance a
                     JOIN members m ON a.member_id = m.member_id
                     WHERE a.service_date = :date";
if ($filter_service) {
    $attendance_query .= " AND a.service_type = :service_type";
}
$attendance_query .= " ORDER BY m.first_name ASC";
$attendance_stmt = $db->prepare($attendance_query);
$params = [':date' => $filter_date];
if ($filter_service) $params[':service_type'] = $filter_service;
$attendance_stmt->execute($params);
$filtered_attendance = $attendance_stmt->fetchAll();

// --- Recent attendance (with Show More) ---
$recent_sql = "SELECT a.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.membership_no
               FROM attendance a
               JOIN members m ON a.member_id = m.member_id
               ORDER BY a.service_date DESC, a.created_at DESC
               LIMIT :limit";
$recent_stmt = $db->prepare($recent_sql);
$recent_stmt->bindParam(':limit', $recent_limit, PDO::PARAM_INT);
$recent_stmt->execute();
$recent_attendance = $recent_stmt->fetchAll();

// --- Follow-up members (using dynamic threshold) ---
$absent_members = [];
if ($view == 'absent') {
    $absent_members = getAbsentMembers($db, ABSENT_THRESHOLD);
}

// Badge count for Follow-up tab (same threshold)
$badge_sql = "SELECT COUNT(*) as cnt FROM (
    SELECT m.member_id
    FROM members m
    LEFT JOIN attendance a ON m.member_id = a.member_id
    WHERE m.membership_status = 'Active'
    GROUP BY m.member_id
    HAVING MAX(a.service_date) IS NULL OR DATEDIFF(NOW(), MAX(a.service_date)) >= :days
) AS sub";
$badge_stmt = $db->prepare($badge_sql);
$badge_stmt->execute([':days' => ABSENT_THRESHOLD]);
$absent_badge_count = $badge_stmt->fetchColumn();
?>

<!-- ============================================
     PAGE HEADER & DYNAMIC THRESHOLD SELECTOR
============================================ -->
<div class="container-fluid">
    <h2 class="mb-3"><i class="fas fa-calendar-check"></i> Attendance Management</h2>
    
    <!-- Dynamic threshold control -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label">Show members absent for ≥</label>
                </div>
                <div class="col-auto">
                    <select name="threshold" class="form-select" onchange="this.form.submit()">
                        <option value="3" <?php echo ABSENT_THRESHOLD == 3 ? 'selected' : ''; ?>>3 days</option>
                        <option value="7" <?php echo ABSENT_THRESHOLD == 7 ? 'selected' : ''; ?>>1 week (7 days)</option>
                        <option value="14" <?php echo ABSENT_THRESHOLD == 14 ? 'selected' : ''; ?>>2 weeks (14 days)</option>
                        <option value="21" <?php echo ABSENT_THRESHOLD == 21 ? 'selected' : ''; ?>>3 weeks (21 days)</option>
                        <option value="30" <?php echo ABSENT_THRESHOLD == 30 ? 'selected' : ''; ?>>1 month (30 days)</option>
                        <option value="60" <?php echo ABSENT_THRESHOLD == 60 ? 'selected' : ''; ?>>2 months (60 days)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <noscript><button type="submit" class="btn btn-primary">Apply</button></noscript>
                </div>
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="hidden" name="recent_limit" value="<?php echo $recent_limit; ?>">
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $view == 'mark' ? 'active' : ''; ?>" href="?view=mark&threshold=<?php echo ABSENT_THRESHOLD; ?>">
                <i class="fas fa-fingerprint"></i> Mark Attendance
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $view == 'records' ? 'active' : ''; ?>" href="?view=records&threshold=<?php echo ABSENT_THRESHOLD; ?>">
                <i class="fas fa-history"></i> Attendance Records
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $view == 'absent' ? 'active' : ''; ?>" href="?view=absent&threshold=<?php echo ABSENT_THRESHOLD; ?>">
                <i class="fas fa-bell"></i> Follow-up Needed 
                <?php if ($absent_badge_count > 0): ?>
                    <span class="badge bg-danger"><?php echo $absent_badge_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- Success / Error Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- ============================================
         STATISTICS CARDS
    ============================================ -->
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h6>Today's Attendance</h6><h2><?php echo $today_attendance; ?></h2></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h6>This Week</h6><h2><?php echo $weekly_attendance; ?></h2></div></div></div>
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h6>This Month</h6><h2><?php echo $monthly_attendance; ?></h2></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-white"><div class="card-body"><h6>Total Active Members</h6><h2><?php echo $total_members; ?></h2></div></div></div>
    </div>

    <!-- ============================================
         MAIN CONTENT (depends on active tab)
    ============================================ -->
    <?php if ($view == 'mark'): ?>
    <!-- ========== MARK ATTENDANCE VIEW ========== -->
    <div class="row">
        <!-- Individual Marking -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white"><h5><i class="fas fa-fingerprint"></i> Mark Individual</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3"><label>Member *</label><select name="member_id" class="form-control" required>
                            <option value="">Choose member...</option>
                            <?php foreach ($members_list as $m): ?>
                                <option value="<?php echo $m['member_id']; ?>"><?php echo $m['membership_no'] . ' - ' . $m['name']; ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div class="mb-3"><label>Service Date *</label><input type="date" name="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="mb-3"><label>Service Type *</label><select name="service_type" class="form-control">
                            <option>Sabbath School</option><option>Divine Service</option><option>Prayer Meeting</option><option>Youth</option><option>Choir Practice</option>
                        </select></div>
                        <div class="mb-3"><label>Status *</label><select name="status" class="form-control">
                            <option>Present</option><option>Late</option><option>Absent</option>
                        </select></div>
                        <div class="mb-3"><label>Check-in Time</label><input type="time" name="check_in_time" class="form-control" value="<?php echo date('H:i:s'); ?>"></div>
                        <button type="submit" name="mark_attendance" class="btn btn-primary w-100"><i class="fas fa-save"></i> Mark</button>
                    </form>
                </div>
            </div>
            <!-- Service Statistics Chart -->
            <div class="card">
                <div class="card-header bg-info text-white"><h5>Service Statistics (This Month)</h5></div>
                <div class="card-body">
                    <canvas id="serviceStatsChart" height="250"></canvas>
                    <hr>
                    <?php foreach ($service_stats as $stat): ?>
                        <div><div class="d-flex justify-content-between"><span><?php echo $stat['service_type']; ?></span><span><?php echo round($stat['avg_attendance']); ?>%</span></div>
                        <div class="progress mb-2"><div class="progress-bar bg-success" style="width: <?php echo $stat['avg_attendance']; ?>%"></div></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Bulk Attendance -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><h5><i class="fas fa-users"></i> Bulk Attendance</h5></div>
                <div class="card-body">
                    <form method="POST" id="bulkForm">
                        <div class="row mb-3">
                            <div class="col-md-4"><label>Service Date</label><input type="date" name="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                            <div class="col-md-4"><label>Service Type</label><select name="service_type" class="form-control"><option>Sabbath School</option><option>Divine Service</option><option>Prayer Meeting</option><option>Youth</option><option>Choir Practice</option></select></div>
                            <div class="col-md-4"><label>&nbsp;</label><button type="button" class="btn btn-secondary form-control" onclick="selectAll()"><i class="fas fa-check-double"></i> Select All</button></div>
                        </div>
                        <div class="table-responsive" style="max-height:400px; overflow-y:auto">
                            <table class="table table-bordered">
                                <thead><tr><th><input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()"></th><th>Member No</th><th>Name</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($members_list as $m): ?>
                                    <tr>
                                        <td class="text-center"><input type="checkbox" name="members[<?php echo $m['member_id']; ?>]" value="present" class="member-checkbox"></td>
                                        <td><?php echo $m['membership_no']; ?></td>
                                        <td><?php echo $m['name']; ?></td>
                                        <td><select name="status[<?php echo $m['member_id']; ?>]" class="form-select"><option value="present">Present</option><option value="late">Late</option><option value="absent" selected>Absent</option></select></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3"><button type="submit" name="bulk_attendance" class="btn btn-success"><i class="fas fa-save"></i> Save Bulk</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($view == 'records'): ?>
    <!-- ========== ATTENDANCE RECORDS VIEW ========== -->
    <div class="card">
        <div class="card-header bg-warning"><h5><i class="fas fa-search"></i> Filter Records</h5></div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4"><label>Date</label><input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>"></div>
                <div class="col-md-4"><label>Service Type</label><select name="service_type" class="form-control"><option value="">All</option><option <?php echo $filter_service=='Sabbath School'?'selected':''; ?>>Sabbath School</option><option <?php echo $filter_service=='Divine Service'?'selected':''; ?>>Divine Service</option><option <?php echo $filter_service=='Prayer Meeting'?'selected':''; ?>>Prayer Meeting</option><option <?php echo $filter_service=='Youth'?'selected':''; ?>>Youth</option><option <?php echo $filter_service=='Choir Practice'?'selected':''; ?>>Choir Practice</option></select></div>
                <div class="col-md-4"><label>&nbsp;</label><button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button></div>
                <input type="hidden" name="view" value="records">
                <input type="hidden" name="threshold" value="<?php echo ABSENT_THRESHOLD; ?>">
            </form>
            <hr>
            <div class="table-responsive">
                <table class="table table-striped" id="recordsTable">
                    <thead><tr><th>Member No</th><th>Member Name</th><th>Service Type</th><th>Status</th><th>Check-in Time</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($filtered_attendance as $rec): ?>
                        <tr>
                            <td><?php echo $rec['membership_no']; ?></td>
                            <td><?php echo $rec['member_name']; ?></td>
                            <td><?php echo $rec['service_type']; ?></td>
                            <td><span class="badge bg-<?php echo $rec['status']=='Present'?'success':($rec['status']=='Late'?'warning':'danger'); ?>"><?php echo $rec['status']; ?></span></td>
                            <td><?php echo $rec['check_in_time'] ?: '-'; ?></td>
                            <td><button class="btn btn-sm btn-warning" onclick="editAttendance(<?php echo $rec['attendance_id']; ?>, '<?php echo $rec['status']; ?>')"><i class="fas fa-edit"></i> Edit</button></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($filtered_attendance)): ?>
                        <tr><td colspan="6" class="text-center">No records found for this date.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($view == 'absent'): ?>
    <!-- ========== FOLLOW-UP VIEW (absent >= threshold) ========== -->
    <div class="card">
        <div class="card-header bg-warning">
            <h5><i class="fas fa-bell"></i> Members Needing Follow-up (absent ≥ <?php echo ABSENT_THRESHOLD; ?> days)</h5>
        </div>
        <div class="card-body">
            <?php if (count($absent_members) > 0): ?>
                <div class="alert alert-info">These <?php echo count($absent_members); ?> members haven't attended for <?php echo ABSENT_THRESHOLD; ?>+ days.</div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="absentTable">
                        <thead><tr><th>Member No</th><th>Name</th><th>Last Attendance</th><th>Days Absent</th><th>Phone</th><th>Email</th><th>Total Attendance</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($absent_members as $m): ?>
                            <tr class="<?php echo $m['days_absent'] > 60 ? 'table-danger' : ($m['days_absent'] > 30 ? 'table-warning' : 'table-info'); ?>">
                                <td><?php echo $m['membership_no']; ?></td>
                                <td><strong><?php echo $m['first_name'] . ' ' . $m['last_name']; ?></strong></td>
                                <td><?php echo $m['last_attendance'] ? date('F j, Y', strtotime($m['last_attendance'])) : '<span class="text-danger">Never</span>'; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $m['days_absent']; ?> days</span></td>
                                <td><?php echo $m['phone'] ? '<a href="tel:'.$m['phone'].'" class="btn btn-sm btn-success"><i class="fas fa-phone"></i> Call</a>' : '-'; ?></td>
                                <td><?php echo $m['email'] ? '<a href="mailto:'.$m['email'].'" class="btn btn-sm btn-primary"><i class="fas fa-envelope"></i> Email</a>' : '-'; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $m['total_attendance']; ?> times</span></td>
                                <td>
                                    <a href="member_details.php?id=<?php echo $m['member_id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
                                    <button class="btn btn-sm btn-success" onclick="markContacted(<?php echo $m['member_id']; ?>, '<?php echo addslashes($m['first_name']); ?>')"><i class="fas fa-check"></i> Contacted</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-secondary mt-3">
                    <strong>Recommended Actions:</strong><br>
                    • <?php echo ABSENT_THRESHOLD; ?>-30 days: SMS or call<br>
                    • 31-60 days: Call & schedule visit<br>
                    • 60+ days: Immediate pastoral visit
                </div>
            <?php else: ?>
                <div class="alert alert-success text-center">No members have been absent for <?php echo ABSENT_THRESHOLD; ?>+ days. All active members are attending regularly.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================
         RECENT ATTENDANCE (with Show More)
    ============================================ -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between">
            <span><i class="fas fa-history"></i> Recent Attendance Records</span>
            <?php if ($recent_limit == 20): ?>
                <a href="?view=<?php echo $view; ?>&threshold=<?php echo ABSENT_THRESHOLD; ?>&recent_limit=50" class="btn btn-sm btn-light">Show More (50)</a>
            <?php else: ?>
                <a href="?view=<?php echo $view; ?>&threshold=<?php echo ABSENT_THRESHOLD; ?>&recent_limit=20" class="btn btn-sm btn-light">Show Less</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <table class="table table-striped" id="recentTable">
                <thead><tr><th>Date</th><th>Member</th><th>Service</th><th>Status</th><th>Check-in</th><th>Recorded By</th></tr></thead>
                <tbody>
                    <?php foreach ($recent_attendance as $rec): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($rec['service_date'])); ?></td>
                        <td><?php echo $rec['member_name']; ?><br><small class="text-muted"><?php echo $rec['membership_no']; ?></small></td>
                        <td><?php echo $rec['service_type']; ?></td>
                        <td><span class="badge bg-<?php echo $rec['status']=='Present'?'success':($rec['status']=='Late'?'warning':'danger'); ?>"><?php echo $rec['status']; ?></span></td>
                        <td><?php echo $rec['check_in_time'] ?: '-'; ?></td>
                        <td><?php echo $rec['created_by']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================
     MODALS
============================================ -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header"><h5>Edit Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="attendance_id" id="edit_attendance_id">
                    <label>Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option>Present</option><option>Late</option><option>Absent</option>
                    </select>
                </div>
                <div class="modal-footer"><button type="submit" name="update_attendance" class="btn btn-primary">Update</button></div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart
const ctx = document.getElementById('serviceStatsChart')?.getContext('2d');
if (ctx) {
    const labels = <?php echo json_encode(array_column($service_stats, 'service_type')); ?>;
    const data = <?php echo json_encode(array_column($service_stats, 'avg_attendance')); ?>;
    new Chart(ctx, {
        type: 'pie',
        data: { labels: labels, datasets: [{ data: data, backgroundColor: ['#3498db','#2ecc71','#f1c40f','#e67e22','#e74c3c'] }] }
    });
}

// Bulk helpers
function selectAll() { document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = true); }
function toggleSelectAll() { let all = document.getElementById('selectAllCheckbox').checked; document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = all); }
function resetForm() { document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = false); document.getElementById('selectAllCheckbox') && (document.getElementById('selectAllCheckbox').checked = false); }

// Edit modal
function editAttendance(id, status) {
    document.getElementById('edit_attendance_id').value = id;
    document.getElementById('edit_status').value = status;
    new bootstrap.Modal(document.getElementById('editAttendanceModal')).show();
}

// Follow-up mark contacted
function markContacted(memberId, name) {
    if (confirm(`Mark ${name} as contacted?`)) {
        // You can add AJAX logging here if needed
        alert(`${name} has been marked as contacted.`);
        location.reload(); // refresh to update list
    }
}

// DataTables
$(document).ready(function() {
    $('#recentTable, #recordsTable, #absentTable').DataTable({ pageLength: 10, order: [] });
});
</script>

<?php require_once 'includes/footer.php'; ?>