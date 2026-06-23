<?php
// api/status.php - Complete with database integration

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');



require_once __DIR__ . '../includes/auth.php';
require_once __DIR__ . '../models/Donation.php';
require_once __DIR__ . '../models/Transaction.php';
require_once __DIR__ . '../includes/functions.php';
require_once __DIR__ . '../config/mpesa.php';

$checkoutRequestId = $_GET['id'] ?? null;

if (!$checkoutRequestId) {
    sendError('CheckoutRequestID is required', 400);
    exit;
}

try {
    $donation = new Donation();
    $transaction = new Transaction();
    
    // First check local database
    $donationData = $donation->findByCheckoutRequestId($checkoutRequestId);
    
    if ($donationData && $donationData['status'] !== 'pending') {
        // If already completed or failed, return cached status
        sendSuccess([
            'ResultCode' => $donationData['status'] === 'completed' ? 0 : 1,
            'ResultDesc' => $donationData['response_description'] ?? '',
            'mpesaReceiptNumber' => $donationData['mpesa_receipt_number'] ?? null,
            'transactionDate' => $donationData['transaction_date'] ?? null,
            'amount' => $donationData['amount'],
            'status' => $donationData['status'],
        ], 'Status retrieved from database');
        exit;
    }
    
    // Query M-Pesa for real-time status
    $config = MpesaConfig::get();
    $apiUrl = MpesaConfig::getApiUrl();
    
    $timestamp = date('YmdHis');
    $password = base64_encode(
        $config['shortcode'] . $config['passkey'] . $timestamp
    );
    
    $token = MpesaAuth::getAccessToken();
    
    $payload = [
        'BusinessShortCode' => $config['shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestId,
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . '/mpesa/stkpushquery/v1/query',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('CURL Error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to query status: HTTP ' . $httpCode);
    }
    
    $result = json_decode($response, true);
    
    // Update local database if we have result
    if (isset($result['ResultCode'])) {
        $updateData = [
            'status' => $result['ResultCode'] === 0 ? 'completed' : 'failed',
            'response_code' => $result['ResultCode'],
            'response_description' => $result['ResultDesc'] ?? '',
        ];
        
        // Extract metadata if available
        if (isset($result['ResultDesc'])) {
            // Parse result description for receipt if available
            if ($result['ResultCode'] === 0 && $result['ResultDesc'] === 'The service request is processed successfully.') {
                // Success
            }
        }
        
        if ($donationData) {
            $donation->updateByTransactionId(
                $donationData['transaction_id'],
                $updateData
            );
        }
        
        // Log the query
        $transaction->log([
            'transaction_id' => $donationData['transaction_id'] ?? $checkoutRequestId,
            'action' => 'status_query',
            'request_data' => ['checkout_request_id' => $checkoutRequestId],
            'response_data' => $result,
        ]);
    }
    
    // Return response
    sendSuccess([
        'ResultCode' => $result['ResultCode'] ?? 1,
        'ResultDesc' => $result['ResultDesc'] ?? 'Unknown',
        'ResponseCode' => $result['ResponseCode'] ?? null,
        'ResponseDescription' => $result['ResponseDescription'] ?? null,
        'mpesaReceiptNumber' => $result['MpesaReceiptNumber'] ?? null,
        'transactionDate' => $result['TransactionDate'] ?? null,
        'status' => isset($result['ResultCode']) ? ($result['ResultCode'] === 0 ? 'completed' : 'failed') : 'pending',
    ], 'Status retrieved successfully');
    
} catch (Exception $e) {
    error_log('Status query error: ' . $e->getMessage());
    sendError($e->getMessage(), 500);
}