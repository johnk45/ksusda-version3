<?php
// prayer_request.php - Public form for submitting prayer requests
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_prayer'])) {
    $member_id = $_POST['member_id'] ?: null;
    $requester_name = $_POST['requester_name'];
    $requester_email = $_POST['requester_email'];
    $requester_phone = $_POST['requester_phone'];
    $prayer_title = $_POST['prayer_title'];
    $prayer_content = $_POST['prayer_content'];
    $category = $_POST['category'];
    $urgency = $_POST['urgency'];
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if(empty($requester_name)) $errors[] = "Name is required";
    if(empty($prayer_title)) $errors[] = "Prayer title is required";
    if(empty($prayer_content)) $errors[] = "Prayer request content is required";
    
    if(empty($errors)) {
        $query = "INSERT INTO prayer_requests (member_id, requester_name, requester_email, requester_phone, 
                  prayer_title, prayer_content, category, urgency, is_public, status) 
                  VALUES (:member_id, :requester_name, :requester_email, :requester_phone, 
                  :prayer_title, :prayer_content, :category, :urgency, :is_public, 'pending')";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':member_id' => $member_id,
            ':requester_name' => $requester_name,
            ':requester_email' => $requester_email,
            ':requester_phone' => $requester_phone,
            ':prayer_title' => $prayer_title,
            ':prayer_content' => $prayer_content,
            ':category' => $category,
            ':urgency' => $urgency,
            ':is_public' => $is_public
        ]);
        
        $success = "Your prayer request has been submitted! Our prayer team will pray for you.";
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Prayer Request - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1f2b61 0%, #020114 100%);
            min-height: 100vh;
        }
        
        .prayer-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            margin: 50px auto;
            max-width: 800px;
        }
        
        .prayer-header {
            background: linear-gradient(135deg, #09133e 0%, #010516 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .prayer-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .prayer-header p {
            opacity: 0.9;
        }
        
        .prayer-body {
            padding: 40px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #0e198d 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .urgency-high { background: #dc3545; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; }
        .urgency-medium { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; }
        .urgency-low { background: #28a745; color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; }
        
        .alert-custom {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="prayer-card">
            <div class="prayer-header">
                <i class="fas fa-hands-praying fa-3x mb-3"></i>
                <h1>Submit a Prayer Request</h1>
                <p>We believe in the power of prayer. Share your request with us.</p>
            </div>
            <div class="prayer-body">
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <br><br>
                        <a href="prayer_wall.php" class="btn btn-success">View Prayer Wall</a>
                        <a href="prayer_request.php" class="btn btn-outline-success">Submit Another</a>
                    </div>
                <?php else: ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Your Name *</label>
                                <input type="text" name="requester_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="requester_email" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="requester_phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Are you a member?</label>
                                <select name="member_id" class="form-select">
                                    <option value="">Not a member (Guest)</option>
                                    <?php
                                    $query = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE membership_status = 'Active' ORDER BY first_name";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach($members as $member):
                                    ?>
                                    <option value="<?php echo $member['member_id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prayer Title *</label>
                            <input type="text" name="prayer_title" class="form-control" required placeholder="Brief title for your prayer request">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prayer Request *</label>
                            <textarea name="prayer_content" class="form-control" rows="5" required placeholder="Please share your prayer request in detail..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="Personal">Personal</option>
                                    <option value="Family">Family</option>
                                    <option value="Health">Health</option>
                                    <option value="Financial">Financial</option>
                                    <option value="Work">Work</option>
                                    <option value="Ministry">Ministry</option>
                                    <option value="Community">Community</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Urgency</label>
                                <select name="urgency" class="form-select">
                                    <option value="Low">Low - General prayer</option>
                                    <option value="Medium">Medium - Needs prayer soon</option>
                                    <option value="High">High - Urgent prayer needed</option>
                                    <option value="Critical">Critical - Immediate prayer</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_public" class="form-check-input" id="is_public" checked>
                            <label class="form-check-label" for="is_public">
                                Make my request public on the prayer wall
                            </label>
                            <small class="text-muted d-block">If unchecked, only pastors can see your request</small>
                        </div>
                        
                        <button type="submit" name="submit_prayer" class="btn btn-primary btn-submit">
                            <i class="fas fa-praying-hands"></i> Submit Prayer Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>