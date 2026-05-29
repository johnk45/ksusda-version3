<?php
/**
 * backup_config.php - Central backup configuration
 */

class BackupManager {
    private $db;
    private $backup_dir;
    private $log_file;
    
    public function __construct() {
        require_once 'database.php';
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Create backup directories
        $this->backup_dir = __DIR__ . '/../backups/';
        $this->log_file = $this->backup_dir . 'backup_log.txt';
        
        if (!file_exists($this->backup_dir)) {
            mkdir($this->backup_dir, 0777, true);
        }
    }
    
    /**
     * Create a database backup
     */
    public function createBackup($triggered_by = 'system') {
        $date = date('Y-m-d_H-i-s');
        $filename = "church_backup_{$date}.sql";
        $filepath = $this->backup_dir . $filename;
        
        // Get database credentials from your database.php
        $db_config = $this->getDbConfig();
        
        // Create backup using mysqldump if available
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
            escapeshellarg($db_config['user']),
            escapeshellarg($db_config['pass']),
            escapeshellarg($db_config['host']),
            escapeshellarg($db_config['name']),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && file_exists($filepath)) {
            // Compress the file
            $gz_filepath = $filepath . '.gz';
            $fp = gzopen($gz_filepath, 'wb9');
            gzwrite($fp, file_get_contents($filepath));
            gzclose($fp);
            unlink($filepath); // Remove uncompressed
            
            // Log the backup
            $this->logBackup($filename . '.gz', filesize($gz_filepath), $triggered_by);
            
            // Clean old backups (keep last 10)
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'filename' => $filename . '.gz',
                'filesize' => filesize($gz_filepath),
                'filepath' => $gz_filepath
            ];
        }
        
        return ['success' => false, 'error' => 'Backup failed'];
    }
    
    /**
     * Get list of all backups
     */
    public function getBackups() {
        $backups = glob($this->backup_dir . "*.sql.gz");
        $backup_list = [];
        
        foreach ($backups as $file) {
            $backup_list[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file
            ];
        }
        
        // Sort by date (newest first)
        usort($backup_list, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backup_list;
    }
    
    /**
     * Delete a backup
     */
    public function deleteBackup($filename) {
        $filepath = $this->backup_dir . basename($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
            $this->logBackup($filename, 0, 'deleted');
            return true;
        }
        return false;
    }
    
    /**
     * Clean old backups (keep only last 10)
     */
    private function cleanOldBackups() {
        $backups = $this->getBackups();
        if (count($backups) > 10) {
            $to_delete = array_slice($backups, 10);
            foreach ($to_delete as $backup) {
                unlink($this->backup_dir . $backup['filename']);
            }
        }
    }
    
    /**
     * Log backup activities
     */
    private function logBackup($filename, $size, $triggered_by) {
        $log_entry = date('Y-m-d H:i:s') . " | {$triggered_by} | {$filename} | " . round($size/1024, 2) . " KB\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Get database configuration
     */
    private function getDbConfig() {
        // Read from your database.php file
        return [
            'user' => 'root',
            'pass' => '',
            'host' => 'localhost',
            'name' => 'church_db'
        ];
    }
}
?>