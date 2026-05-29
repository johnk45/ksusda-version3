<?php
/**
 * edit_sermon.php - Edit YouTube Sermon (Admin Only)
 * Allows admin to edit existing YouTube sermons in the database
 */

require_once 'config/database.php';
require_once 'config/sermon_config.php';
require_once 'includes/header.php';

// Check if user is logged in
if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();
$sermonManager = new SermonManager();

$sermon_id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Get sermon details from database
$query = "SELECT * FROM sermons WHERE sermon_id = :sermon_id";
$stmt = $db->prepare($query);
$stmt->execute([':sermon_id' => $sermon_id]);
$sermon = $stmt->fetch(PDO::FETCH_ASSOC);

// If sermon not found, redirect back
if(!$sermon) {
    $_SESSION['error'] = "Sermon not found!";
    header("Location: admin_sermons.php");
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_sermon'])) {
    $title = trim($_POST['title']);
    $preacher = trim($_POST['preacher']);
    $youtube_url = trim($_POST['youtube_url']);
    $sermon_date = $_POST['sermon_date'];
    $description = trim($_POST['description']);
    $scripture = trim($_POST['scripture']);
    $status = $_POST['status'];
    
    // Validate inputs
    $errors = [];
    if(empty($title)) $errors[] = "Title is required";
    if(empty($preacher)) $errors[] = "Preacher name is required";
    if(empty($youtube_url)) $errors[] = "YouTube URL is required";
    if(empty($sermon_date)) $errors[] = "Sermon date is required";
    
    // Extract YouTube ID if URL changed
    $youtube_id = $sermon['youtube_id'];
    if($youtube_url != $sermon['youtube_url']) {
        $youtube_id = $sermonManager->extractYoutubeId($youtube_url);
        if(!$youtube_id) {
            $errors[] = "Invalid YouTube URL. Please check and try again.";
        }
    }
    
    if(empty($errors)) {
        $update_query = "UPDATE sermons SET 
                         title = :title,
                         preacher = :preacher,
                         youtube_url = :youtube_url,
                         youtube_id = :youtube_id,
                         sermon_date = :sermon_date,
                         description = :description,
                         scripture = :scripture,
                         status = :status,
                         updated_at = NOW()
                         WHERE sermon_id = :sermon_id";
        
        $update_stmt = $db->prepare($update_query);
        $result = $update_stmt->execute([
            ':title' => $title,
            ':preacher' => $preacher,
            ':youtube_url' => $youtube_url,
            ':youtube_id' => $youtube_id,
            ':sermon_date' => $sermon_date,
            ':description' => $description,
            ':scripture' => $scripture,
            ':status' => $status,
            ':sermon_id' => $sermon_id
        ]);
        
        if($result) {
            $success = "Sermon updated successfully!";
            // Refresh sermon data
            $stmt = $db->prepare($query);
            $stmt->execute([':sermon_id' => $sermon_id]);
            $sermon = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update sermon. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Generate thumbnail URL
$thumbnail_url = "https://img.youtube.com/vi/{$sermon['youtube_id']}/hqdefault.jpg";
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fab fa-youtube"></i> Edit YouTube Sermon</h2>
        <div>
            <a href="admin_sermons.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sermons
            </a>
            <a href="sermons.php" class="btn btn-info" target="_blank">
                <i class="fas fa-eye"></i> View Live
            </a>
        </div>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Sermon Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="editSermonForm">
                        <div class="mb-3">
                            <label class="form-label">Sermon Title *</label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($sermon['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Preacher/Speaker *</label>
                            <input type="text" name="preacher" class="form-control" 
                                   value="<?php echo htmlspecialchars($sermon['preacher']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">YouTube URL *</label>
                            <input type="url" name="youtube_url" id="youtube_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($sermon['youtube_url']); ?>" required>
                            <small class="text-muted">Supports youtube.com/watch?v= or youtu.be/ formats</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sermon Date *</label>
                                <input type="date" name="sermon_date" class="form-control" 
                                       value="<?php echo $sermon['sermon_date']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="published" <?php echo $sermon['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="draft" <?php echo $sermon['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Scripture Reference</label>
                            <input type="text" name="scripture" class="form-control" 
                                   value="<?php echo htmlspecialchars($sermon['scripture']); ?>"
                                   placeholder="e.g., John 3:16">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" 
                                      placeholder="Brief description of the sermon..."><?php echo htmlspecialchars($sermon['description']); ?></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Note:</strong> View count (<?php echo number_format($sermon['views']); ?>) is automatically tracked and cannot be edited manually.
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="update_sermon" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Sermon
                            </button>
                            <a href="admin_sermons.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Thumbnail Preview -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-image"></i> Thumbnail Preview</h5>
                </div>
                <div class="card-body text-center">
                    <img id="thumbnailPreview" src="<?php echo $thumbnail_url; ?>" 
                         alt="YouTube Thumbnail" class="img-fluid rounded mb-3"
                         style="max-width: 100%; border: 1px solid #ddd;">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> Thumbnail is automatically fetched from YouTube
                    </p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshThumbnail()">
                        <i class="fas fa-sync-alt"></i> Refresh Thumbnail
                    </button>
                </div>
            </div>
            
            <!-- Sermon Stats -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Sermon Statistics</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Sermon ID:</th>
                            <td><?php echo $sermon['sermon_id']; ?></td>
                        </tr>
                        <tr>
                            <th>YouTube ID:</th>
                            <td><code><?php echo $sermon['youtube_id']; ?></code></td>
                        </tr>
                        <tr>
                            <th>Total Views:</th>
                            <td><strong><?php echo number_format($sermon['views']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td><?php echo date('M d, Y g:i A', strtotime($sermon['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('M d, Y g:i A', strtotime($sermon['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Sermon Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="previewThumbnail" src="" alt="Thumbnail" class="img-fluid rounded" style="max-width: 100%;">
                </div>
                <h4 id="previewTitle"></h4>
                <p><strong>Preacher:</strong> <span id="previewPreacher"></span></p>
                <p><strong>Date:</strong> <span id="previewDate"></span></p>
                <p><strong>Scripture:</strong> <span id="previewScripture"></span></p>
                <p><strong>Description:</strong> <span id="previewDescription"></span></p>
                <hr>
                <p class="text-muted">
                    <i class="fab fa-youtube"></i> This sermon will appear on the public sermons page with the YouTube player.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Preview thumbnail when YouTube URL changes
document.getElementById('youtube_url').addEventListener('input', function() {
    const url = this.value;
    const videoId = extractYoutubeId(url);
    
    if(videoId) {
        const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
        document.getElementById('thumbnailPreview').src = thumbnailUrl;
    }
});

// Extract YouTube ID from URL
function extractYoutubeId(url) {
    const patterns = [
        /(?:youtube\.com\/watch\?v=)([^&]+)/,
        /(?:youtu\.be\/)([^?]+)/,
        /(?:youtube\.com\/embed\/)([^\/]+)/
    ];
    
    for(let i = 0; i < patterns.length; i++) {
        const match = url.match(patterns[i]);
        if(match) return match[1];
    }
    return null;
}

// Refresh thumbnail
function refreshThumbnail() {
    const url = document.getElementById('youtube_url').value;
    const videoId = extractYoutubeId(url);
    
    if(videoId) {
        const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg?t=${Date.now()}`;
        document.getElementById('thumbnailPreview').src = thumbnailUrl;
        showNotification('Thumbnail refreshed!', 'success');
    } else {
        showNotification('Invalid YouTube URL', 'danger');
    }
}

// Preview sermon
function previewSermon() {
    const title = document.querySelector('input[name="title"]').value;
    const preacher = document.querySelector('input[name="preacher"]').value;
    const date = document.querySelector('input[name="sermon_date"]').value;
    const scripture = document.querySelector('input[name="scripture"]').value;
    const description = document.querySelector('textarea[name="description"]').value;
    const url = document.getElementById('youtube_url').value;
    const videoId = extractYoutubeId(url);
    
    if(videoId) {
        document.getElementById('previewThumbnail').src = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
    }
    document.getElementById('previewTitle').textContent = title || 'Untitled Sermon';
    document.getElementById('previewPreacher').textContent = preacher || 'Not specified';
    document.getElementById('previewDate').textContent = date ? new Date(date).toLocaleDateString() : 'Not specified';
    document.getElementById('previewScripture').textContent = scripture || 'Not specified';
    document.getElementById('previewDescription').textContent = description || 'No description available.';
    
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

// Show notification
function showNotification(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 3000);
}

// Add preview button to form
document.addEventListener('DOMContentLoaded', function() {
    const formActions = document.querySelector('.d-flex.gap-2');
    if(formActions) {
        const previewBtn = document.createElement('button');
        previewBtn.type = 'button';
        previewBtn.className = 'btn btn-info';
        previewBtn.innerHTML = '<i class="fas fa-eye"></i> Preview';
        previewBtn.onclick = previewSermon;
        formActions.insertBefore(previewBtn, formActions.firstChild);
    }
});
</script>

<style>
    .card {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: none;
    }
    .card-header {
        border-bottom: none;
    }
    #thumbnailPreview {
        transition: all 0.3s;
    }
    #thumbnailPreview:hover {
        transform: scale(1.02);
    }
</style>

<?php require_once 'includes/footer.php'; ?>