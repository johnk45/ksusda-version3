<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$prayer_id = $_GET['prayer_id'];

$query = "SELECT * FROM prayer_requests WHERE prayer_id = :prayer_id";
$stmt = $db->prepare($query);
$stmt->execute([':prayer_id' => $prayer_id]);
$prayer = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h5><?php echo htmlspecialchars($prayer['prayer_title']); ?></h5>
    <hr>
    <p><strong>Requester:</strong> <?php echo htmlspecialchars($prayer['requester_name']); ?></p>
    <p><strong>Email:</strong> <?php echo $prayer['requester_email'] ?: 'Not provided'; ?></p>
    <p><strong>Phone:</strong> <?php echo $prayer['requester_phone'] ?: 'Not provided'; ?></p>
    <p><strong>Category:</strong> <?php echo $prayer['category']; ?></p>
    <p><strong>Urgency:</strong> <?php echo $prayer['urgency']; ?></p>
    <p><strong>Date Submitted:</strong> <?php echo date('F j, Y g:i A', strtotime($prayer['created_at'])); ?></p>
    <hr>
    <p><strong>Prayer Request:</strong></p>
    <p><?php echo nl2br(htmlspecialchars($prayer['prayer_content'])); ?></p>
    
    <?php if($prayer['is_answered']): ?>
    <div class="alert alert-success">
        <strong>Answered on <?php echo $prayer['answered_date']; ?>:</strong><br>
        <?php echo htmlspecialchars($prayer['answer_notes']); ?>
    </div>
    <?php endif; ?>
    
    <p><strong>Prayer Count:</strong> <?php echo $prayer['prayer_count']; ?> people have prayed</p>
</div>