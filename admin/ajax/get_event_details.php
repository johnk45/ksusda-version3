<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$event_id = $_GET['event_id'];

$query = "SELECT e.*, CONCAT(m.first_name, ' ', m.last_name) as organizer_name
          FROM events e
          LEFT JOIN members m ON e.organizer_id = m.member_id
          WHERE e.event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->execute([':event_id' => $event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if($event):
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h6><strong>Event Name:</strong></h6>
            <p><?php echo htmlspecialchars($event['event_name']); ?></p>
            
            <h6><strong>Event Type:</strong></h6>
            <p><?php echo $event['event_type']; ?></p>
            
            <h6><strong>Description:</strong></h6>
            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            
            <h6><strong>Venue:</strong></h6>
            <p><?php echo htmlspecialchars($event['venue']) ?: 'Not specified'; ?></p>
        </div>
        <div class="col-md-6">
            <h6><strong>Date:</strong></h6>
            <p><?php echo date('l, F j, Y', strtotime($event['event_date'])); ?></p>
            
            <h6><strong>Time:</strong></h6>
            <p>
                <?php 
                if($event['start_time'] && $event['end_time']) {
                    echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time']));
                } elseif($event['start_time']) {
                    echo date('g:i A', strtotime($event['start_time']));
                } else {
                    echo 'Time TBD';
                }
                ?>
            </p>
            
            <h6><strong>Organizer:</strong></h6>
            <p><?php echo $event['organizer_name'] ?: 'Not assigned'; ?></p>
            
            <h6><strong>Budget:</strong></h6>
            <p>KES <?php echo number_format($event['budget'], 2); ?></p>
            
            <h6><strong>Status:</strong></h6>
            <p>
                <span class="badge bg-<?php 
                    echo $event['status'] == 'Planned' ? 'primary' : 
                        ($event['status'] == 'Ongoing' ? 'success' : 
                        ($event['status'] == 'Completed' ? 'secondary' : 'danger')); 
                ?>">
                    <?php echo $event['status']; ?>
                </span>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>