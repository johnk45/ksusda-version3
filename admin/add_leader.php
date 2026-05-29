<?php
// add_leader.php - Admin form to add new leader
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Create uploads directory if not exists
$upload_dir = 'uploads/leaders/';
if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_leader'])) {
    $name = $_POST['name'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $bio = $_POST['bio'];
    $order_position = $_POST['order_position'];
    
    // Handle photo upload
    $photo = '';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $photo = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '.' . $ext;
            $upload_path = $upload_dir . $photo;
            move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path);
        }
    }
    
    $query = "INSERT INTO church_leaders (name, title, category, phone, email, whatsapp, photo, bio, order_position) 
              VALUES (:name, :title, :category, :phone, :email, :whatsapp, :photo, :bio, :order)";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':name' => $name,
        ':title' => $title,
        ':category' => $category,
        ':phone' => $phone,
        ':email' => $email,
        ':whatsapp' => $whatsapp,
        ':photo' => $photo,
        ':bio' => $bio,
        ':order' => $order_position
    ]);
    
    if($result) {
        $success = "Leader added successfully!";
    } else {
        $error = "Failed to add leader.";
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-plus"></i> Add Church Leader</h2>
        <a href="manage_leaders.php" class="btn btn-secondary">Manage Leaders</a>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Title/Position *</label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="e.g., Senior Pastor, Head Elder, Deacon">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Category *</label>
                        <select name="category" class="form-control" required>
                            <option value="Pastoral">Pastoral Team</option>
                            <option value="Elders">Church Elders</option>
                            <option value="Deacons">Deacons</option>
                            <option value="Deaconesses">Deaconesses</option>
                            <option value="Department">Department Leaders</option>
                            <option value="Ministry">Ministry Leaders</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Display Order</label>
                        <input type="number" name="order_position" class="form-control" value="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="0712345678">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>WhatsApp Number</label>
                        <input type="tel" name="whatsapp" class="form-control" placeholder="0712345678">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label>Profile Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <small class="text-muted">Accepted formats: JPG, PNG, GIF, WEBP. Max size: 2MB</small>
                </div>
                
                <div class="mb-3">
                    <label>Bio / Description (Optional)</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="Brief bio of the leader..."></textarea>
                </div>
                
                <button type="submit" name="add_leader" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Leader
                </button>
                <a href="manage_leaders.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>