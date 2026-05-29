<?php
// edit_member.php - Complete member editing page
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$member_id = $_GET['id'] ?? 0;

// Get member details
$query = "SELECT * FROM members WHERE member_id = :member_id";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$member) {
    $_SESSION['error'] = "Member not found!";
    redirect('members.php');
}

// Handle form submission
$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_member'])) {
        // Update basic information
        $query = "UPDATE members SET 
                  title = :title,
                  first_name = :first_name,
                  last_name = :last_name,
                  other_name = :other_name,
                  gender = :gender,
                  date_of_birth = :date_of_birth,
                  marital_status = :marital_status,
                  phone = :phone,
                  email = :email,
                  address = :address,
                  occupation = :occupation,
                  baptism_date = :baptism_date,
                  membership_status = :membership_status,
                  emergency_contact_name = :emergency_contact_name,
                  emergency_contact_phone = :emergency_contact_phone
                  WHERE member_id = :member_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':title' => $_POST['title'],
            ':first_name' => $_POST['first_name'],
            ':last_name' => $_POST['last_name'],
            ':other_name' => $_POST['other_name'],
            ':gender' => $_POST['gender'],
            ':date_of_birth' => $_POST['date_of_birth'],
            ':marital_status' => $_POST['marital_status'],
            ':phone' => $_POST['phone'],
            ':email' => $_POST['email'],
            ':address' => $_POST['address'],
            ':occupation' => $_POST['occupation'],
            ':baptism_date' => $_POST['baptism_date'] ?: null,
            ':membership_status' => $_POST['membership_status'],
            ':emergency_contact_name' => $_POST['emergency_contact_name'],
            ':emergency_contact_phone' => $_POST['emergency_contact_phone'],
            ':member_id' => $member_id
        ]);
        
        // Handle profile photo upload
        if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = 'member_' . $member_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/profiles/' . $new_filename;
                
                if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $query = "UPDATE members SET profile_photo = :photo WHERE member_id = :member_id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':photo' => $new_filename,
                        ':member_id' => $member_id
                    ]);
                }
            }
        }
        
        $success = "Member information updated successfully!";
        
        // Refresh member data
        $stmt = $db->prepare("SELECT * FROM members WHERE member_id = :member_id");
        $stmt->execute([':member_id' => $member_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Update family information
    if(isset($_POST['update_family'])) {
        $query = "UPDATE members SET family_id = :family_id WHERE member_id = :member_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':family_id' => $_POST['family_id'] ?: null,
            ':member_id' => $member_id
        ]);
        $success = "Family information updated successfully!";
        $member['family_id'] = $_POST['family_id'];
    }
    
    // Add to department
    if(isset($_POST['add_to_department'])) {
        $check = "SELECT * FROM member_departments WHERE member_id = :member_id AND dept_id = :dept_id";
        $stmt = $db->prepare($check);
        $stmt->execute([
            ':member_id' => $member_id,
            ':dept_id' => $_POST['dept_id']
        ]);
        
        if($stmt->rowCount() == 0) {
            $query = "INSERT INTO member_departments (member_id, dept_id, role, joined_date) 
                      VALUES (:member_id, :dept_id, :role, CURDATE())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':member_id' => $member_id,
                ':dept_id' => $_POST['dept_id'],
                ':role' => $_POST['role']
            ]);
            $success = "Member added to department successfully!";
        } else {
            $error = "Member is already in this department!";
        }
    }
    
    // Remove from department
    if(isset($_POST['remove_from_department'])) {
        $query = "DELETE FROM member_departments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $_POST['dept_member_id']]);
        $success = "Member removed from department successfully!";
    }
    
    // Update department role
    if(isset($_POST['update_role'])) {
        $query = "UPDATE member_departments SET role = :role WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':role' => $_POST['role'],
            ':id' => $_POST['dept_member_id']
        ]);
        $success = "Role updated successfully!";
    }
}

// Get member's departments
$query = "SELECT md.*, d.dept_name 
          FROM member_departments md
          JOIN departments d ON md.dept_id = d.dept_id
          WHERE md.member_id = :member_id AND md.status = 'Active'";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$member_depts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all departments for dropdown
$query = "SELECT * FROM departments ORDER BY dept_name";
$stmt = $db->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get families for dropdown
$query = "SELECT * FROM families ORDER BY family_name";
$stmt = $db->prepare($query);
$stmt->execute();
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance history
$query = "SELECT * FROM attendance 
          WHERE member_id = :member_id 
          ORDER BY service_date DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$attendance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get offering history
$query = "SELECT * FROM offerings 
          WHERE member_id = :member_id 
          ORDER BY offering_date DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$offering_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$query = "SELECT 
          COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
          COUNT(CASE WHEN status = 'Late' THEN 1 END) as late_count,
          COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_count,
          COUNT(*) as total_attendance
          FROM attendance 
          WHERE member_id = :member_id 
          AND service_date > DATE_SUB(NOW(), INTERVAL 3 MONTH)";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get total offerings
