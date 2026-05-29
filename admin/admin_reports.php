<?php
// admin_reports.php - Admin interface for managing reports
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

// Check if user is admin
if($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Secretary') {
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle file upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_report'])) {
    $title = $_POST['title'];
    $report_type = $_POST['report_type'];
    $description = $_POST['description'];
    $report_date = $_POST['report_date'];
    $status = $_POST['status'];
    
    // Handle file upload
    $target_dir = "uploads/reports/";
    
    // Create directory if not exists
    if(!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES["report_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $file_size = $_FILES["report_file"]["size"];
    
    // Allowed file types
    $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'png'];
    
    if(in_array($file_type, $allowed_types)) {
        if(move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
            $query = "INSERT INTO church_reports (title, report_type, description, file_name, file_path, 
                      file_size, file_type, report_date, uploaded_by, status) 
                      VALUES (:title, :report_type, :description, :file_name, :file_path, 
                      :file_size, :file_type, :report_date, :uploaded_by, :status)";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':title' => $title,
                ':report_type' => $report_type,
                ':description' => $description,
                ':file_name' => $file_name,
                ':file_path' => $target_file,
                ':file_size' => $file_size,
                ':file_type' => $file_type,
                ':report_date' => $report_date,
                ':uploaded_by' => $_SESSION['user_id'],
                ':status' => $status
            ]);
            
            $success = "Report uploaded successfully!";
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    } else {
        $error = "Sorry, only PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG files are allowed.";
    }
}

// Handle report deletion
if(isset($_GET['delete'])) {
    $report_id = $_GET['delete'];
    
    // Get file path
    $query = "SELECT file_path FROM church_reports WHERE report_id = :report_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':report_id' => $report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete file from server
    if($report && file_exists($report['file_path'])) {
        unlink($report['file_path']);
    }
    
    // Delete from database
    $query = "DELETE FROM church_reports WHERE report_id = :report_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':report_id' => $report_id]);
    
    $success = "Report deleted successfully!";
}

// Handle status update
if(isset($_POST['update_status'])) {
    $report_id = $_POST['report_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE church_reports SET status = :status WHERE report_id = :report_id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':status' => $status,
        ':report_id' => $report_id
    ]);
    
    $success = "Report status updated!";
}

// Get all reports
$query = "SELECT r.*, u.username as uploaded_by_name 
          FROM church_reports r
          LEFT JOIN users u ON r.uploaded_by = u.user_id
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = 'Published' THEN 1 ELSE 0 END) as published,
                    SUM(view_count) as total_views,
                    SUM(download_count) as total_downloads
                FROM church_reports";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Church Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .report-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .file-icon {
            font-size: 3rem;
            color: #3498db;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .preview-modal {
            max-width: 90%;
            width: 90%;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-alt"></i> Church Reports Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadReportModal">
            <i class="fas fa-upload"></i> Upload New Report
        </button>
    </div>
    
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
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Reports</h6>
                        <h2 class="mb-0"><?php echo $stats['total_reports']; ?></h2>
                    </div>
                    <i class="fas fa-file-alt fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Published Reports</h6>
                        <h2 class="mb-0"><?php echo $stats['published']; ?></h2>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Views</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_views']); ?></h2>
                    </div>
                    <i class="fas fa-eye fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Downloads</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_downloads']); ?></h2>
                    </div>
                    <i class="fas fa-download fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reports List -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> All Reports</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="reportsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Report Date</th>
                            <th>File</th>
                            <th>Views</th>
                            <th>Downloads</th>
                            <th>Status</th>
                            <th>Uploaded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $report): ?>
                        <tr>
                            <td><?php echo $report['report_id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($report['title']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($report['description'], 0, 50)); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $report['report_type']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                            <td>
                                <i class="fas fa-file-<?php 
                                    echo $report['file_type'] == 'pdf' ? 'pdf text-danger' : 
                                        (in_array($report['file_type'], ['doc', 'docx']) ? 'word text-primary' : 
                                        (in_array($report['file_type'], ['xls', 'xlsx']) ? 'excel text-success' : 'alt')); 
                                ?> fa-2x"></i>
                                <br>
                                <small><?php echo round($report['file_size'] / 1024); ?> KB</small>
                            </td>
                            <td><?php echo number_format($report['view_count']); ?></td>
                            <td><?php echo number_format($report['download_count']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                        <option value="Published" <?php echo $report['status'] == 'Published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="Draft" <?php echo $report['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="Archived" <?php echo $report['status'] == 'Archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td><?php echo $report['uploaded_by_name']; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo $report['file_path']; ?>" class="btn btn-info" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="download_report.php?id=<?php echo $report['report_id']; ?>" class="btn btn-success">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button class="btn btn-warning" onclick="editReport(<?php echo $report['report_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $report['report_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upload Report Modal -->
<div class="modal fade" id="uploadReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-upload"></i> Upload Church Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label>Report Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Report Type *</label>
                            <select name="report_type" class="form-control" required>
                                <option value="Annual">Annual Report</option>
                                <option value="Quarterly">Quarterly Report</option>
                                <option value="Monthly">Monthly Report</option>
                                <option value="Financial">Financial Report</option>
                                <option value="Mission">Mission Report</option>
                                <option value="Activity">Activity Report</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the report..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Report Date *</label>
                            <input type="date" name="report_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Published">Published (Visible to members)</option>
                                <option value="Draft">Draft (Hidden from members)</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Upload File *</label>
                        <input type="file" name="report_file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.png">
                        <small class="text-muted">Allowed: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG (Max 10MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_report" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Report Modal -->
<div class="modal fade" id="editReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editReportForm">
                <div class="modal-body">
                    <input type="hidden" name="report_id" id="edit_report_id">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label>Report Title *</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Report Type *</label>
                            <select name="report_type" id="edit_report_type" class="form-control" required>
                                <option value="Annual">Annual Report</option>
                                <option value="Quarterly">Quarterly Report</option>
                                <option value="Monthly">Monthly Report</option>
                                <option value="Financial">Financial Report</option>
                                <option value="Mission">Mission Report</option>
                                <option value="Activity">Activity Report</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Report Date *</label>
                            <input type="date" name="report_date" id="edit_report_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="Published">Published</option>
                                <option value="Draft">Draft</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_report" class="btn btn-primary">Update Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#reportsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });
});

function editReport(id) {
    // Fetch report details via AJAX
    $.ajax({
        url: 'ajax/get_report_details.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            var report = JSON.parse(response);
            $('#edit_report_id').val(report.report_id);
            $('#edit_title').val(report.title);
            $('#edit_report_type').val(report.report_type);
            $('#edit_description').val(report.description);
            $('#edit_report_date').val(report.report_date);
            $('#edit_status').val(report.status);
            $('#editReportModal').modal('show');
        }
    });
}

// Handle edit form submission
$('#editReportForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'ajax/update_report.php',
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            location.reload();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>