<?php
// api/result.php

// This handles timeout results from M-Pesa
header('Content-Type: application/json');


require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../includes/functions.php';

// Log the result
error_log('M-Pesa Result URL received: ' . file_get_contents('php://input'));

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Invalid request'
    ]);
    exit;
}

try {
    $donation = new Donation();
    $transaction = new Transaction();
    
    // Check if it's a timeout result
    if (isset($input['ResultCode'])) {
        $resultCode = $input['ResultCode'];
        $resultDesc = $input['ResultDesc'] ?? 'Transaction timeout';
        $checkoutRequestId = $input['CheckoutRequestID'] ?? null;
        
        if ($checkoutRequestId) {
            // Find and update the donation
            $donationData = $donation->findByCheckoutRequestId($checkoutRequestId);
            
            if ($donationData) {
                $donation->updateByTransactionId(
                    $donationData['transaction_id'],
                    [
                        'status' => $resultCode === 0 ? 'completed' : 'failed',
                        'response_code' => $resultCode,
                        'response_description' => $resultDesc,
                    ]
                );
            }
        }
        
        // Log the result
        $transaction->log([
            'transaction_id' => $checkoutRequestId ?? 'unknown',
            'action' => 'result_received',
            'request_data' => $input,
            'response_data' => ['status' => 'processed'],
        ]);
        
        // Return success
        echo json_encode([
            'ResultCode' => 0,
            'ResultDesc' => 'Result processed successfully'
        ]);
    } else {
        // Invalid result structure
        http_response_code(400);
        echo json_encode([
            'ResultCode' => 1,
            'ResultDesc' => 'Invalid result structure'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Result URL error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Internal server error'
    ]);
}