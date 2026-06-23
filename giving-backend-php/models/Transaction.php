<?php
// models/Transaction.php

require_once __DIR__ . '/../config/database.php';

class Transaction {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Log a transaction
     */
    public function log($data) {
        $sql = "INSERT INTO transaction_logs (
            transaction_id, action, request_data, response_data, 
            ip_address, user_agent
        ) VALUES (
            :transaction_id, :action, :request_data, :response_data,
            :ip_address, :user_agent
        )";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':action' => $data['action'] ?? 'unknown',
            ':request_data' => isset($data['request_data']) ? json_encode($data['request_data']) : null,
            ':response_data' => isset($data['response_data']) ? json_encode($data['response_data']) : null,
            ':ip_address' => $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            ':user_agent' => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
        ]);
    }
    
    /**
     * Get transaction by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM transaction_logs WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get transactions by transaction ID
     */
    public function getByTransactionId($transactionId) {
        $sql = "SELECT * FROM transaction_logs WHERE transaction_id = :transaction_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':transaction_id' => $transactionId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get transactions by action
     */
    public function getByAction($action, $limit = 50) {
        $sql = "SELECT * FROM transaction_logs WHERE action = :action ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':action' => $action,
            ':limit' => $limit
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent transactions
     */
    public function getRecent($limit = 20) {
        $sql = "SELECT * FROM transaction_logs ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':limit' => $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get transactions by date range
     */
    public function getByDateRange($startDate, $endDate) {
        $sql = "SELECT * FROM transaction_logs 
                WHERE created_at BETWEEN :start_date AND :end_date 
                ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get transaction statistics
     */
    public function getStats($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_logs,
                    COUNT(DISTINCT transaction_id) as unique_transactions,
                    COUNT(CASE WHEN action = 'stkpush_initiated' THEN 1 END) as stkpush_count,
                    COUNT(CASE WHEN action = 'callback_received' THEN 1 END) as callback_count,
                    COUNT(CASE WHEN action = 'payment_completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN action = 'payment_failed' THEN 1 END) as failed_count
                FROM transaction_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetch();
    }
    
    /**
     * Clean old logs (keep only last 30 days by default)
     */
    public function cleanOldLogs($days = 30) {
        $sql = "DELETE FROM transaction_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':days' => $days]);
    }
    
    /**
     * Get transaction timeline for a specific ID
     */
    public function getTimeline($transactionId) {
        $logs = $this->getByTransactionId($transactionId);
        
        $timeline = [];
        foreach ($logs as $log) {
            $timeline[] = [
                'time' => $log['created_at'],
                'action' => $log['action'],
                'data' => json_decode($log['request_data'], true),
                'response' => json_decode($log['response_data'], true),
                'ip' => $log['ip_address'],
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Check if transaction exists
     */
    public function exists($transactionId) {
        $sql = "SELECT COUNT(*) as count FROM transaction_logs WHERE transaction_id = :transaction_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':transaction_id' => $transactionId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Get transaction by CheckoutRequestID
     */
    public function getByCheckoutRequestId($checkoutRequestId) {
        $sql = "SELECT * FROM transaction_logs WHERE request_data LIKE :checkout_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':checkout_id' => '%' . $checkoutRequestId . '%']);
        return $stmt->fetch();
    }
    
    /**
     * Get error summary
     */
    public function getErrorSummary($days = 7) {
        $sql = "SELECT 
                    action,
                    COUNT(*) as error_count,
                    JSON_ARRAYAGG(DISTINCT response_data) as errors
                FROM transaction_logs 
                WHERE response_data IS NOT NULL 
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY action
                ORDER BY error_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll();
    }
}