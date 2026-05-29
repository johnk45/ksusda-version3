<?php
// ajax/get_request_details.php - Get full request details
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$request_id = $_GET['request_id'];

$query = "SELECT * FROM pending_members WHERE request_id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <table class="table table-bordered">
        <tr>
            <th width="200">Full Name</th>
            <td><?php echo htmlspecialchars($request['title'] . ' ' . $request['first_name'] . ' ' . $request['last_name']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($request['email']); ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><?php echo htmlspecialchars($request['phone']); ?></td>
        </tr>
        <tr>
            <th>Gender</th>
            <td><?php echo $request['gender'] ?: 'Not specified'; ?></td>
        </tr>
        <tr>
            <th>Date of Birth</th>
            <td><?php echo $request['date_of_birth'] ?: 'Not specified'; ?></td>
        </tr>
        <tr>
            <th>Address</th>
            <td><?php echo nl2br(htmlspecialchars($request['address'])) ?: 'Not specified'; ?></td>
        </tr>
        <tr>
            <th>Occupation</th>
            <td><?php echo htmlspecialchars($request['occupation']) ?: 'Not specified'; ?></td>
        </tr>
        <tr>
            <th>Request Type</th>
            <td><?php echo ucfirst($request['request_type']); ?></td>
        </tr>
        <?php if($request['request_type'] == 'transfer'): ?>
        <tr>
            <th>Previous Church</th>
            <td><?php echo htmlspecialchars($request['previous_church']); ?></td>
        </tr>
        <tr>
            <th>Previous Membership No</th>
            <td><?php echo htmlspecialchars($request['previous_membership_no']) ?: 'Not provided'; ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th>Additional Information</th>
            <td><?php echo nl2br(htmlspecialchars($request['additional_info'])) ?: 'None'; ?></td>
        </tr>
        <tr>
            <th>Requested Date</th>
            <td><?php echo date('F j, Y g:i A', strtotime($request['requested_at'])); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                <span class="badge bg-<?php 
                    echo $request['status'] == 'pending' ? 'warning' : 
                        ($request['status'] == 'approved' ? 'success' : 'danger'); 
                ?>">
                    <?php echo ucfirst($request['status']); ?>
                </span>
            </td>
        </tr>
        <?php if($request['status'] == 'declined' && $request['decline_reason']): ?>
        <tr>
            <th>Decline Reason</th>
            <td class="text-danger"><?php echo htmlspecialchars($request['decline_reason']); ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>