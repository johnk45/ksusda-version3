<?php
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Handle event deletion
if(isset($_GET['delete_event']) && isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    
    $db->beginTransaction();
    
    try {
        $query = "DELETE FROM event_registrations WHERE event_id = :event_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':event_id' => $event_id]);
        
        $query = "DELETE FROM events WHERE event_id = :event_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':event_id' => $event_id]);
        
        $db->commit();
        
        $_SESSION['success'] = "Event and all related registrations deleted successfully!";
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Failed to delete event: " . $e->getMessage();
    }
    
    header("Location: events.php");
    exit();
}

// Handle bulk event deletion
if(isset($_POST['bulk_delete']) && isset($_POST['event_ids'])) {
    $event_ids = $_POST['event_ids'];
    $deleted_count = 0;
    
    $db->beginTransaction();
    
    try {
        foreach($event_ids as $event_id) {
            $query = "DELETE FROM event_registrations WHERE event_id = :event_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':event_id' => $event_id]);
            
            $query = "DELETE FROM events WHERE event_id = :event_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':event_id' => $event_id]);
            
            $deleted_count++;
        }
        
        $db->commit();
        $_SESSION['success'] = "$deleted_count events deleted successfully!";
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Failed to delete events: " . $e->getMessage();
    }
    
    header("Location: events.php");
    exit();
}

// Handle event creation - UPDATED with manual organizer
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_event'])) {
    // Get organizer name directly from input (not from member selection)
    $organizer_name = $_POST['organizer_name'] ?? '';
    
    $query = "INSERT INTO events (event_name, event_type, description, event_date, start_time, 
              end_time, venue, organizer_name, budget, status) 
              VALUES (:event_name, :event_type, :description, :event_date, :start_time, 
              :end_time, :venue, :organizer_name, :budget, :status)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':event_name' => $_POST['event_name'],
        ':event_type' => $_POST['event_type'],
        ':description' => $_POST['description'],
        ':event_date' => $_POST['event_date'],
        ':start_time' => $_POST['start_time'],
        ':end_time' => $_POST['end_time'],
        ':venue' => $_POST['venue'],
        ':organizer_name' => $organizer_name,
        ':budget' => $_POST['budget'] ?: null,
        ':status' => $_POST['status']
    ]);
    
    $success = "Event created successfully!";
}

// Handle event registration
if(isset($_GET['register']) && isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    $member_id = $_GET['member_id'] ?? null;
    
    if($member_id) {
        $check_query = "SELECT * FROM event_registrations WHERE event_id = :event_id AND member_id = :member_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([
            ':event_id' => $event_id,
            ':member_id' => $member_id
        ]);
        
        if($check_stmt->rowCount() == 0) {
            $query = "INSERT INTO event_registrations (event_id, member_id, registration_date) 
                      VALUES (:event_id, :member_id, CURDATE())";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':event_id' => $event_id,
                ':member_id' => $member_id
            ]);
            $reg_success = "Successfully registered for the event!";
        } else {
            $reg_error = "You are already registered for this event!";
        }
    }
}

// Handle attendance marking
if(isset($_POST['mark_attendance'])) {
    $query = "UPDATE event_registrations SET attendance_status = 'Checked In' 
              WHERE registration_id = :registration_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':registration_id' => $_POST['registration_id']]);
    $attendance_success = "Attendance marked successfully!";
}

// Get all events - UPDATED to use organizer_name instead of organizer_id
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registered_count,
          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND attendance_status = 'Checked In') as attended_count
          FROM events e
          ORDER BY e.event_date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming events
$query = "SELECT * FROM events WHERE event_date >= CURDATE() AND status != 'Cancelled' ORDER BY event_date ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get members for dropdown (still needed for event registration)
$query = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE membership_status = 'Active' ORDER BY first_name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get event registrations for selected event
$selected_event_id = $_GET['view_event'] ?? null;
$registrations = [];
if($selected_event_id) {
    $query = "SELECT er.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, m.phone, m.email
              FROM event_registrations er
              JOIN members m ON er.member_id = m.member_id
              WHERE er.event_id = :event_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':event_id' => $selected_event_id]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Display success/error messages
