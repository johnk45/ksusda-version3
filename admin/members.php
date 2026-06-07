<?php
/**
 * members.php - Enhanced Member Management
 * Features: Search, filters, export, member count, status toggle, etc.
 */

require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$gender_filter = $_GET['gender'] ?? 'all';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build WHERE clause for members query only (not for count)
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE :search1 OR last_name LIKE :search2 OR membership_no LIKE :search3 OR phone LIKE :search4 OR email LIKE :search5)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
    $params[':search4'] = "%$search%";
    $params[':search5'] = "%$search%";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "membership_status = :status";
    $params[':status'] = $status_filter;
}

if ($gender_filter !== 'all') {
    $where_conditions[] = "gender = :gender";
    $params[':gender'] = $gender_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total member count for statistics (NO parameters - simple query)
$count_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN membership_status = 'Active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN membership_status = 'Inactive' THEN 1 ELSE 0 END) as inactive,
    SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male,
    SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female
    FROM members";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$stats = $count_stmt->fetch(PDO::FETCH_ASSOC);

// Get all members with filters
$allowed_sort_columns = ['created_at', 'first_name', 'last_name', 'membership_no', 'join_date'];
$sort_by = in_array($sort_by, $allowed_sort_columns) ? $sort_by : 'created_at';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$query = "SELECT * FROM members $where_sql ORDER BY $sort_by $sort_order";
$stmt = $db->prepare($query);

$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle member deletion
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $member_id = (int)$_GET['id'];
    $delete_query = "DELETE FROM members WHERE member_id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([':id' => $member_id]);
    $_SESSION['success'] = "Member deleted successfully!";
    header("Location: members.php");
    exit();
}

// Handle status toggle
if(isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $member_id = (int)$_GET['id'];
    $current_status = $_GET['status'] ?? 'Active';
    $new_status = $current_status == 'Active' ? 'Inactive' : 'Active';
    $update_query = "UPDATE members SET membership_status = :status WHERE member_id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([':status' => $new_status, ':id' => $member_id]);
    $_SESSION['success'] = "Member status updated!";
    header("Location: members.php");
    exit();
}

// Handle member addition
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $membership_no = 'SDA/KSU/' . date('Y') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
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
    
    $_SESSION['success'] = "Member added successfully! Membership No: " . $membership_no;
    header("Location: members.php");
    exit();
}
?>

<style>
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        border: 1px solid #e0e0e0;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
    }
    .stats-label {
        color: #666;
        font-size: 0.85rem;
    }
    .filter-badge {
        background: #f8f9fa;
        padding: 8px 15px;
        border-radius: 25px;
        margin-right: 10px;
        margin-bottom: 10px;
        display: inline-block;
        font-size: 0.85rem;
    }
    .filter-badge a {
        color: #dc3545;
        margin-left: 8px;
        text-decoration: none;
    }
    .quick-search {
        position: relative;
    }
    .quick-search input {
        padding-left: 40px;
    }
    .quick-search i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
</style>

