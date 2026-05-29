<?php
// admin_approve_members.php - Admin panel to approve/decline requests
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Approve request
if(isset($_GET['approve']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    
    // Get request details
    $query = "SELECT * FROM pending_members WHERE request_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($request) {
        // Generate membership number
        $membership_no = 'SDA/KSU/' . date('Y') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert into members table
        $insert = "INSERT INTO members (membership_no, title, first_name, last_name, email, phone, 
                   gender, date_of_birth, address, occupation, join_date, membership_status, 
                   approved_by, approved_date) 
                   VALUES (:membership_no, :title, :first_name, :last_name, :email, :phone, 
                   :gender, :dob, :address, :occupation, CURDATE(), 'Active', :approved_by, NOW())";
        
        $insert_stmt = $db->prepare($insert);
        $insert_result = $insert_stmt->execute([
            ':membership_no' => $membership_no,
            ':title' => $request['title'],
            ':first_name' => $request['first_name'],
            ':last_name' => $request['last_name'],
            ':email' => $request['email'],
            ':phone' => $request['phone'],
            ':gender' => $request['gender'],
            ':dob' => $request['date_of_birth'],
            ':address' => $request['address'],
            ':occupation' => $request['occupation'],
            ':approved_by' => $_SESSION['user_id']
        ]);
        
        if($insert_result) {
            // Update pending request status
            $update = "UPDATE pending_members SET status = 'approved', reviewed_by = :user_id, 
                       reviewed_at = NOW() WHERE request_id = :id";
            $update_stmt = $db->prepare($update);
            $update_stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':id' => $request_id
            ]);
            
            // Log the action
            $log = "INSERT INTO membership_requests_log (request_id, action, performed_by, notes) 
                    VALUES (:id, 'approved', :user_id, 'Member approved with number: $membership_no')";
            $log_stmt = $db->prepare($log);
            $log_stmt->execute([
                ':id' => $request_id,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            // Send approval email to member
            $to = $request['email'];
            $subject = "Membership Approved - Kisii University SDA Church";
            $message = "Dear {$request['first_name']},\n\n";
            $message .= "Congratulations! Your membership request has been approved.\n";
            $message .= "Your Membership Number is: $membership_no\n\n";
            $message .= "Welcome to Kisii University SDA Church family!\n\n";
            $message .= "God bless you.";
            
            @mail($to, $subject, $message);
            
            $success = "Member approved successfully! Membership No: $membership_no";
        } else {
            $error = "Failed to add member to database";
        }
    }
}

// Decline request
if(isset($_GET['decline']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $reason = $_POST['decline_reason'] ?? 'Not specified';
    
    $update = "UPDATE pending_members SET status = 'declined', reviewed_by = :user_id, 
               reviewed_at = NOW(), decline_reason = :reason WHERE request_id = :id";
    $stmt = $db->prepare($update);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':reason' => $reason,
        ':id' => $request_id
    ]);
    
    // Log the action
    $log = "INSERT INTO membership_requests_log (request_id, action, performed_by, notes) 
            VALUES (:id, 'declined', :user_id, :reason)";
    $log_stmt = $db->prepare($log);
    $log_stmt->execute([
        ':id' => $request_id,
        ':user_id' => $_SESSION['user_id'],
        ':reason' => $reason
    ]);
    
    // Get request details for email
    $query = "SELECT * FROM pending_members WHERE request_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Send decline email
    if($request) {
        $to = $request['email'];
        $subject = "Membership Request Update - Kisii University SDA Church";
        $message = "Dear {$request['first_name']},\n\n";
        $message .= "Thank you for your interest in joining Kisii University SDA Church.\n";
        $message .= "Unfortunately, we are unable to process your membership request at this time.\n\n";
        $message .= "Reason: $reason\n\n";
        $message .= "Please contact the church office for more information.\n\n";
        $message .= "God bless you.";
        
        @mail($to, $subject, $message);
    }
    
    $success = "Request declined successfully!";
}

