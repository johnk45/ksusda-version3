<?php
/**
 * admin_backup.php - Admin interface for managing backups
 */
require_once 'config/backup_config.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$backupManager = new BackupManager();
$message = '';
$error = '';

// Handle create backup
if(isset($_GET['create_backup'])) {
    $result = $backupManager->createBackup($_SESSION['username'] ?? 'admin');
    
    if($result['success']) {
        $message = "Backup created successfully! File: " . $result['filename'] . " (" . round($result['filesize']/1024, 2) . " KB)";
    } else {
        $error = "Backup failed: " . ($result['error'] ?? 'Unknown error');
    }
}

// Handle delete backup
if(isset($_GET['delete']) && isset($_GET['file'])) {
    if($backupManager->deleteBackup($_GET['file'])) {
        $message = "Backup deleted successfully!";
    } else {
        $error = "Failed to delete backup";
    }
}

// Handle download backup
if(isset($_GET['download']) && isset($_GET['file'])) {
    $file = __DIR__ . '/backups/' . basename($_GET['file']);
    if(file_exists($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }
}

$backups = $backupManager->getBackups();
?>

<style>
    .backup-stats {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .backup-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        overflow: hidden;
    }
    .backup-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }
    .backup-item:hover {
        background: #f8f9fa;
    }
    .backup-item:last-child {
        border-bottom: none;
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8rem;
    }
</style>

<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-database"></i> Database Backup Manager</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column - Stats and Create Backup -->
        <div class="col-md-4">
            <div class="backup-stats text-center">
                <i class="fas fa-hdd fa-3x mb-3"></i>
                <h3><?php echo count($backups); ?></h3>
                <p>Total Backups Available</p>
                <hr style="background: rgba(255,255,255,0.3);">
                <p class="mb-0 small">
                    <i class="fas fa-info-circle"></i> Keeping last 10 backups automatically
                </p>
            </div>
            
            <div class="backup-card">
                <div class="card-body">
                    <h5><i class="fas fa-plus-circle text-success"></i> Create New Backup</h5>
                    <p class="text-muted small">Create a manual backup of your entire database.</p>
                    <a href="?create_backup=1" class="btn btn-primary w-100" onclick="return confirm('Create a new backup? This may take a few seconds.')">
                        <i class="fas fa-database"></i> Create Backup Now
                    </a>
                </div>
            </div>
            
            <div class="backup-card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-exclamation-triangle text-warning"></i> Important</h6>
                    <ul class="small text-muted mb-0">
                        <li>✓ Backups are stored on this server</li>
                        <li>✓ For complete safety, download backups weekly</li>
                        <li>✓ Store copies on Google Drive or external drive</li>
                        <li>✓ Automatic backups run daily at 2 AM</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Backup List -->
        <div class="col-md-8">
            <div class="backup-card">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-list"></i> Available Backups</strong>
                    <span class="float-end text-muted small">Oldest backups auto-delete</span>
                </div>
                <div class="card-body p-0">
                    <?php if(count($backups) > 0): ?>
                        <?php foreach($backups as $backup): ?>
                        <div class="backup-item">
                            <div>
                                <i class="fas fa-archive text-primary"></i>
                                <strong><?php echo $backup['filename']; ?></strong>
                                <br>
                                <small class="text-muted">
                                    Size: <?php echo round($backup['size'] / 1024, 2); ?> KB | 
                                    Created: <?php echo $backup['date']; ?>
                                </small>
                            </div>
                            <div>
                                <a href="?download=1&file=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="?delete=1&file=<?php echo urlencode($backup['filename']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this backup?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-database fa-3x mb-3"></i>
                            <p>No backups found. Click "Create Backup Now" to create your first backup.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>