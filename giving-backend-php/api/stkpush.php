<?php
// api/stkpush.php - Enhanced phone validation

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}



require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Donation.php';
require_once __DIR__ . '/../config/mpesa.php';
require_once __DIR__ . '/../includes/functions.php';

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['phoneNumber']) || empty($input['phoneNumber'])) {
    sendError('Phone number is required', 400);
    exit;
}

if (!isset($input['amount']) || $input['amount'] <= 0) {
    sendError('Valid amount is required', 400);
    exit;
}

try {
    // ============================================
    // ENHANCED PHONE NUMBER FORMATTING
    // ============================================
    $phone = preg_replace('/\s/', '', $input['phoneNumber']);
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Debug: Log original phone
    error_log("📱 Original phone: " . $phone);
    
    // Remove leading +
    if (substr($phone, 0, 1) === '+') {
        $phone = substr($phone, 1);
    }
    
    // Remove leading 0
    if (substr($phone, 0, 1) === '0') {
        $phone = '254' . substr($phone, 1);
    }
    
    // If no country code, add 254
    if (substr($phone, 0, 3) !== '254') {
        // Check if it's a valid Kenyan number (starts with 7 or 1)
        if (preg_match('/^[71][0-9]{8}$/', $phone)) {
            $phone = '254' . $phone;
        } else {
            sendError('Invalid phone number format. Use 254XXXXXXXXX', 400);
            exit;
        }
    }
    
    // Final validation - must be 12 digits starting with 254
    if (!preg_match('/^254[0-9]{9}$/', $phone)) {
        error_log("❌ Invalid phone after formatting: " . $phone);
        sendError('Invalid phone number. Must be 12 digits (254XXXXXXXXX)', 400);
        exit;
    }
    
    error_log("✅ Formatted phone: " . $phone);
    
    // ============================================
    // CHECK IF PHONE IS M-PESA REGISTERED
    // ============================================
    // In sandbox, only specific numbers work
    // Test with: 254708374149 (Safaricom test number)
    $testNumbers = ['254708374149', '254700000000', '254711111111'];
    
    // If in sandbox, suggest using test numbers
    if (MpesaConfig::get()['environment'] === 'sandbox') {
        if (!in_array($phone, $testNumbers)) {
            error_log("⚠️ Using non-test number in sandbox: " . $phone);
            error_log("💡 Sandbox test numbers: " . implode(', ', $testNumbers));
        }
    }
    
    // ============================================
    // GENERATE TRANSACTION ID
    // ============================================
    $transactionId = 'GIV' . date('YmdHis') . rand(100, 999);
    $accountRef = $input['accountReference'] ?? 'Church Giving';
    $transactionDesc = $input['transactionDesc'] ?? 'Online Giving';
    
    // ============================================
    // M-PESA STK PUSH
    // ============================================
    $config = MpesaConfig::get();
    $apiUrl = MpesaConfig::getApiUrl();
    
    // Generate timestamp and password
    $timestamp = date('YmdHis');
    $password = base64_encode(
        $config['shortcode'] . $config['passkey'] . $timestamp
    );
    
    // Get access token
    $token = MpesaAuth::getAccessToken();
    
    // Prepare STK Push payload
    $amount = (int) round($input['amount']);
    
    // Minimum amount for sandbox is 1 KES
    if ($amount < 1) {
        sendError('Minimum amount is 1 KES', 400);
        exit;
    }
    
    $payload = [
        'BusinessShortCode' => $config['shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $config['shortcode'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $config['callback_url'],
        'AccountReference' => $accountRef,
        'TransactionDesc' => substr($transactionDesc, 0, 36), // Max 36 chars
    ];
    
    // Log the full payload (without password)
    $logPayload = $payload;
    $logPayload['Password'] = '***HIDDEN***';
    error_log("📤 STK Push Payload: " . json_encode($logPayload));
    
    // Send STK Push
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl . '/mpesa/stkpush/v1/processrequest',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HEADER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log response
    error_log("📥 STK Push Response:");
    error_log("  HTTP Code: " . $httpCode);
    error_log("  Body: " . $body);
    
    if ($curlError) {
        error_log("❌ CURL Error: " . $curlError);
        sendError('CURL Error: ' . $curlError, 500);
        exit;
    }
    
    if ($httpCode !== 200) {
        error_log("❌ HTTP Error: " . $httpCode);
        sendError('HTTP Error: ' . $httpCode . ' - ' . $body, $httpCode);
        exit;
    }
    
    $result = json_decode($body, true);
    
    if (!$result) {
        sendError('Invalid response from M-Pesa', 500);
        exit;
    }
    
    // Check for errors
    if (isset($result['errorCode'])) {
        sendError($result['errorMessage'] ?? $result['errorCode'], 400);
        exit;
    }
    
    if (!isset($result['CheckoutRequestID'])) {
        error_log("❌ No CheckoutRequestID in response: " . json_encode($result));
        sendError('Invalid M-Pesa response structure', 500);
        exit;
    }
    
    // ============================================
    // SAVE TO DATABASE
    // ============================================
    $donation = new Donation();
    $donationId = $donation->create([
        'transaction_id' => $transactionId,
        'merchant_request_id' => $result['MerchantRequestID'] ?? null,
        'checkout_request_id' => $result['CheckoutRequestID'],
        'phone_number' => $phone,
        'amount' => $amount,
        'payment_method' => 'mpesa',
        'offerings' => $input['offerings'] ?? null,
        'account_reference' => $accountRef,
        'transaction_desc' => $transactionDesc,
        'status' => 'pending',
        'response_code' => $result['ResponseCode'] ?? null,
        'response_description' => $result['ResponseDescription'] ?? null,
    ]);
    
    // ============================================
    // RETURN SUCCESS
    // ============================================
    $response = [
        'success' => true,
        'data' => [
            'checkoutRequestId' => $result['CheckoutRequestID'],
            'merchantRequestId' => $result['MerchantRequestID'] ?? null,
            'responseCode' => $result['ResponseCode'] ?? null,
            'responseDescription' => $result['ResponseDescription'] ?? null,
            'donationId' => $donationId,
            'phoneNumber' => $phone,
            'amount' => $amount,
        ],
        'message' => 'STK Push sent successfully. Please check your phone and enter PIN.',
        'debug' => [
            'environment' => $config['environment'],
            'phone_formatted' => $phone,
            'amount' => $amount,
            'shortcode' => $config['shortcode'],
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('❌ STK Push error: ' . $e->getMessage());
    sendError($e->getMessage(), 500);
}