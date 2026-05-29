<?php
// admin_announcements.php - Admin interface for managing announcements
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Handle Add/Edit Announcement
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_announcement'])) {
        $query = "INSERT INTO announcements (category, ministry, title, announcement_date, 
                  announcement_time, venue, organizer, message, priority, created_by) 
                  VALUES (:category, :ministry, :title, :announcement_date, 
                  :announcement_time, :venue, :organizer, :message, :priority, :created_by)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':category' => $_POST['category'],
            ':ministry' => $_POST['ministry'] ?: null,
            ':title' => $_POST['title'],
            ':announcement_date' => $_POST['announcement_date'],
            ':announcement_time' => $_POST['announcement_time'],
            ':venue' => $_POST['venue'],
            ':organizer' => $_POST['organizer'],
            ':message' => $_POST['message'],
            ':priority' => $_POST['priority'],
            ':created_by' => $_SESSION['user_id']
        ]);
        
        $success = "Announcement created successfully!";
    }
    
    if(isset($_POST['update_announcement'])) {
        $query = "UPDATE announcements SET 
                  category = :category,
                  ministry = :ministry,
                  title = :title,
                  announcement_date = :announcement_date,
                  announcement_time = :announcement_time,
                  venue = :venue,
                  organizer = :organizer,
                  message = :message,
                  priority = :priority
                  WHERE announcement_id = :announcement_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':category' => $_POST['category'],
            ':ministry' => $_POST['ministry'] ?: null,
            ':title' => $_POST['title'],
            ':announcement_date' => $_POST['announcement_date'],
            ':announcement_time' => $_POST['announcement_time'],
            ':venue' => $_POST['venue'],
            ':organizer' => $_POST['organizer'],
            ':message' => $_POST['message'],
            ':priority' => $_POST['priority'],
            ':announcement_id' => $_POST['announcement_id']
        ]);
        
        $success = "Announcement updated successfully!";
    }
    
    if(isset($_POST['delete_announcement'])) {
        $query = "DELETE FROM announcements WHERE announcement_id = :announcement_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':announcement_id' => $_POST['announcement_id']]);
        $success = "Announcement deleted successfully!";
    }
}

// Get all announcements
$query = "SELECT a.*, u.username as creator_name 
          FROM announcements a
          LEFT JOIN users u ON a.created_by = u.user_id
          ORDER BY a.announcement_date DESC, a.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$query = "SELECT * FROM announcement_categories ORDER BY display_order";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ministries for dropdown
