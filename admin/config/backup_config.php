<?php
/**
 * config/backup_config.php - Fixed Backup Manager
 */

class BackupManager {
    private $db;
    private $backup_dir;
    private $log_file;
    
    public function __construct() {
        require_once 'database.php';
        $database = new Database();
        $this->db = $database->getConnection();
        
        // 🔥 FIXED PATH - Use absolute path from project root
        $this->backup_dir = dirname(__DIR__) . '/backups/';
        $this->log_file = $this->backup_dir . 'backup_log.txt';
        
        // Create backup directory if not exists
        if (!file_exists($this->backup_dir)) {
            mkdir($this->backup_dir, 0777, true);
        }
    }
    
    /**
     * Create a database backup using PHP method (no mysqldump required)
     */
    public function createBackup($triggered_by = 'system') {
        $date = date('Y-m-d_H-i-s');
        $filename = "church_backup_{$date}.sql";
        $filepath = $this->backup_dir . $filename;
        
        try {
            // Get all tables
            $tables = [];
            $stmt = $this->db->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            if (empty($tables)) {
                return ['success' => false, 'error' => 'No tables found in database'];
            }
            
            // Open file for writing
            $handle = fopen($filepath, 'w');
            if (!$handle) {
                return ['success' => false, 'error' => 'Cannot create backup file. Check folder permissions.'];
            }
            
            // Write header
            fwrite($handle, "-- Church Management System Backup\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: " . $this->db->query("SELECT DATABASE()")->fetchColumn() . "\n");
            fwrite($handle, "-- Tables: " . implode(', ', $tables) . "\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
            
            // Backup each table
            foreach ($tables as $table) {
                // Get table structure
                $create = $this->db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($handle, $create['Create Table'] . ";\n\n");
                
                // Get table data
                $rows = $this->db->query("SELECT * FROM `$table`");
                $rowCount = $rows->rowCount();
                
                if ($rowCount > 0) {
                    // Get column names
                    $columns = [];
                    for ($i = 0; $i < $rows->columnCount(); $i++) {
                        $col = $rows->getColumnMeta($i);
                        $columns[] = "`{$col['name']}`";
                    }
                    $columns_str = implode(", ", $columns);
                    
                    $batch = [];
                    $counter = 0;
                    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                        $values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $values[] = "NULL";
                            } else {
                                $values[] = $this->db->quote($value);
                            }
                        }
                        $batch[] = "(" . implode(", ", $values) . ")";
                        $counter++;
                        
                        // Insert in batches of 100 rows for better performance
                        if ($counter % 100 == 0) {
                            fwrite($handle, "INSERT INTO `$table` ($columns_str) VALUES \n" . implode(",\n", $batch) . ";\n");
                            $batch = [];
                        }
                    }
                    
                    // Write remaining rows
                    if (!empty($batch)) {
                        fwrite($handle, "INSERT INTO `$table` ($columns_str) VALUES \n" . implode(",\n", $batch) . ";\n");
                    }
                    fwrite($handle, "\n");
                }
            }
            
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            
            // Check if file was created successfully
            if (!file_exists($filepath) || filesize($filepath) == 0) {
                return ['success' => false, 'error' => 'Backup file is empty'];
            }
            
            // Compress the file
            if (function_exists('gzopen')) {
                $gz_filepath = $filepath . '.gz';
                $content = file_get_contents($filepath);
                $fp = gzopen($gz_filepath, 'wb9');
                gzwrite($fp, $content);
                gzclose($fp);
                unlink($filepath); // Remove uncompressed
                $filepath = $gz_filepath;
                $filesize = filesize($gz_filepath);
            } else {
                $filesize = filesize($filepath);
            }
            
            // Log the backup
            $this->logBackup(basename($filepath), $filesize, $triggered_by);
            
            // Clean old backups (keep last 10)
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'filename' => basename($filepath),
                'filesize' => $filesize,
                'filepath' => $filepath
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
                $filepath = $this->backup_dir . $backup['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
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
}
?>