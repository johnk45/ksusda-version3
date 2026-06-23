<?php
// models/Donation.php

require_once __DIR__ . '/../config/database.php';

class Donation {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Create a new donation record
     */
    public function create($data) {
        $sql = "INSERT INTO donations (
            transaction_id, merchant_request_id, checkout_request_id,
            phone_number, email, first_name, last_name,
            amount, payment_method, offerings, account_reference,
            transaction_desc, status, response_code, response_description
        ) VALUES (
            :transaction_id, :merchant_request_id, :checkout_request_id,
            :phone_number, :email, :first_name, :last_name,
            :amount, :payment_method, :offerings, :account_reference,
            :transaction_desc, :status, :response_code, :response_description
        )";
        
        $stmt = $this->db->prepare($sql);
        
        // Convert offerings array to JSON
        $offeringsJson = isset($data['offerings']) 
            ? json_encode($data['offerings']) 
            : null;
        
        $params = [
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':merchant_request_id' => $data['merchant_request_id'] ?? null,
            ':checkout_request_id' => $data['checkout_request_id'] ?? null,
            ':phone_number' => $data['phone_number'] ?? '',
            ':email' => $data['email'] ?? null,
            ':first_name' => $data['first_name'] ?? null,
            ':last_name' => $data['last_name'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':payment_method' => $data['payment_method'] ?? 'mpesa',
            ':offerings' => $offeringsJson,
            ':account_reference' => $data['account_reference'] ?? null,
            ':transaction_desc' => $data['transaction_desc'] ?? null,
            ':status' => $data['status'] ?? 'pending',
            ':response_code' => $data['response_code'] ?? null,
            ':response_description' => $data['response_description'] ?? null,
        ];
        
        $stmt->execute($params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Update donation by transaction ID
     */
    public function updateByTransactionId($transactionId, $data) {
        $allowedFields = [
            'status', 'response_code', 'response_description',
            'mpesa_receipt_number', 'transaction_date',
            'airtel_transaction_id'
        ];
        
        $setParts = [];
        $params = [':transaction_id' => $transactionId];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $setParts[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE donations SET " . implode(', ', $setParts) . " 
                WHERE transaction_id = :transaction_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Find donation by transaction ID
     */
    public function findByTransactionId($transactionId) {
        $sql = "SELECT * FROM donations WHERE transaction_id = :transaction_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':transaction_id' => $transactionId]);
        return $stmt->fetch();
    }
    
    /**
     * Find donation by checkout request ID
     */
    public function findByCheckoutRequestId($checkoutRequestId) {
        $sql = "SELECT * FROM donations WHERE checkout_request_id = :checkout_request_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':checkout_request_id' => $checkoutRequestId]);
        return $stmt->fetch();
    }
    
    /**
     * Get donation statistics
     */
    public function getStats($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_count,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    SUM(CASE WHEN payment_method = 'mpesa' THEN amount ELSE 0 END) as mpesa_amount,
                    SUM(CASE WHEN payment_method = 'airtel' THEN amount ELSE 0 END) as airtel_amount
                FROM donations";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " WHERE created_at BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}