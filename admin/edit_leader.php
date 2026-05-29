<?php
//edit_leader.php - This page edit existing leader

require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$leader_id = $_GET['id'] ?? 0;

//Get leader details
$query = "SELECT * FROM church_leaders WHERE leader_id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $leader_id]);
$leader = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$leader){
    header("Location: manage_leaders.php");
    exit();
}
$upload_dir = 'uploads/leaders/';
if(!file_exists($upload_dir)){
    mkdir($upload_dir,0777,true);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_leader'])){
    $name = $_POST['name'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $bio = $_POST['bio'];
    $order_position = $_POST['order_position'];

    $photo = $leader['photo'];

    //Handle new photo upload
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $filename = $_FILES['photo']['name'];
        $ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));

        if(in_array($ext,$allowed)){
            //delete old photo
            if($photo && file_exists($upload_dir . $photo)){
                unlink($upload_dir . $photo);
            }
            $photo = time() . '_' . preg_replace('/[^a-zA-Z0-9]/','_',$name).'_'.$ext;
            move_uploaded_file($_FILES['photo']['tmp_name'],$upload_dir . $photo);

        }
    }

    $query = "UPDATE church_leaders SET
    name = :name,title = :title,category = :category,
    phone = :phone, email = :email,whatsapp = :whatsapp,
    photo = :photo, bio = :bio, order_position = :order
    WHERE leader_id = :id";

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
        ':order' => $order_position,
        ':id' => $leader_id
    ]);
    
    
    if($result) {
        $success = "Leader updated successfully!";
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM church_leaders WHERE leader_id = :id");
        $stmt->execute([':id' => $leader_id]);
        $leader = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Failed to update leader.";
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> Edit Church Leader</h2>
        <a href="manage_leaders.php" class="btn btn-secondary">Back to List</a>
    </div>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($leader['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Title/Position *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($leader['title']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Category *</label>
                        <select name="category" class="form-control" required>
                            <option value="Pastoral" <?php echo $leader['category'] == 'Pastoral' ? 'selected' : ''; ?>>Pastoral Team</option>
                            <option value="Elders" <?php echo $leader['category'] == 'Elders' ? 'selected' : ''; ?>>Church Elders</option>
                            <option value="Deacons" <?php echo $leader['category'] == 'Deacons' ? 'selected' : ''; ?>>Deacons</option>
                            <option value="Deaconesses" <?php echo $leader['category'] == 'Deaconesses' ? 'selected' : ''; ?>>Deaconesses</option>
                            <option value="Department" <?php echo $leader['category'] == 'Department' ? 'selected' : ''; ?>>Department Leaders</option>
                            <option value="Ministry" <?php echo $leader['category'] == 'Ministry' ? 'selected' : ''; ?>>Ministry Leaders</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Display Order</label>
                        <input type="number" name="order_position" class="form-control" value="<?php echo $leader['order_position']; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($leader['phone']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>WhatsApp Number</label>
                        <input type="tel" name="whatsapp" class="form-control" value="<?php echo htmlspecialchars($leader['whatsapp']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($leader['email']); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label>Current Photo</label>
                    <?php if($leader['photo']): ?>
                        <div class="mb-2">
                            <img src="uploads/leaders/<?php echo $leader['photo']; ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <small class="text-muted">Leave empty to keep current photo</small>
                </div>
                
                <div class="mb-3">
                    <label>Bio / Description</label>
                    <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($leader['bio']); ?></textarea>
                </div>
                
                <button type="submit" name="update_leader" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Leader
                </button>
                <a href="manage_leaders.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>


