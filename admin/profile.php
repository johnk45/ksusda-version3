<?php
/**
 * profile.php - User Profile Management
 * Allows users to view/edit profile, change password, manage account settings
 */

require_once '../admin/config/database.php';
require_once '../admin/includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data with member information if linked
$query = "SELECT u.*, m.member_id, m.first_name, m.last_name, m.title, m.phone, m.email as member_email,
          m.gender, m.date_of_birth, m.address, m.membership_no, m.profile_photo
          FROM users u
          LEFT JOIN members m ON u.member_id = m.member_id
          WHERE u.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('logout.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Update basic info
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        $errors = [];
        
        // Validate username
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        // Check if username exists (excluding current user)
        $check = $db->prepare("SELECT user_id FROM users WHERE username = :username AND user_id != :user_id");
        $check->execute([':username' => $username, ':user_id' => $user_id]);
        if ($check->rowCount() > 0) {
            $errors[] = "Username already taken";
        }
        
        // Validate email
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($errors)) {
            $update = "UPDATE users SET username = :username, email = :email WHERE user_id = :user_id";
            $stmt = $db->prepare($update);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':user_id' => $user_id
            ]);
            
            // Update session username
            $_SESSION['username'] = $username;
            
            // Also update member info if linked
            if ($user['member_id']) {
                $update_member = "UPDATE members SET phone = :phone WHERE member_id = :member_id";
                $stmt_member = $db->prepare($update_member);
                $stmt_member->execute([
                    ':phone' => $phone,
                    ':member_id' => $user['member_id']
                ]);
            }
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $db->prepare($query);
            $stmt->execute([':user_id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        // Validate new password
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = "New password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = "New password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = "New password must contain at least one number";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = "UPDATE users SET password = :password WHERE user_id = :user_id";
            $stmt = $db->prepare($update);
            $stmt->execute([
                ':password' => $hashed_password,
                ':user_id' => $user_id
            ]);
            
            $success = "Password changed successfully!";
        } else {
            $error = implode("<br>", $errors);
        }
    }
    
    // Update profile photo (if member is linked)
    if (isset($_POST['update_photo']) && $user['member_id']) {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = '../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old photo
                if ($user['profile_photo'] && file_exists($upload_dir . $user['profile_photo'])) {
                    unlink($upload_dir . $user['profile_photo']);
                }
                
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $update = "UPDATE members SET profile_photo = :photo WHERE member_id = :member_id";
                    $stmt = $db->prepare($update);
                    $stmt->execute([
                        ':photo' => $new_filename,
                        ':member_id' => $user['member_id']
                    ]);
                    $success = "Profile photo updated!";
                    
                    // Refresh user data
                    $stmt = $db->prepare($query);
                    $stmt->execute([':user_id' => $user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to upload photo";
                }
            } else {
                $error = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
            }
        } else {
            $error = "Please select a file to upload";
        }
    }
}

