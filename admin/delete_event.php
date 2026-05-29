<?php
// delete_event.php - Dedicated event deletion page
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$event_id = $_GET['id'] ?? 0;

// Get event details
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
          FROM events e 
          WHERE e.event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->execute([':event_id' => $event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$event) {
    $_SESSION['error'] = "Event not found!";
    redirect('events.php');
}

// Handle deletion confirmation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    $db->beginTransaction();
    
    try {
        // Delete registrations
        $query = "DELETE FROM event_registrations WHERE event_id = :event_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':event_id' => $event_id]);
        
        // Delete event
        $query = "DELETE FROM events WHERE event_id = :event_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':event_id' => $event_id]);
        
        $db->commit();
        
        $_SESSION['success'] = "Event deleted successfully!";
        redirect('events.php');
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Failed to delete event: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Delete Event</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-warning"></i> 
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6>Event Details:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th width="150">Event Name:</th>
                                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Event Type:</th>
                                    <td><?php echo $event['event_type']; ?></td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td><?php echo date('l, F j, Y', strtotime($event['event_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Venue:</th>
                                    <td><?php echo htmlspecialchars($event['venue']) ?: 'Not specified'; ?></td>
                                </tr>
                                <tr>
                                    <th>Registered Members:</th>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $event['registration_count']; ?></span>
                                        registrations will be deleted
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><?php echo $event['status']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmCheckbox" required>
                            <label class="form-check-label" for="confirmCheckbox">
                                I understand that this will permanently delete the event and all associated registrations
                            </label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="events.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger" id="deleteBtn" disabled>
                                <i class="fas fa-trash"></i> Permanently Delete Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirmCheckbox').addEventListener('change', function() {
    document.getElementById('deleteBtn').disabled = !this.checked;
});
</script>

<?php require_once 'includes/footer.php'; ?>