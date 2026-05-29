<?php
// secure_upload.php - Secure file upload handling
require_once 'config/database.php';
require_once 'config/security.php';

class SecureUpload {
    private $db;
    private $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];
    
    private $maxFileSize = 10485760; // 10MB
    private $uploadPath = 'uploads/reports/';
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function uploadReport($file, $metadata) {
        // Validate CSRF
        if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
            Security::logSecurityEvent('Invalid CSRF Token in Upload');
            return ['success' => false, 'error' => 'Security validation failed'];
        }
        
        // Check user permissions
        if (!Security::hasPermission('Secretary')) {
            Security::logSecurityEvent('Unauthorized Upload Attempt');
            return ['success' => false, 'error' => 'Permission denied'];
        }
        
        // Validate file
        $validationErrors = Security::validateFile($file, $this->allowedTypes, $this->maxFileSize);
        if (!empty($validationErrors)) {
            return ['success' => false, 'error' => implode(', ', $validationErrors)];
        }
        
        // Create secure filename
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $secureFilename = time() . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        
        // Create upload directory if not exists
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        
        $targetPath = $this->uploadPath . $secureFilename;
        
        // Move file with virus scan simulation
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Set secure file permissions
            chmod($targetPath, 0644);
            
            // Insert into database with prepared statement
            $query = "INSERT INTO church_reports (title, report_type, description, file_name, file_path, 
                      file_size, file_type, report_date, uploaded_by, status) 
                      VALUES (:title, :report_type, :description, :file_name, :file_path, 
                      :file_size, :file_type, :report_date, :uploaded_by, :status)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':title' => Security::sanitizeInput($metadata['title']),
                ':report_type' => Security::sanitizeInput($metadata['report_type']),
                ':description' => Security::sanitizeInput($metadata['description']),
                ':file_name' => $secureFilename,
                ':file_path' => $targetPath,
                ':file_size' => $file['size'],
                ':file_type' => $extension,
                ':report_date' => $metadata['report_date'],
                ':uploaded_by' => $_SESSION['user_id'],
                ':status' => $metadata['status']
            ]);
            
            if ($result) {
                Security::logSecurityEvent('File Uploaded Successfully', [
                    'filename' => $secureFilename,
                    'size' => $file['size'],
                    'type' => $extension
                ]);
                return ['success' => true, 'message' => 'File uploaded successfully'];
            }
        }
        
        Security::logSecurityEvent('File Upload Failed', ['error' => 'Move failed']);
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    public function deleteReport($reportId) {
        // Verify permissions
        if (!Security::hasPermission('Admin')) {
            Security::logSecurityEvent('Unauthorized Delete Attempt', ['report_id' => $reportId]);
            return false;
        }
        
        // Get file path
        $query = "SELECT file_path FROM church_reports WHERE report_id = :report_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':report_id' => $reportId]);
        $report = $stmt->fetch();
        
        if ($report && file_exists($report['file_path'])) {
            // Delete file
            unlink($report['file_path']);
            
            // Delete from database
            $delete = "DELETE FROM church_reports WHERE report_id = :report_id";
            $stmt = $this->db->prepare($delete);
            $stmt->execute([':report_id' => $reportId]);
            
            Security::logSecurityEvent('Report Deleted', ['report_id' => $reportId]);
            return true;
        }
        
        return false;
    }
}
?> 