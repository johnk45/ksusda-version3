<?php
// config/database.php - Secure database configuration
$SITE_NAME = "Church Management System";
require_once __DIR__ . '/security.php';

class Database {
    private $host = "localhost";
    private $db_name = "church_db";
    private $username = "root";
    private $password = "";
    public $conn;
    private $stmt;
    
    // Use prepared statements with PDO for SQL injection prevention
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // Disable emulated prepares
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch(PDOException $exception) {
            Security::logSecurityEvent('Database Connection Failed', ['error' => $exception->getMessage()]);
            die("Connection error. Please try again later.");
        }
        return $this->conn;
    }
    
    // Secure query method with automatic sanitization
    public function secureQuery($sql, $params = []) {
        $this->stmt = $this->conn->prepare($sql);
        
        // Bind parameters with type detection
        foreach ($params as $key => $value) {
            $type = PDO::PARAM_STR;
            if (is_int($value)) $type = PDO::PARAM_INT;
            elseif (is_bool($value)) $type = PDO::PARAM_BOOL;
            elseif (is_null($value)) $type = PDO::PARAM_NULL;
            
            $this->stmt->bindValue($key, $value, $type);
        }
        
        $this->stmt->execute();
        return $this->stmt;
    }
    
    // Get last insert ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->conn->rollback();
    }
}
?>