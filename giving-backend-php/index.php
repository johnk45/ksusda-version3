<?php
// index.php - Main entry point / API documentation

header('Content-Type: application/json');

// Load required files
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Documentation
$endpoints = [
    [
        'method' => 'POST',
        'path' => '/api/stkpush.php',
        'description' => 'Initiate M-Pesa STK Push',
        'parameters' => [
            'phoneNumber' => 'string (required) - M-Pesa phone number',
            'amount' => 'number (required) - Amount to pay',
            'accountReference' => 'string (optional) - Account reference',
            'transactionDesc' => 'string (optional) - Transaction description',
            'offerings' => 'array (optional) - List of offerings with categories and amounts'
        ],
        'example' => [
            'phoneNumber' => '254712345678',
            'amount' => 1000,
            'accountReference' => 'Church Giving',
            'transactionDesc' => 'Tithe & Offerings'
        ]
    ],
    [
        'method' => 'GET',
        'path' => '/api/status.php?id={checkoutRequestId}',
        'description' => 'Check transaction status',
        'parameters' => [
            'id' => 'string (required) - CheckoutRequestID from M-Pesa'
        ],
        'example' => '/api/status.php?id=ws_CO_123456789'
    ],
    [
        'method' => 'POST',
        'path' => '/api/callback.php',
        'description' => 'M-Pesa callback URL (called by Safaricom)',
        'note' => 'Do not call this endpoint directly'
    ],
    [
        'method' => 'POST',
        'path' => '/api/result.php',
        'description' => 'M-Pesa result URL (called by Safaricom for timeouts)',
        'note' => 'Do not call this endpoint directly'
    ],
    [
        'method' => 'GET',
        'path' => '/api/test.php',
        'description' => 'Test M-Pesa authentication',
        'note' => 'Use to verify credentials are working'
    ]
];

$response = [
    'name' => 'Kisii University SDA Church Giving API',
    'version' => '1.0.0',
    'description' => 'API for processing M-Pesa donations',
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => $endpoints,
    'documentation' => 'https://github.com/kisii-university-sda/giving-api',
];

sendJsonResponse($response);