// Get all pending requests
$pending_query = "SELECT * FROM pending_members WHERE status = 'pending' ORDER BY requested_at ASC";
$pending_stmt = $db->prepare($pending_query);
$pending_stmt->execute();
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved requests (recent)
$approved_query = "SELECT * FROM pending_members WHERE status = 'approved' ORDER BY reviewed_at DESC LIMIT 20";
$approved_stmt = $db->prepare($approved_query);
$approved_stmt->execute();
$approved_requests = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get declined requests
$declined_query = "SELECT * FROM pending_members WHERE status = 'declined' ORDER BY reviewed_at DESC LIMIT 20";
$declined_stmt = $db->prepare($declined_query);
$declined_stmt->execute();
$declined_requests = $declined_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_count
                FROM pending_members";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .request-card {
        border-left: 4px solid;
        margin-bottom: 15px;
        transition: transform 0.2s;
    }
    .request-card:hover {
        transform: translateX(5px);
    }
    .request-pending { border-left-color: #ffc107; }
    .request-approved { border-left-color: #28a745; }
    .request-declined { border-left-color: #dc3545; }
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .stats-number {
        font-size: 2rem;
        font-weight: bold;
    }
</style>

<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-user-check"></i> Membership Request Management</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number text-warning"><?php echo $stats['pending_count']; ?></div>
                <div>Pending Requests</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number text-success"><?php echo $stats['approved_count']; ?></div>
                <div>Approved Members</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number text-danger"><?php echo $stats['declined_count']; ?></div>
                <div>Declined Requests</div>
            </div>
        </div>
    </div>
    
    <!-- Pending Requests Tab -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Requests (<?php echo count($pending_requests); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if(count($pending_requests) > 0): ?>
                <?php foreach($pending_requests as $request): ?>
                <div class="request-card request-pending card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h5>
                                <p class="mb-1">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($request['email']); ?> |
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['phone']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-calendar"></i> Requested: <?php echo date('M d, Y g:i A', strtotime($request['requested_at'])); ?>
                                    <span class="badge bg-info ms-2"><?php echo ucfirst($request['request_type']); ?></span>
                                </p>
                                <?php if($request['request_type'] == 'transfer'): ?>
                                <p class="mb-0 small text-muted">
                                    <i class="fas fa-church"></i> Previous Church: <?php echo htmlspecialchars($request['previous_church']); ?>
                                </p>
                                <?php endif; ?>
                                <?php if($request['additional_info']): ?>
                                <p class="mb-0 small text-muted mt-2">
                                    <i class="fas fa-info-circle"></i> <?php echo nl2br(htmlspecialchars($request['additional_info'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-success btn-sm mb-2" onclick="viewFullDetails(<?php echo $request['request_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <br>
                                <a href="?approve=1&id=<?php echo $request['request_id']; ?>" 
                                   class="btn btn-success btn-sm" 
                                   onclick="return confirm('Approve this membership request? The person will be added as a member.')">
                                    <i class="fas fa-check"></i> Approve
                                </a>
                                <button class="btn btn-danger btn-sm" onclick="showDeclineModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['first_name']); ?>')">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center">No pending requests</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Approved Requests -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Recently Approved (<?php echo count($approved_requests); ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Requested</th>
                            <th>Approved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($approved_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo htmlspecialchars($request['phone']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($request['reviewed_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Membership Request Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Decline Modal -->
<div class="modal fade" id="declineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Decline Membership Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="decline_request_id">
                    <div class="mb-3">
                        <label>Reason for Declining</label>
                        <textarea name="decline_reason" class="form-control" rows="3" required 
                                  placeholder="Please provide a reason for declining this request..."></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        The applicant will receive an email with this reason.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewFullDetails(requestId) {
    $.ajax({
        url: 'ajax/get_request_details.php',
        method: 'GET',
        data: { request_id: requestId },
        success: function(response) {
            $('#detailsContent').html(response);
            $('#viewDetailsModal').modal('show');
        }
    });
}

function showDeclineModal(requestId, name) {
    $('#decline_request_id').val(requestId);
    $('#declineModal').modal('show');
    $('#declineModal form').attr('action', '?decline=1&id=' + requestId);
}
</script>

<?php require_once 'includes/footer.php'; ?>