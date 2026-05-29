<?php
// secure_register.php - Secure member registration
require_once 'config/database.php';
require_once 'config/security.php';
require_once 'includes/Validation.php';

class SecureRegistration {
    use Validation;
    
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function registerMember($data) {
        try {
            // Validate CSRF
            Security::verifyCSRFToken($data['csrf_token']);
            
            // Validate input
            $this->validateRequired('First Name', $data['first_name']);
            $this->validateRequired('Last Name', $data['last_name']);
            $this->validateEmail($data['email']);
            $this->validatePhone($data['phone']);
            $this->validateDate($data['dob']);
            
            // Check for duplicate email/phone
            $check = "SELECT COUNT(*) FROM members WHERE email = :email OR phone = :phone";
            $stmt = $this->db->prepare($check);
            $stmt->execute([
                ':email' => $data['email'],
                ':phone' => $data['phone']
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Member with this email or phone already exists");
            }
            
            // Generate unique membership number
            $membership_no = 'SDA/KSU/' . date('Y') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insert member
            $query = "INSERT INTO members (membership_no, title, first_name, last_name, gender, 
                      date_of_birth, phone, email, address, join_date) 
                      VALUES (:membership_no, :title, :first_name, :last_name, :gender, 
                      :dob, :phone, :email, :address, :join_date)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':membership_no' => $membership_no,
                ':title' => $this->sanitizeInput($data['title']),
                ':first_name' => $this->sanitizeInput($data['first_name']),
                ':last_name' => $this->sanitizeInput($data['last_name']),
                ':gender' => $data['gender'],
                ':dob' => $data['dob'],
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':address' => $this->sanitizeInput($data['address']),
                ':join_date' => date('Y-m-d')
            ]);
            
            if ($result) {
                Security::logSecurityEvent('New Member Registered', [
                    'membership_no' => $membership_no,
                    'email' => $data['email']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Registration successful!',
                    'membership_no' => $membership_no
                ];
            }
            
        } catch (Exception $e) {
            Security::logSecurityEvent('Registration Failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>