$query = "SELECT SUM(amount) as total_offerings FROM offerings WHERE member_id = :member_id";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$total_offerings = $stmt->fetch(PDO::FETCH_ASSOC)['total_offerings'] ?? 0;

// Get registered events
$query = "SELECT e.*, er.registration_date, er.attendance_status
          FROM events e
          JOIN event_registrations er ON e.event_id = er.event_id
          WHERE er.member_id = :member_id
          ORDER BY e.event_date DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([':member_id' => $member_id]);
$registered_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-user-edit"></i> Edit Member
            <small class="text-muted"><?php echo $member['membership_no']; ?></small>
        </h2>
        <div>
            <a href="member_details.php?id=<?php echo $member_id; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> View Profile
            </a>
            <a href="members.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Members
            </a>
        </div>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Main Form - Left Column -->
        <div class="col-md-8">
            <!-- Basic Information Tab -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <ul class="nav nav-tabs card-header-tabs" id="memberTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-white" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab">
                                <i class="fas fa-user"></i> Basic Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" data-bs-toggle="tab" data-bs-target="#contact-info" type="button" role="tab">
                                <i class="fas fa-address-card"></i> Contact
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" data-bs-toggle="tab" data-bs-target="#church-info" type="button" role="tab">
                                <i class="fas fa-church"></i> Church Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" data-bs-toggle="tab" data-bs-target="#emergency" type="button" role="tab">
                                <i class="fas fa-ambulance"></i> Emergency
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="tab-content">
                            <!-- Basic Information Tab -->
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label>Title</label>
                                        <select name="title" class="form-control">
                                            <option value="Mr." <?php echo $member['title'] == 'Mr.' ? 'selected' : ''; ?>>Mr.</option>
                                            <option value="Mrs." <?php echo $member['title'] == 'Mrs.' ? 'selected' : ''; ?>>Mrs.</option>
                                            <option value="Miss" <?php echo $member['title'] == 'Miss' ? 'selected' : ''; ?>>Miss</option>
                                            <option value="Dr." <?php echo $member['title'] == 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                                            <option value="Prof." <?php echo $member['title'] == 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                                            <option value="Pastor" <?php echo $member['title'] == 'Pastor' ? 'selected' : ''; ?>>Pastor</option>
                                            <option value="Elder" <?php echo $member['title'] == 'Elder' ? 'selected' : ''; ?>>Elder</option>
                                            <option value="Deacon" <?php echo $member['title'] == 'Deacon' ? 'selected' : ''; ?>>Deacon</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>First Name *</label>
                                        <input type="text" name="first_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Last Name *</label>
                                        <input type="text" name="last_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($member['last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Other Name</label>
                                        <input type="text" name="other_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($member['other_name']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="Male" <?php echo $member['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $member['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label>Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control" 
                                               value="<?php echo $member['date_of_birth']; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label>Marital Status</label>
                                        <select name="marital_status" class="form-control">
                                            <option value="Single" <?php echo $member['marital_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo $member['marital_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Divorced" <?php echo $member['marital_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="Widowed" <?php echo $member['marital_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Occupation</label>
                                    <input type="text" name="occupation" class="form-control" 
                                           value="<?php echo htmlspecialchars($member['occupation']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($member['address']); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Contact Information Tab -->
                            <div class="tab-pane fade" id="contact-info" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($member['phone']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($member['email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Profile Photo</label>
                                    <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                    <?php if($member['profile_photo']): ?>
                                    <div class="mt-2">
                                        <img src="uploads/profiles/<?php echo $member['profile_photo']; ?>" 
                                             alt="Profile" style="max-width: 100px; border-radius: 50%;">
                                        <small class="text-muted">Current photo</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Church Information Tab -->
                            <div class="tab-pane fade" id="church-info" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Baptism Date</label>
                                        <input type="date" name="baptism_date" class="form-control" 
                                               value="<?php echo $member['baptism_date']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Membership Status</label>
                                        <select name="membership_status" class="form-control">
                                            <option value="Active" <?php echo $member['membership_status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $member['membership_status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="Transferred" <?php echo $member['membership_status'] == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                                            <option value="Deceased" <?php echo $member['membership_status'] == 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label>Family</label>
                                    <select name="family_id" class="form-control" form="familyForm">
                                        <option value="">No Family Assigned</option>
                                        <?php foreach($families as $family): ?>
                                        <option value="<?php echo $family['family_id']; ?>" 
                                            <?php echo $member['family_id'] == $family['family_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($family['family_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Emergency Contact Tab -->
                            <div class="tab-pane fade" id="emergency" role="tabpanel">
                                <div class="mb-3">
                                    <label>Emergency Contact Name</label>
                                    <input type="text" name="emergency_contact_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($member['emergency_contact_name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label>Emergency Contact Phone</label>
                                    <input type="tel" name="emergency_contact_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($member['emergency_contact_phone']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="update_member" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Member Information
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Department Management -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Department Management</h5>
                </div>
                <div class="card-body">
                    <!-- Current Departments -->
                    <h6>Current Departments</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($member_depts) > 0): ?>
                                    <?php foreach($member_depts as $dept): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dept['dept_name']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline-flex; gap: 5px;">
                                                <input type="hidden" name="dept_member_id" value="<?php echo $dept['id']; ?>">
                                                <input type="text" name="role" value="<?php echo htmlspecialchars($dept['role']); ?>" 
                                                       class="form-control form-control-sm" style="width: 150px;">
                                                <button type="submit" name="update_role" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td><?php echo $dept['joined_date']; ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Remove from this department?')">
                                                <input type="hidden" name="dept_member_id" value="<?php echo $dept['id']; ?>">
                                                <button type="submit" name="remove_from_department" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Not assigned to any department</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Add to Department -->
                    <h6>Add to Department</h6>
                    <form method="POST" class="row g-3">
                        <div class="col-md-5">
                            <select name="dept_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>">
                                    <?php echo htmlspecialchars($dept['dept_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="role" class="form-control" placeholder="Role (e.g., Member, Leader, Secretary)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_to_department" class="btn btn-success w-100">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Statistics and History -->
        <div class="col-md-4">
            <!-- Quick Stats -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Member Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if($member['profile_photo']): ?>
                        <img src="uploads/profiles/<?php echo $member['profile_photo']; ?>" 
                             alt="Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                        <div style="width: 100px; height: 100px; background: #3498db; border-radius: 50%; 
                                    display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user fa-3x text-white"></i>
                        </div>
                        <?php endif; ?>
                        <h5 class="mt-2"><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></h5>
                        <p class="text-muted"><?php echo $member['membership_no']; ?></p>
                    </div>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <h3><?php echo $attendance_stats['present_count'] ?? 0; ?></h3>
                            <small class="text-muted">Present (3 months)</small>
                        </div>
                        <div class="col-6">
                            <h3>KES <?php echo number_format($total_offerings, 0); ?></h3>
                            <small class="text-muted">Total Offerings</small>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 8px;">
                            <?php 
                            $total = $attendance_stats['total_attendance'] ?? 1;
                            $present_rate = ($attendance_stats['present_count'] ?? 0) / $total * 100;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $present_rate; ?>%"></div>
                        </div>
                        <small>Attendance Rate: <?php echo round($present_rate); ?>%</small>
                    </div>
                </div>
            </div>
            
            <!-- Recent Attendance -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Recent Attendance</h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if(count($attendance_history) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach($attendance_history as $att): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo date('M d, Y', strtotime($att['service_date'])); ?></strong>
                                        <br>
                                        <small><?php echo $att['service_type']; ?></small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $att['status'] == 'Present' ? 'success' : 
                                            ($att['status'] == 'Late' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo $att['status']; ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center">No attendance records</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Offerings -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-usd"></i> Recent Offerings</h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if(count($offering_history) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach($offering_history as $offering): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo date('M d, Y', strtotime($offering['offering_date'])); ?></strong>
                                        <br>
                                        <small><?php echo $offering['offering_type']; ?></small>
                                    </div>
                                    <span class="fw-bold text-success">
                                        KES <?php echo number_format($offering['amount'], 2); ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center">No offering records</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Registered Events -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Registered Events</h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if(count($registered_events) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach($registered_events as $event): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                        <br>
                                        <small><?php echo date('M d, Y', strtotime($event['event_date'])); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $event['attendance_status'] == 'Checked In' ? 'success' : 'warning'; ?>">
                                        <?php echo $event['attendance_status']; ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center">No event registrations</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tabs .nav-link {
    color: white;
    border: none;
}
.nav-tabs .nav-link:hover {
    background: rgba(255,255,255,0.2);
    border-color: transparent;
}
.nav-tabs .nav-link.active {
    background: white;
    color: #3498db;
    border: none;
}
.card-header {
    border-bottom: none;
}
.list-group-item {
    border-left: none;
    border-right: none;
}
</style>

<script>
// Confirm before status change
function confirmStatusChange() {
    var status = document.querySelector('select[name="membership_status"]').value;
    if(status === 'Deceased') {
        return confirm('Warning: Marking as Deceased will archive this member. Continue?');
    }
    return true;
}

// Calculate age from date of birth
function calculateAge() {
    var dob = document.querySelector('input[name="date_of_birth"]').value;
    if(dob) {
        var birthDate = new Date(dob);
        var today = new Date();
        var age = today.getFullYear() - birthDate.getFullYear();
        var m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        if(age > 0) {
            // Could display age somewhere if needed
        }
    }
}

document.querySelector('input[name="date_of_birth"]').addEventListener('change', calculateAge);
</script>

<?php require_once 'includes/footer.php'; ?>