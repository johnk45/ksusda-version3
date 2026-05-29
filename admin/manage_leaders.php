<?php
//manage_leaders.php - List and manage all leaders
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLOggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

//handle delete
if(isset($_GET['delete']) && isset($_GET['id'])){
    $id = $_GET['id'];

    //Get photo to delete
    $query = "SELECT photo FROM church_leaders WHERE leader_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $leader = $stmt->fetch(PDO::FETCH_ASSOC);

    if($leader && $leader['photo']){
       $photo_path = 'uploads/leaders/' . $leader['photo'];
       if(file_exists($photo_path)){
        unlink($photo_path);
       }
    }
    $query = "DELETE FROM church_leaders WHERE leader_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);

    $_SESSION['success']  = "Leader deleted successfully!";
    header("Location: manage_leaders.php");
    exit();
}

//Get all Leaders
$query = "SELECT * FROM church_leaders ORDER BY category, order_position,name";
$stmt=$db->prepare($query);
$stmt->execute();
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i>Manage Church Leaders</h2>
        <a href="add_leader.php" class="btn btn-primary">
            <i class="fas fa-plus"></i>Add New Leader</a>
</div>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="leadersTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Phone</th>
                            <th>WhatsApp</th>
                            <th>Order</th>
                            <th>Actions</th>
</tr>
<tbody>
    <?php foreach($leaders as $leader): ?>
        <tr>
            <td>
                <?php if($leader['photo']): ?>
                    <img src="uploads/leaders/<?php echo $leader['photo'];?>"
                    style="width:50px;height:50px;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                        <?php endif;?>
                    </td>
                    <td><?php echo htmlspecialchars($leader['name']);?></td>
                    <td><?php echo htmlspecialchars($leader['title']);?></td>
                    <td><?php echo $leader['category']; ?></td>
                    <td><?php echo $leader['phone'] ?: '-';?></td>
                    <td><?php echo $leader['whatsapp'] ?: '-';?></td>
                    <td><?php echo $leader['order_position']; ?></td>
                    <td>
                        <a href="edit_leader.php?id=<?php echo $leader['leader_id']; ?>"class="btn btn-sm btn-warming">
                            <i class="fa fa-edit"></i>Edit</a>
                            <a href="?delete=1&id=<?php echo $leader['leader_id'];?>"class="btn btn-sm btn-danger" onclick="return confirm('Delete this leader?')">
                                <i class="fas fa-trash"></i>Delete</a>
                    </td>
                    </tr>
                    <?php endforeach;?>
                    </tbody>
                    </table>
                    </div>
                    </div>
                    </div>
                    </div>

                    <script>
                       $($document).ready(function(){
                        $('#leadersTable').DataTable();
                       });                      
                        </script>
                        <?php require_once 'includes/footer.php'; ?>


