<?php
// api/callback.php

// This is called by Safaricom - MUST return a response quickly
header('Content-Type: application/json');

require_once __DIR__ . '/../models/Donation.php';

// Log the callback
error_log('M-Pesa Callback received: ' . file_get_contents('php://input'));

$input = json_decode(file_get_contents('php://input'), true);

// Validate callback structure
if (!isset($input['Body']) || !isset($input['Body']['stkCallback'])) {
    http_response_code(400);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Invalid callback structure'
    ]);
    exit;
}

$callback = $input['Body']['stkCallback'];
$checkoutRequestId = $callback['CheckoutRequestID'] ?? null;
$resultCode = $callback['ResultCode'] ?? 1;
$resultDesc = $callback['ResultDesc'] ?? 'Unknown error';

if (!$checkoutRequestId) {
    http_response_code(400);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Missing CheckoutRequestID'
    ]);
    exit;
}

try {
    $donation = new Donation();
    
    // Find the donation
    $donationData = $donation->findByCheckoutRequestId($checkoutRequestId);
    
    if (!$donationData) {
        error_log("Donation not found: $checkoutRequestId");
        http_response_code(404);
        echo json_encode([
            'ResultCode' => 1,
            'ResultDesc' => 'Transaction not found'
        ]);
        exit;
    }
    
    // Prepare update data
    $updateData = [
        'status' => $resultCode === 0 ? 'completed' : 'failed',
        'response_code' => $resultCode,
        'response_description' => $resultDesc,
    ];
    
    // Extract metadata if successful
    if ($resultCode === 0 && isset($callback['CallbackMetadata'])) {
        $metadata = [];
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            $metadata[$item['Name']] = $item['Value'] ?? null;
        }
        
        $updateData['mpesa_receipt_number'] = $metadata['MpesaReceiptNumber'] ?? null;
        $updateData['transaction_date'] = $metadata['TransactionDate'] ?? null;
        
        // Update phone and amount if provided
        if (isset($metadata['PhoneNumber'])) {
            // Optionally update phone number
        }
        if (isset($metadata['Amount'])) {
            // Optionally update amount
        }
        
        error_log("Payment successful: " . ($metadata['MpesaReceiptNumber'] ?? ''));
    } else {
        error_log("Payment failed: $resultDesc");
    }
    
    // Update donation
    $donation->updateByTransactionId(
        $donationData['transaction_id'],
        $updateData
    );
    
    // Return success to Safaricom
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Callback received successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Callback error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Internal server error'
    ]);
}