<div class="container-fluid">
    <!-- Success Message -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-users"></i> Member Management</h2>
        <div>
            <button class="btn btn-success me-2" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
            <button class="btn btn-secondary me-2" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-plus"></i> Add New Member
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number text-primary"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stats-label">Total Members</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number text-success"><?php echo $stats['active'] ?? 0; ?></div>
                <div class="stats-label">Active Members</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number text-warning"><?php echo $stats['inactive'] ?? 0; ?></div>
                <div class="stats-label">Inactive Members</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number text-info"><?php echo $stats['male'] ?? 0; ?> / <?php echo $stats['female'] ?? 0; ?></div>
                <div class="stats-label">Male / Female</div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <!-- Quick Search -->
                <div class="col-md-4">
                    <div class="quick-search">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, membership no, phone or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Transferred" <?php echo $status_filter == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                        <option value="Deceased" <?php echo $status_filter == 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                
                <!-- Gender Filter -->
                <div class="col-md-2">
                    <select name="gender" class="form-select">
                        <option value="all" <?php echo $gender_filter == 'all' ? 'selected' : ''; ?>>All Genders</option>
                        <option value="Male" <?php echo $gender_filter == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $gender_filter == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <!-- Sort By -->
                <div class="col-md-2">
                    <select name="sort_by" class="form-select">
                        <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                        <option value="first_name" <?php echo $sort_by == 'first_name' ? 'selected' : ''; ?>>First Name</option>
                        <option value="last_name" <?php echo $sort_by == 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                        <option value="membership_no" <?php echo $sort_by == 'membership_no' ? 'selected' : ''; ?>>Membership No</option>
                        <option value="join_date" <?php echo $sort_by == 'join_date' ? 'selected' : ''; ?>>Join Date</option>
                    </select>
                </div>
                
                <!-- Sort Order -->
                <div class="col-md-2">
                    <select name="sort_order" class="form-select">
                        <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>
                
                <!-- Submit and Reset Buttons -->
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="members.php" class="btn btn-secondary">Clear Filters</a>
                    <span class="text-muted ms-2">Showing <?php echo count($members); ?> members</span>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filters Display -->
    <?php if(!empty($search) || $status_filter != 'all' || $gender_filter != 'all'): ?>
    <div class="mb-3">
        <strong>Active Filters:</strong>
        <?php if(!empty($search)): ?>
        <span class="filter-badge">Search: <?php echo htmlspecialchars($search); ?> <a href="members.php">✕</a></span>
        <?php endif; ?>
        <?php if($status_filter != 'all'): ?>
        <span class="filter-badge">Status: <?php echo $status_filter; ?> <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'all'])); ?>">✕</a></span>
        <?php endif; ?>
        <?php if($gender_filter != 'all'): ?>
        <span class="filter-badge">Gender: <?php echo $gender_filter; ?> <a href="?<?php echo http_build_query(array_merge($_GET, ['gender' => 'all'])); ?>">✕</a></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Members Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="membersTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Membership No</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Join Date</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($members) > 0): ?>
                            <?php foreach($members as $member): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($member['membership_no']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($member['title'] . ' ' . $member['first_name'] . ' ' . $member['last_name']); ?></strong>
                                    <?php if($member['other_name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($member['other_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                <td>
                                    <?php if($member['phone']): ?>
                                        <a href="tel:<?php echo $member['phone']; ?>"><?php echo htmlspecialchars($member['phone']); ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($member['email']): ?>
                                        <a href="mailto:<?php echo $member['email']; ?>"><?php echo htmlspecialchars($member['email']); ?></a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $member['membership_status'] == 'Active' ? 'success' : 
                                            ($member['membership_status'] == 'Inactive' ? 'secondary' : 
                                            ($member['membership_status'] == 'Transferred' ? 'info' : 'danger')); 
                                    ?>">
                                        <?php echo $member['membership_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info" onclick="viewMember(<?php echo $member['member_id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning" onclick="editMember(<?php echo $member['member_id']; ?>)" title="Edit Member">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-secondary" onclick="toggleStatus(<?php echo $member['member_id']; ?>, '<?php echo $member['membership_status']; ?>')" title="Toggle Status">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <button class="btn btn-danger" onclick="deleteMember(<?php echo $member['member_id']; ?>, '<?php echo addslashes($member['first_name'] . ' ' . $member['last_name']); ?>')" title="Delete Member">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <p>No members found matching your criteria.</p>
                                    <a href="members.php" class="btn btn-primary btn-sm">Clear Filters</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                            <label>First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Last Name *</label>
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
                            <input type="date" name="dob" class="form-control">
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
                            <label>Phone *</label>
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
                        <label>Join Date *</label>
                        <input type="date" name="join_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_member" class="btn btn-primary">Save Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#membersTable').DataTable({
        pageLength: 25,
        order: [],
        language: {
            search: "Search table:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ members"
        }
    });
});

function viewMember(id) {
    window.location.href = 'member_details.php?id=' + id;
}

function editMember(id) {
    window.location.href = 'edit_member.php?id=' + id;
}

function deleteMember(id, name) {
    if(confirm(`Are you sure you want to delete "${name}"? This action cannot be undone!`)) {
        window.location.href = 'members.php?delete=1&id=' + id;
    }
}

function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
    if(confirm(`Change member status to ${newStatus}?`)) {
        window.location.href = `members.php?toggle_status=1&id=${id}&status=${currentStatus}`;
    }
}

function exportToExcel() {
    const table = document.getElementById('membersTable');
    let csv = [];
    // Get headers
    let headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.innerText);
    });
    csv.push(headers.join(','));
    // Get data
    table.querySelectorAll('tbody tr').forEach(tr => {
        let row = [];
        tr.querySelectorAll('td').forEach(td => {
            let text = td.innerText.replace(/,/g, ' ');
            row.push(text);
        });
        csv.push(row.join(','));
    });
    // Download
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'members_export.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php require_once 'includes/footer.php'; ?>