// Get user role display name
$role_display = [
    'Admin' => 'Administrator',
    'Pastor' => 'Pastor',
    'Secretary' => 'Church Secretary',
    'Treasurer' => 'Church Treasurer',
    'Department Head' => 'Department Head',
    'Viewer' => 'Viewer'
];
$user_role = $role_display[$user['role']] ?? $user['role'];
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 30px;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .profile-avatar-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        border: 4px solid white;
    }
    
    .info-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e0e0e0;
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .info-card .card-header {
        background: #f8f9fa;
        padding: 15px 20px;
        font-weight: 600;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .info-card .card-header i {
        color: #E74C3C;
        margin-right: 8px;
    }
    
    .form-label {
        font-weight: 500;
        font-size: 0.85rem;
        margin-bottom: 5px;
        color: #555;
    }
    
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #e0e0e0;
        padding: 10px 15px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #E74C3C;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    }
    
    .btn-save {
        background: #E74C3C;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        color: white;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-save:hover {
        background: #c0392b;
        transform: translateY(-1px);
    }
    
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-label {
        width: 140px;
        font-weight: 600;
        color: #555;
    }
    
    .info-value {
        flex: 1;
        color: #333;
    }
    
    .password-strength {
        height: 4px;
        margin-top: 8px;
        border-radius: 2px;
        transition: all 0.3s;
    }
    
    @media (max-width: 768px) {
        .profile-header {
            padding: 25px;
            text-align: center;
        }
        .info-row {
            flex-direction: column;
        }
        .info-label {
            width: 100%;
            margin-bottom: 5px;
        }
        .profile-avatar, .profile-avatar-placeholder {
            margin: 0 auto;
        }
    }
</style>

<div class="container-fluid">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center text-md-start">
                <?php if ($user['profile_photo'] && file_exists('../uploads/profiles/' . $user['profile_photo'])): ?>
                    <img src="../uploads/profiles/<?php echo $user['profile_photo']; ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <i class="fas fa-user-circle"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-7 text-center text-md-start">
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="mb-1"><i class="fas fa-tag"></i> <?php echo $user_role; ?></p>
                <p class="mb-0"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?: 'No email set'); ?></p>
            </div>
            <div class="col-md-3 text-center text-md-end">
                <small><i class="fas fa-calendar-alt"></i> Member since <?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column - Profile Info Display -->
        <div class="col-lg-4">
            <!-- Quick Info Card -->
            <div class="info-card">
                <div class="card-header">
                    <i class="fas fa-id-card"></i> Account Information
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <div class="info-label">Username:</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Role:</div>
                        <div class="info-value"><?php echo $user_role; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email'] ?: 'Not set'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Account Status:</div>
                        <div class="info-value">
                            <span class="badge bg-success">Active</span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Login:</div>
                        <div class="info-value"><?php echo $user['last_login'] ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></div>
                    </div>
                    <?php if ($user['member_id']): ?>
                    <div class="info-row">
                        <div class="info-label">Linked Member:</div>
                        <div class="info-value">
                            <a href="member_details.php?id=<?php echo $user['member_id']; ?>">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                (<?php echo $user['membership_no']; ?>)
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Photo Upload Card -->
            <?php if ($user['member_id']): ?>
            <div class="info-card">
                <div class="card-header">
                    <i class="fas fa-camera"></i> Profile Photo
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="profile_photo" class="form-control" accept="image/*" required>
                            <small class="text-muted">Allowed: JPG, PNG, GIF, WEBP (Max 2MB)</small>
                        </div>
                        <button type="submit" name="update_photo" class="btn btn-save w-100">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Edit Forms -->
        <div class="col-lg-8">
            <!-- Edit Profile Form -->
            <div class="info-card">
                <div class="card-header">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <?php if ($user['member_id']): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-save">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="info-card">
                <div class="card-header">
                    <i class="fas fa-key"></i> Change Password
                </div>
                <div class="card-body">
                    <form method="POST" id="passwordForm">
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password *</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <div id="passwordStrength" class="password-strength"></div>
                            <small class="text-muted">Min 8 characters, 1 uppercase, 1 lowercase, 1 number</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password *</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <small id="passwordMatch" class="text-muted"></small>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-save">
                            <i class="fas fa-lock"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Password strength meter
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('passwordStrength');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            
            const width = (strength / 4) * 100;
            const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745'];
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = colors[strength - 1] || '#ddd';
        });
    }
    
    // Password match confirmation
    const confirmInput = document.getElementById('confirm_password');
    const matchMsg = document.getElementById('passwordMatch');
    
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (confirm === '') {
                matchMsg.textContent = '';
                matchMsg.className = 'text-muted';
            } else if (password === confirm) {
                matchMsg.textContent = '✓ Passwords match';
                matchMsg.className = 'text-success';
            } else {
                matchMsg.textContent = '✗ Passwords do not match';
                matchMsg.className = 'text-danger';
            }
        });
    }
</script>

<?php require_once '../admin/includes/footer.php'; ?>