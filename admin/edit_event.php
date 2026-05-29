<?php
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

$event_id = $_GET['id'];

// Handle event update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_event'])) {
    $query = "UPDATE events SET 
              event_name = :event_name,
              event_type = :event_type,
              description = :description,
              event_date = :event_date,
              start_time = :start_time,
              end_time = :end_time,
              venue = :venue,
              organizer_id = :organizer_id,
              budget = :budget,
              status = :status
              WHERE event_id = :event_id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':event_name' => $_POST['event_name'],
        ':event_type' => $_POST['event_type'],
        ':description' => $_POST['description'],
        ':event_date' => $_POST['event_date'],
        ':start_time' => $_POST['start_time'],
        ':end_time' => $_POST['end_time'],
        ':venue' => $_POST['venue'],
        ':organizer_id' => $_POST['organizer_id'] ?: null,
        ':budget' => $_POST['budget'] ?: null,
        ':status' => $_POST['status'],
        ':event_id' => $event_id
    ]);
    
    $success = "Event updated successfully!";
}

// Get event details
$query = "SELECT * FROM events WHERE event_id = :event_id";
$stmt = $db->prepare($query);
$stmt->execute([':event_id' => $event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$event) {
    redirect('events.php');
}

// Get members for dropdown
$query = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE membership_status = 'Active'";
$stmt = $db->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Event</h2>
        <a href="events.php" class="btn btn-secondary">Back to Events</a>
    </div>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Event Name *</label>
                        <input type="text" name="event_name" class="form-control" 
                               value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Event Type *</label>
                        <select name="event_type" class="form-control" required>
                            <option value="Worship" <?php echo $event['event_type'] == 'Worship' ? 'selected' : ''; ?>>Worship</option>
                            <option value="Seminar" <?php echo $event['event_type'] == 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                            <option value="Concert" <?php echo $event['event_type'] == 'Concert' ? 'selected' : ''; ?>>Concert</option>
                            <option value="Conference" <?php echo $event['event_type'] == 'Conference' ? 'selected' : ''; ?>>Conference</option>
                            <option value="Fellowship" <?php echo $event['event_type'] == 'Fellowship' ? 'selected' : ''; ?>>Fellowship</option>
                            <option value="Other" <?php echo $event['event_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" class="form-control" 
                               value="<?php echo $event['event_date']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" 
                               value="<?php echo $event['start_time']; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" 
                               value="<?php echo $event['end_time']; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Venue</label>
                        <input type="text" name="venue" class="form-control" 
                               value="<?php echo htmlspecialchars($event['venue']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Planned" <?php echo $event['status'] == 'Planned' ? 'selected' : ''; ?>>Planned</option>
                            <option value="Ongoing" <?php echo $event['status'] == 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="Completed" <?php echo $event['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $event['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Organizer</label>
                        <select name="organizer_id" class="form-control">
                            <option value="">Select Organizer</option>
                            <?php foreach($members as $member): ?>
                            <option value="<?php echo $member['member_id']; ?>" 
                                <?php echo $event['organizer_id'] == $member['member_id'] ? 'selected' : ''; ?>>
                                <?php echo $member['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Budget (KES)</label>
                        <input type="number" step="0.01" name="budget" class="form-control" 
                               value="<?php echo $event['budget']; ?>">
                    </div>
                </div>
                
                <button type="submit" name="update_event" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Event
                </button>
                <a href="events.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>