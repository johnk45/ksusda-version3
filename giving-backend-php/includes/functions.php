<?php
// includes/functions.php

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate phone number (Kenyan format)
 */
function validatePhoneNumber($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/\s+/', '', $phone);
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if it's a valid Kenyan number
    $patterns = [
        '/^254[0-9]{9}$/',           // 2547XXXXXXXX
        '/^0[0-9]{9}$/',             // 07XXXXXXXX
        '/^\+254[0-9]{9}$/',         // +2547XXXXXXXX
        '/^7[0-9]{8}$/',             // 7XXXXXXXX
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Format phone number to 254XXXXXXXXX
 */
function formatPhoneNumber($phone) {
    $phone = preg_replace('/\s+/', '', $phone);
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Remove leading +
    if (substr($phone, 0, 1) === '+') {
        $phone = substr($phone, 1);
    }
    
    // Add 254 if missing
    if (substr($phone, 0, 1) === '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) !== '254') {
        $phone = '254' . $phone;
    }
    
    return $phone;
}

/**
 * Generate unique transaction ID
 */
function generateTransactionId($prefix = 'GIV') {
    return $prefix . date('YmdHis') . rand(100, 999);
}

/**
 * Log transaction data
 */
function logTransaction($transactionId, $action, $requestData, $responseData = null, $ip = null) {
    global $db;
    
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO transaction_logs (
        transaction_id, action, request_data, response_data, ip_address, user_agent
    ) VALUES (
        :transaction_id, :action, :request_data, :response_data, :ip_address, :user_agent
    )";
    
    $stmt = $db->prepare($sql);
    
    return $stmt->execute([
        ':transaction_id' => $transactionId,
        ':action' => $action,
        ':request_data' => is_array($requestData) ? json_encode($requestData) : $requestData,
        ':response_data' => $responseData ? json_encode($responseData) : null,
        ':ip_address' => $ip,
        ':user_agent' => $userAgent,
    ]);
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send success response
 */
function sendSuccess($data = null, $message = 'Success') {
    sendJsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send error response
 */
function sendError($message = 'Error', $statusCode = 400) {
    sendJsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * Validate amount
 */
function validateAmount($amount) {
    return is_numeric($amount) && $amount > 0 && $amount <= 1000000;
}

/**
 * Mask sensitive data for logging
 */
function maskSensitiveData($data, $fields = ['password', 'passkey', 'consumer_secret', 'PIN']) {
    if (!is_array($data)) {
        return $data;
    }
    
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $data[$field] = '***MASKED***';
        }
    }
    
    return $data;
}

/**
 * Check if request is from M-Pesa (validate IP)
 */
function isMpesaCallback() {
    $allowedIPs = [
        '196.201.214.200',
        '196.201.214.206',
        '196.201.213.114',
        '196.201.214.207',
        '196.201.214.208',
        '196.201.213.44',
        '196.201.212.127',
        '196.201.212.138',
        '196.201.212.129',
        '196.201.212.136',
        '196.201.212.74',
        '196.201.212.69',
        '196.201.212.72',
        '196.201.212.71',
        '196.201.212.70',
        '196.201.212.73',
    ];
    
    $clientIP = getClientIP();
    
    // In production, uncomment this check
    // return in_array($clientIP, $allowedIPs);
    
    // For testing, allow all
    return true;
}

/**
 * Get offering categories
 */
function getOfferingCategories() {
    return [
        'tithe' => 'TITHE',
        'local' => 'LOCAL OFFERINGS',
        'conference' => 'CONFERENCE/UNION OFFERINGS',
        'world' => 'WORLD OFFERINGS'
    ];
}

/**
 * Calculate offering breakdown
 */
function calculateOfferings($offerings) {
    $summary = [
        'total' => 0,
        'categories' => []
    ];
    
    if (!is_array($offerings)) {
        return $summary;
    }
    
    foreach ($offerings as $offering) {
        $category = $offering['category'] ?? 'other';
        $amount = floatval($offering['amount'] ?? 0);
        
        if (!isset($summary['categories'][$category])) {
            $summary['categories'][$category] = 0;
        }
        
        $summary['categories'][$category] += $amount;
        $summary['total'] += $amount;
    }
    
    return $summary;
}

/**
 * Generate receipt number
 */
function generateReceiptNumber() {
    return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Send email notification (using PHPMailer or mail())
 */
function sendDonationReceipt($email, $data) {
    $subject = "Donation Receipt - Kisii University SDA Church";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .receipt { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
            .header { background: #006B75; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 10px; border-bottom: 1px solid #eee; }
            .amount { font-size: 24px; font-weight: bold; color: #006B75; }
        </style>
    </head>
    <body>
        <div class='receipt'>
            <div class='header'>
                <h2>Kisii University SDA Church</h2>
                <p>Donation Receipt</p>
            </div>
            <div class='content'>
                <p><strong>Receipt Number:</strong> {$data['receipt_number']}</p>
                <p><strong>Date:</strong> {$data['date']}</p>
                <p><strong>Transaction ID:</strong> {$data['transaction_id']}</p>
                <p><strong>Payment Method:</strong> {$data['payment_method']}</p>
                
                <h3>Donation Details</h3>
                <table>
                    <tr><td><strong>Amount</strong></td><td class='amount'>KSh " . number_format($data['amount'], 2) . "</td></tr>
                    <tr><td><strong>Account Reference</strong></td><td>{$data['account_reference']}</td></tr>
                    <tr><td><strong>Status</strong></td><td>{$data['status']}</td></tr>
                </table>
                
                " . (!empty($data['mpesa_receipt']) ? "
                <p><strong>M-Pesa Receipt:</strong> {$data['mpesa_receipt']}</p>
                " : "") . "
                
                <p><strong>Thank you for your generous giving!</strong></p>
                <p>" . ($data['is_tax_deductible'] ? 'This donation is tax-deductible.' : '') . "</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Kisii University SDA Church. All rights reserved.</p>
                <p>Kisii University Campus, Kisii, Kenya</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: donations@kisiiuniversitysdachurch.org" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'KSh') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Get donation status badge
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'completed' => '<span class="badge badge-success">Completed</span>',
        'failed' => '<span class="badge badge-danger">Failed</span>',
        'timeout' => '<span class="badge badge-secondary">Timeout</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length));
}

/**
 * Check if donation is valid
 */
function isValidDonation($data) {
    // Check required fields
    if (empty($data['phone_number'])) {
        return ['valid' => false, 'message' => 'Phone number is required'];
    }
    
    if (empty($data['amount']) || $data['amount'] <= 0) {
        return ['valid' => false, 'message' => 'Valid amount is required'];
    }
    
    if (!validatePhoneNumber($data['phone_number'])) {
        return ['valid' => false, 'message' => 'Invalid phone number format'];
    }
    
    return ['valid' => true];
}