<?php
// admin_prayers.php - Admin panel to manage prayer requests
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Approve prayer request
if(isset($_GET['approve']) && isset($_GET['id'])) {
    $query = "UPDATE prayer_requests SET status = 'approved', approved_by = :user_id, approved_at = NOW() 
              WHERE prayer_id = :prayer_id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':prayer_id' => $_GET['id']
    ]);
    $_SESSION['success'] = "Prayer request approved and published!";
    header("Location: admin_prayers.php");
    exit();
}

// Mark as answered
if(isset($_POST['mark_answered'])) {
    $query = "UPDATE prayer_requests SET is_answered = 1, answered_date = CURDATE(), answer_notes = :notes 
              WHERE prayer_id = :prayer_id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':notes' => $_POST['answer_notes'],
        ':prayer_id' => $_POST['prayer_id']
    ]);
    $_SESSION['success'] = "Prayer request marked as answered!";
    header("Location: admin_prayers.php");
    exit();
}

// Delete prayer request
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $query = "DELETE FROM prayer_requests WHERE prayer_id = :prayer_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':prayer_id' => $_GET['id']]);
    $_SESSION['success'] = "Prayer request deleted!";
    header("Location: admin_prayers.php");
    exit();
}

// Get all prayer requests
$status_filter = $_GET['status'] ?? 'pending';
$query = "SELECT p.*, u.username as approved_by_name
          FROM prayer_requests p
          LEFT JOIN users u ON p.approved_by = u.user_id
          WHERE p.status = :status
          ORDER BY 
            CASE p.urgency 
                WHEN 'Critical' THEN 1 
                WHEN 'High' THEN 2 
                WHEN 'Medium' THEN 3 
                WHEN 'Low' THEN 4 
            END,
            p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':status' => $status_filter]);
$prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts
$count_query = "SELECT status, COUNT(*) as count FROM prayer_requests GROUP BY status";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);
$count_map = [];
foreach($counts as $c) { $count_map[$c['status']] = $c['count']; }
?>

<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-praying-hands"></i> Prayer Request Management</h2>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h3><?php echo $count_map['pending'] ?? 0; ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3><?php echo $count_map['approved'] ?? 0; ?></h3>
                    <p>Approved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h3><?php 
                        $answered = 0;
                        $query = "SELECT COUNT(*) as count FROM prayer_requests WHERE is_answered = 1";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    ?></h3>
                    <p>Answered Prayers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3><?php echo array_sum($count_map); ?></h3>
                    <p>Total Requests</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" href="?status=pending">
                Pending <span class="badge bg-warning"><?php echo $count_map['pending'] ?? 0; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" href="?status=approved">
                Approved <span class="badge bg-success"><?php echo $count_map['approved'] ?? 0; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'declined' ? 'active' : ''; ?>" href="?status=declined">
                Declined
            </a>
        </li>
    </ul>
    
    <!-- Prayer Requests Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="prayersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Requester</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Urgency</th>
                            <th>Date</th>
                            <th>Prayers</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($prayers as $prayer): ?>
                        <tr>
                            <td><?php echo $prayer['prayer_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($prayer['requester_name']); ?></strong>
                                <?php if($prayer['requester_email']): ?>
                                <br><small><?php echo $prayer['requester_email']; ?></small>
                                <?php endif; ?>
                             </td>
                            <td><?php echo htmlspecialchars(substr($prayer['prayer_title'], 0, 50)); ?></td>
                            <td><?php echo $prayer['category']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $prayer['urgency'] == 'Critical' ? 'danger' : 
                                        ($prayer['urgency'] == 'High' ? 'warning' : 
                                        ($prayer['urgency'] == 'Medium' ? 'info' : 'success')); 
                                ?>">
                                    <?php echo $prayer['urgency']; ?>
                                </span>
                             </td>
                            <td><?php echo date('M d, Y', strtotime($prayer['created_at'])); ?></td>
                            <td><?php echo $prayer['prayer_count']; ?></td>
                            <td>
                                <?php if($prayer['status'] == 'pending'): ?>
                                    <a href="?approve=1&id=<?php echo $prayer['prayer_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this prayer request?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-sm btn-info" onclick="viewPrayer(<?php echo $prayer['prayer_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                
                                <?php if(!$prayer['is_answered'] && $prayer['status'] == 'approved'): ?>
                                    <button class="btn btn-sm btn-primary" onclick="markAnswered(<?php echo $prayer['prayer_id']; ?>)">
                                        <i class="fas fa-check-double"></i> Mark Answered
                                    </button>
                                <?php endif; ?>
                                
                                <a href="?delete=1&id=<?php echo $prayer['prayer_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this prayer request?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Prayer Modal -->
<div class="modal fade" id="viewPrayerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Prayer Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="prayerDetails"></div>
        </div>
    </div>
</div>

<!-- Mark Answered Modal -->
<div class="modal fade" id="answerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Prayer as Answered</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="prayer_id" id="answer_prayer_id">
                    <div class="mb-3">
                        <label>How was this prayer answered?</label>
                        <textarea name="answer_notes" class="form-control" rows="4" required placeholder="Describe how God answered this prayer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="mark_answered" class="btn btn-primary">Mark Answered</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#prayersTable').DataTable();
});

function viewPrayer(id) {
    $.ajax({
        url: 'ajax/get_prayer_details.php',
        method: 'GET',
        data: { prayer_id: id },
        success: function(response) {
            $('#prayerDetails').html(response);
            $('#viewPrayerModal').modal('show');
        }
    });
}

function markAnswered(id) {
    $('#answer_prayer_id').val(id);
    $('#answerModal').modal('show');
}
</script>

<?php require_once 'includes/footer.php'; ?>