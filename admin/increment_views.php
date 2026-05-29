<?php
// increment_views.php - AJAX endpoint to increment sermon views
require_once 'config/sermon_config.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['youtube_id'])) {
    $sermonManager = new SermonManager();
    
    // Increment views using the class method
    $result = $sermonManager->incrementViewsByYoutubeId($_POST['youtube_id']);
    
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>