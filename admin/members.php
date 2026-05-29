<?php
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Handle member addition
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $membership_no = 'SDA/KSU/' . date('Y') . '/' . rand(1000, 9999);
    
    $query = "INSERT INTO members (membership_no, title, first_name, last_name, other_name, gender, 
              date_of_birth, marital_status, phone, email, address, occupation, join_date) 
              VALUES (:membership_no, :title, :first_name, :last_name, :other_name, :gender, 
              :dob, :marital_status, :phone, :email, :address, :occupation, :join_date)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':membership_no' => $membership_no,
        ':title' => $_POST['title'],
        ':first_name' => $_POST['first_name'],
        ':last_name' => $_POST['last_name'],
        ':other_name' => $_POST['other_name'],
        ':gender' => $_POST['gender'],
        ':dob' => $_POST['dob'],
        ':marital_status' => $_POST['marital_status'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':address' => $_POST['address'],
        ':occupation' => $_POST['occupation'],
        ':join_date' => $_POST['join_date']
    ]);
    
    $success = "Member added successfully! Membership No: " . $membership_no;
}

// Get all members
$query = "SELECT * FROM members ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Member Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class="fas fa-plus"></i> Add New Member
        </button>
    </div>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <table id="membersTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Membership No</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($members as $member): ?>
                    <tr>
                        <td><?php echo $member['membership_no']; ?></td>
                        <td><?php echo $member['title'] . ' ' . $member['first_name'] . ' ' . $member['last_name']; ?></td>
                        <td><?php echo $member['gender']; ?></td>
                        <td><?php echo $member['phone']; ?></td>
                        <td><?php echo $member['email']; ?></td>
                        <td><?php echo $member['join_date']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $member['membership_status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                <?php echo $member['membership_status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewMember(<?php echo $member['member_id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="editMember(<?php echo $member['member_id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Title</label>
                            <select name="title" class="form-control" required>
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Prof.">Prof.</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Other Name</label>
                            <input type="text" name="other_name" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Marital Status</label>
                            <select name="marital_status" class="form-control">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Occupation</label>
                            <input type="text" name="occupation" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Join Date</label>
                        <input type="date" name="join_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_member" class="btn btn-primary">Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#membersTable').DataTable();
});

function viewMember(id) {
    // Implement view member details
    window.location.href = 'member_details.php?id=' + id;
}

function editMember(id) {
    // Implement edit member
    window.location.href = 'edit_member.php?id=' + id;
}
</script>

<?php require_once 'includes/footer.php'; ?>