<?php
// request_membership.php - Public form for membership requests
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Check if email already has pending request
function hasPendingRequest($db, $email) {
    $query = "SELECT request_id, status, requested_at FROM pending_members 
              WHERE email = :email AND status IN ('pending', 'approved')";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    // Get form data
    $title = $_POST['title'] ?? '';
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $request_type = $_POST['request_type'] ?? 'new';
    $previous_church = trim($_POST['previous_church'] ?? '');
    $previous_membership_no = trim($_POST['previous_membership_no'] ?? '');
    $additional_info = trim($_POST['additional_info'] ?? '');
    
    // Validation
    $errors = [];
    if(empty($first_name)) $errors[] = "First name is required";
    if(empty($last_name)) $errors[] = "Last name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(empty($phone)) $errors[] = "Phone number is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check for duplicate/pending request
    $existing = hasPendingRequest($db, $email);
    if($existing) {
        if($existing['status'] == 'pending') {
            $errors[] = "You already have a pending membership request submitted on " . 
                        date('M d, Y', strtotime($existing['requested_at'])) . 
                        ". The church will review it soon.";
        } elseif($existing['status'] == 'approved') {
            $errors[] = "You are already a registered member. Please login to your account.";
        }
    }
    
    if(empty($errors)) {
        // Generate membership number (temporary, will be updated on approval)
        $temp_membership_no = 'PENDING/' . date('Ymd') . '/' . rand(1000, 9999);
        
        $query = "INSERT INTO pending_members (title, first_name, last_name, email, phone, 
                  gender, date_of_birth, address, occupation, request_type, previous_church, 
                  previous_membership_no, additional_info, status, requested_at) 
                  VALUES (:title, :first_name, :last_name, :email, :phone, :gender, :dob, 
                  :address, :occupation, :request_type, :prev_church, :prev_membership, 
                  :additional_info, 'pending', NOW())";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':title' => $title,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':phone' => $phone,
            ':gender' => $gender,
            ':dob' => $date_of_birth,
            ':address' => $address,
            ':occupation' => $occupation,
            ':request_type' => $request_type,
            ':prev_church' => $previous_church,
            ':prev_membership' => $previous_membership_no,
            ':additional_info' => $additional_info
        ]);
        
        if($result) {
            // Send notification email to church admin
            $admin_email = "admin@kisiiuniversitysdachurch.org";
            $subject = "New Membership Request - $first_name $last_name";
            $message = "A new membership request has been submitted.\n\n";
            $message .= "Name: $first_name $last_name\n";
            $message .= "Email: $email\n";
            $message .= "Phone: $phone\n";
            $message .= "Type: $request_type\n";
            $message .= "Please login to admin panel to review and approve.\n";
            $message .= "http://" . $_SERVER['HTTP_HOST'] . "/churchms/admin_approve_members.php";
            
            @mail($admin_email, $subject, $message);
            
            $success = "
                <div class='text-center'>
                    <i class='fas fa-check-circle fa-3x text-success mb-3'></i>
                    <h4>Thank You, $first_name!</h4>
                    <p>Your membership request has been submitted successfully.</p>
                    <p>The church administration will review your request and contact you within 3-5 business days.</p>
                    <p class='text-muted'>A confirmation has been sent to <strong>$email</strong></p>
                    <a href='index.php' class='btn btn-primary mt-3'>Return to Home</a>
                </div>
            ";
        } else {
            $error = "Unable to submit request. Please try again later.";
        }
    } else {
        $error = '<ul class="mb-0">' . implode('', array_map(function($e) { return "<li>$e</li>"; }, $errors)) . '</ul>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Membership - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 0;
        }
        .request-card {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .request-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .request-body {
            padding: 40px;
        }
        .form-label {
            font-weight: 600;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            width: 100%;
        }
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="request-card">
        <div class="request-header">
            <i class="fas fa-user-plus fa-3x mb-3"></i>
            <h2>Request Church Membership</h2>
            <p>Join Kisii University SDA Church family</p>
        </div>
        <div class="request-body">
            <?php if($success): ?>
                <?php echo $success; ?>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="requestForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-select">
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Prof.">Prof.</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label required">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label required">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                            <small class="text-muted">We'll send confirmation to this email</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Request Type</label>
                        <select name="request_type" id="request_type" class="form-select">
                            <option value="new">New Member (First time joining)</option>
                            <option value="transfer">Transfer from another SDA Church</option>
                        </select>
                    </div>
                    
                    <div id="transfer_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Previous Church Name</label>
                                <input type="text" name="previous_church" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Previous Membership No</label>
                                <input type="text" name="previous_membership_no" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Information</label>
                        <textarea name="additional_info" class="form-control" rows="3" 
                                  placeholder="Any additional information you'd like to share..."></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="confirm" required>
                        <label class="form-check-label" for="confirm">
                            I confirm that the information provided is accurate and I wish to become a member of Kisii University SDA Church
                        </label>
                    </div>
                    
                    <button type="submit" name="submit_request" class="btn btn-primary btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Membership Request
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-lock"></i> Your information is secure and will only be used for church purposes.
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.getElementById('request_type').addEventListener('change', function() {
            var transferFields = document.getElementById('transfer_fields');
            transferFields.style.display = this.value === 'transfer' ? 'block' : 'none';
        });
    </script>
</body>
</html>