$query = "SELECT * FROM ministries WHERE status = 'Active' ORDER BY ministry_name";
$stmt = $db->prepare($query);
$stmt->execute();
$ministries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get announcement for editing
$edit_announcement = null;
if(isset($_GET['edit'])) {
    $query = "SELECT * FROM announcements WHERE announcement_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_GET['edit']]);
    $edit_announcement = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Announcements Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-card h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .announcement-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .category-Choir { background: #e74c3c; color: white; }
        .category-Ministry { background: #3498db; color: white; }
        .category-Church-Board { background: #9b59b6; color: white; }
        .category-Elders { background: #1abc9c; color: white; }
        .category-Leaders { background: #f39c12; color: white; }
        .category-General { background: #95a5a6; color: white; }
        
        .priority-High { border-left: 4px solid #e74c3c; }
        .priority-Medium { border-left: 4px solid #f39c12; }
        .priority-Low { border-left: 4px solid #27ae60; }
        
        .btn-action {
            margin: 0 5px;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bullhorn"></i> Announcements Management</h2>
        <a href="frontend_announcements.php" class="btn btn-info" target="_blank">
            <i class="fas fa-eye"></i> View Public Page
        </a>
    </div>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Add/Edit Form -->
    <div class="form-card">
        <h4><i class="fas fa-<?php echo $edit_announcement ? 'edit' : 'plus'; ?>"></i> 
            <?php echo $edit_announcement ? 'Edit Announcement' : 'Create New Announcement'; ?>
        </h4>
        <form method="POST">
            <?php if($edit_announcement): ?>
                <input type="hidden" name="announcement_id" value="<?php echo $edit_announcement['announcement_id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['category_name']; ?>" 
                            <?php echo ($edit_announcement && $edit_announcement['category'] == $cat['category_name']) ? 'selected' : ''; ?>>
                            <?php echo $cat['category_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ministry (Optional)</label>
                    <select name="ministry" class="form-control">
                        <option value="">Select Ministry (if applicable)</option>
                        <?php foreach($ministries as $min): ?>
                        <option value="<?php echo $min['ministry_name']; ?>"
                            <?php echo ($edit_announcement && $edit_announcement['ministry'] == $min['ministry_name']) ? 'selected' : ''; ?>>
                            <?php echo $min['ministry_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required 
                       value="<?php echo $edit_announcement ? htmlspecialchars($edit_announcement['title']) : ''; ?>">
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="announcement_date" class="form-control" required
                           value="<?php echo $edit_announcement ? $edit_announcement['announcement_date'] : date('Y-m-d'); ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Time</label>
                    <input type="time" name="announcement_time" class="form-control"
                           value="<?php echo $edit_announcement ? $edit_announcement['announcement_time'] : ''; ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control">
                        <option value="Low" <?php echo ($edit_announcement && $edit_announcement['priority'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($edit_announcement && $edit_announcement['priority'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($edit_announcement && $edit_announcement['priority'] == 'High') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Venue</label>
                    <input type="text" name="venue" class="form-control" placeholder="e.g., TC21, Main Sanctuary"
                           value="<?php echo $edit_announcement ? htmlspecialchars($edit_announcement['venue']) : ''; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Organizer</label>
                    <input type="text" name="organizer" class="form-control" placeholder="e.g., Music Director, Pastor John"
                           value="<?php echo $edit_announcement ? htmlspecialchars($edit_announcement['organizer']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Message <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="4" required placeholder="Enter the announcement message..."><?php echo $edit_announcement ? htmlspecialchars($edit_announcement['message']) : ''; ?></textarea>
            </div>
            
            <button type="submit" name="<?php echo $edit_announcement ? 'update_announcement' : 'add_announcement'; ?>" 
                    class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $edit_announcement ? 'Update Announcement' : 'Publish Announcement'; ?>
            </button>
            
            <?php if($edit_announcement): ?>
                <a href="admin_announcements.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Announcements List -->
    <div class="form-card">
        <h4><i class="fas fa-list"></i> All Announcements</h4>
        <div class="table-responsive">
            <table class="table table-striped" id="announcementsTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($announcements as $ann): ?>
                    <tr>
                        <td>
                            <span class="category-badge category-<?php echo str_replace(' ', '-', $ann['category']); ?>">
                                <?php echo $ann['category']; ?>
                            </span>
                            <?php if($ann['ministry']): ?>
                                <br><small class="text-muted"><?php echo $ann['ministry']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                            <br><small class="text-muted"><?php echo substr($ann['message'], 0, 50); ?>...</small>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($ann['announcement_date'])); ?>
                            <?php if($ann['announcement_time']): ?>
                                <br><small><?php echo date('g:i A', strtotime($ann['announcement_time'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ann['venue']) ?: '-'; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $ann['priority'] == 'High' ? 'danger' : ($ann['priority'] == 'Medium' ? 'warning' : 'success'); ?>">
                                <?php echo $ann['priority']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="?edit=<?php echo $ann['announcement_id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(<?php echo $ann['announcement_id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm">
    <input type="hidden" name="delete_announcement" id="delete_id">
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#announcementsTable').DataTable({
        order: [[2, 'desc']],
        pageLength: 15
    });
});

function deleteAnnouncement(id) {
    if(confirm('Are you sure you want to delete this announcement?')) {
        $('#delete_id').val(id);
        $('#deleteForm').submit();
    }
}
</script>
</body>
</html>