<?php
// admin_sermons.php - Admin interface for managing sermons
require_once 'config/sermon_config.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$sermonManager = new SermonManager();
$message = '';
$error = '';

// Handle sermon addition
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sermon'])) {
    $result = $sermonManager->addSermon(
        $_POST['title'],
        $_POST['preacher'],
        $_POST['youtube_url'],
        $_POST['sermon_date'],
        $_POST['description'],
        $_POST['scripture']
    );
    
    if($result['success']) {
        $message = "Sermon added successfully!";
    } else {
        $error = $result['error'];
    }
}

// Handle sermon deletion
if(isset($_GET['delete'])) {
    if($sermonManager->deleteSermon($_GET['delete'])) {
        $message = "Sermon deleted successfully!";
    } else {
        $error = "Failed to delete sermon!";
    }
}

// Get all sermons for listing using the class method
$sermons = $sermonManager->getAllSermons();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Sermon Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .preview-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .thumbnail-preview {
            width: 120px;
            border-radius: 8px;
        }
        .sermon-thumb {
            width: 60px;
            border-radius: 5px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
                <h2><i class="fas fa-church"></i> Sermon Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSermonModal">
                    <i class="fas fa-plus"></i> Add New Sermon
                </button>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> All Sermons</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="sermonsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Thumbnail</th>
                                    <th>Title</th>
                                    <th>Preacher</th>
                                    <th>Date</th>
                                    <th>Scripture</th>
                                    <th>Views</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sermons as $sermon): ?>
                                <tr>
                                    <td><?php echo $sermon['sermon_id']; ?></td>
                                    <td>
                                        <img src="https://img.youtube.com/vi/<?php echo $sermon['youtube_id']; ?>/0.jpg" 
                                             class="sermon-thumb" alt="Thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($sermon['title']); ?></td>
                                    <td><?php echo htmlspecialchars($sermon['preacher']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sermon['sermon_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($sermon['scripture'] ?: '-'); ?></td>
                                    <td><?php echo number_format($sermon['views']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $sermon['status'] == 'published' ? 'success' : 'secondary'; ?>">
                                            <?php echo $sermon['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_sermon.php?id=<?php echo $sermon['sermon_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $sermon['sermon_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this sermon?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                     </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Sermon Modal -->
<div class="modal fade" id="addSermonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Sermon</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="sermonForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sermon Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preacher *</label>
                            <input type="text" name="preacher" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">YouTube URL *</label>
                            <input type="url" name="youtube_url" id="youtube_url" class="form-control" 
                                   placeholder="https://www.youtube.com/watch?v=..." required>
                            <small class="text-muted">Supports youtube.com/watch?v= or youtu.be/ formats</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sermon Date *</label>
                            <input type="date" name="sermon_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Scripture Reference</label>
                        <input type="text" name="scripture" class="form-control" placeholder="e.g., John 3:16">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Brief description of the sermon..."></textarea>
                    </div>
                    
                    <!-- Preview Section -->
                    <div id="previewSection" style="display: none;">
                        <div class="preview-card">
                            <h6>Preview:</h6>
                            <div class="d-flex align-items-center">
                                <img id="previewThumbnail" class="thumbnail-preview me-3">
                                <div>
                                    <strong id="previewTitle"></strong><br>
                                    <small id="previewUrl" class="text-muted"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_sermon" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Sermon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preview YouTube video
$('#youtube_url').on('input', function() {
    var url = $(this).val();
    var videoId = extractYoutubeId(url);
    
    if(videoId) {
        $('#previewThumbnail').attr('src', 'https://img.youtube.com/vi/' + videoId + '/0.jpg');
        $('#previewTitle').text('Video ID: ' + videoId);
        $('#previewUrl').text(url);
        $('#previewSection').show();
    } else {
        $('#previewSection').hide();
    }
});

function extractYoutubeId(url) {
    var patterns = [
        /(?:youtube\.com\/watch\?v=)([^&]+)/,
        /(?:youtu\.be\/)([^?]+)/,
        /(?:youtube\.com\/embed\/)([^\/]+)/
    ];
    
    for(var i = 0; i < patterns.length; i++) {
        var match = url.match(patterns[i]);
        if(match) return match[1];
    }
    return null;
}

// Initialize DataTable if you have the library
// $(document).ready(function() {
//     $('#sermonsTable').DataTable();
// });
</script>
</body>
</html>