if(isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if(isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<style>
/* Additional styles for better UI */
.event-card {
    transition: transform 0.2s;
}
.event-card:hover {
    transform: translateY(-3px);
}
.organizer-badge {
    background: #e8f4f8;
    color: #17a2b8;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: inline-block;
}
</style>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-calendar-alt"></i> Events Management
    </h2>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($reg_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $reg_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($reg_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $reg_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($attendance_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $attendance_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Event Creation Form -->
        <div class="col-md-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Create New Event</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Event Name *</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Event Type *</label>
                            <select name="event_type" class="form-control" required>
                                <option value="Worship">Worship Service</option>
                                <option value="Seminar">Camp Meeting</option>
                                <option value="Concert">Concert</option>
                                <option value="Conference">Conference</option>
                                <option value="Fellowship">Fellowship</option>
                                <option value="Prayer Meeting">Prayer Meeting</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Brief description of the event..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Event Date *</label>
                                <input type="date" name="event_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="Planned">Planned</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" 
                                   placeholder="e.g., Main Sanctuary, Fellowship Hall, Online">
                        </div>
                        
                        <!-- NEW: Manual Organizer Input instead of dropdown -->
                        <div class="mb-3">
                            <label class="form-label">Organizer/Leader</label>
                            <input type="text" name="organizer_name" class="form-control" 
                                   placeholder="Enter organizer name (e.g., Pastor John Doe, Elder Mary Smith)">
                            <small class="text-muted">Type the name of the person organizing this event</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Budget (KES)</label>
                            <input type="number" step="0.01" name="budget" class="form-control" 
                                   placeholder="0.00">
                        </div>
                        
                        <button type="submit" name="create_event" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Create Event
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Upcoming Events Widget -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Upcoming Events</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if(count($upcoming_events) > 0): ?>
                        <?php foreach($upcoming_events as $event): ?>
                        <div class="card mb-2 event-card">
                            <div class="card-body p-3">
                                <h6 class="mb-1"><?php echo htmlspecialchars($event['event_name']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                    <?php if($event['start_time']): ?>
                                    <br><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                    <?php endif; ?>
                                </small>
                                <?php if($event['organizer_name']): ?>
                                <br><small><i class="fas fa-user"></i> <?php echo htmlspecialchars($event['organizer_name']); ?></small>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <span class="badge bg-<?php echo $event['status'] == 'Planned' ? 'primary' : ($event['status'] == 'Ongoing' ? 'success' : 'secondary'); ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No upcoming events</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Events List with Delete Functionality -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> All Events
                        <button class="btn btn-sm btn-danger float-end" id="bulkDeleteBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="bulkDeleteForm">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="eventsTable">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                        </th>
                                        <th>Event Name</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th>Organizer</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($events as $event): ?>
                                    <tr id="event-row-<?php echo $event['event_id']; ?>">
                                        <td class="text-center">
                                            <input type="checkbox" name="event_ids[]" value="<?php echo $event['event_id']; ?>" class="event-checkbox">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                            <br>
                                            <small class="text-muted badge bg-light"><?php echo $event['event_type']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                            <?php if($event['start_time']): ?>
                                            <br><small><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($event['venue']) ?: '<span class="text-muted">TBD</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if($event['organizer_name']): ?>
                                                <span class="organizer-badge">
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($event['organizer_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill"><?php echo $event['registered_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $event['status'] == 'Planned' ? 'primary' : 
                                                    ($event['status'] == 'Ongoing' ? 'success' : 
                                                    ($event['status'] == 'Completed' ? 'secondary' : 'danger')); 
                                            ?>">
                                                <?php echo $event['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-info" onclick="viewEvent(<?php echo $event['event_id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-success" onclick="registerForEvent(<?php echo $event['event_id']; ?>)" title="Register Member">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                                <button class="btn btn-warning" onclick="editEvent(<?php echo $event['event_id']; ?>)" title="Edit Event">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger" onclick="deleteEvent(<?php echo $event['event_id']; ?>, '<?php echo htmlspecialchars(addslashes($event['event_name'])); ?>')" title="Delete Event">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <input type="submit" name="bulk_delete" id="bulkDeleteSubmit" style="display: none;">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Event Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetails">
                <!-- Event details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Register for Event Modal -->
<div class="modal fade" id="registerEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Register for Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="">
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="reg_event_id">
                    <input type="hidden" name="register" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Member</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">Choose member...</option>
                            <?php foreach($members as $member): ?>
                            <option value="<?php echo $member['member_id']; ?>">
                                <?php echo htmlspecialchars($member['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mark Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Mark Attendance - <span id="attendanceEventName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Member Name</th>
                                <th>Phone</th>
                                <th>Registration Date</th>
                                <th>Attendance Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceList">
                            <!-- Attendance list will be loaded here -->
                        </tbody>
                     </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#eventsTable').DataTable({
        order: [[2, 'asc']], // Sort by date column
        pageLength: 10,
        columnDefs: [
            { orderable: false, targets: [0, 7] } // Disable sorting on checkbox and actions columns
        ],
        language: {
            search: "Search events:",
            lengthMenu: "Show _MENU_ events per page",
            info: "Showing _START_ to _END_ of _TOTAL_ events"
        }
    });
});

function viewEvent(eventId) {
    $.ajax({
        url: 'ajax/get_event_details.php',
        method: 'GET',
        data: { event_id: eventId },
        success: function(response) {
            $('#eventDetails').html(response);
            $('#viewEventModal').modal('show');
        },
        error: function() {
            alert('Error loading event details');
        }
    });
}

function registerForEvent(eventId) {
    $('#reg_event_id').val(eventId);
    $('#registerEventModal').modal('show');
}

function editEvent(eventId) {
    window.location.href = 'edit_event.php?id=' + eventId;
}

function markAttendance(eventId, eventName) {
    $('#attendanceEventName').text(eventName);
    
    $.ajax({
        url: 'ajax/get_event_registrations.php',
        method: 'GET',
        data: { event_id: eventId },
        success: function(response) {
            $('#attendanceList').html(response);
            $('#attendanceModal').modal('show');
        },
        error: function() {
            alert('Error loading attendance data');
        }
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.event-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
    if(bulkDeleteBtn) {
        bulkDeleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.event-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if(checkboxes.length > 0 && bulkDeleteBtn) {
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                bulkDeleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
                
                const selectAll = document.getElementById('selectAll');
                if(selectAll) {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }
            });
        });
    }
});

function deleteEvent(eventId, eventName) {
    if(confirm(`Are you sure you want to delete "${eventName}"?\n\nThis will also remove all registrations for this event. This action cannot be undone!`)) {
        window.location.href = `?delete_event=1&event_id=${eventId}`;
    }
}

const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
if(bulkDeleteBtn) {
    bulkDeleteBtn.addEventListener('click', function() {
        const selectedEvents = document.querySelectorAll('.event-checkbox:checked');
        if(selectedEvents.length === 0) {
            alert('Please select at least one event to delete');
            return;
        }
        
        if(confirm(`Are you sure you want to delete ${selectedEvents.length} event(s)?\n\nThis will also remove all registrations for these events. This action cannot be undone!`)) {
            document.getElementById('bulkDeleteSubmit').click();
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>