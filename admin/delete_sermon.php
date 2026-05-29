<?php
// delete_sermon.php - Delete sermon from database
require_once 'config/sermon_config.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

if(isset($_GET['id'])) {
    $sermonManager = new SermonManager();
    $query = "DELETE FROM sermons WHERE sermon_id = :sermon_id";
    $stmt = $sermonManager->db->prepare($query);
    $stmt->execute([':sermon_id' => $_GET['id']]);
    
    $_SESSION['success'] = "Sermon deleted successfully!";
}

header("Location: admin_sermons.php");
exit();
?>