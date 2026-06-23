<?php
// includes/auth.php - With enhanced debugging

require_once __DIR__ . '/../config/mpesa.php';

class MpesaAuth {
    private static $accessToken = null;
    private static $tokenExpiry = null;
    
    /**
     * Get OAuth access token from Safaricom with detailed debugging
     */
    public static function getAccessToken() {
        // Check if cached token is still valid
        if (self::$accessToken && self::$tokenExpiry && time() < self::$tokenExpiry) {
            return self::$accessToken;
        }
        
        $config = MpesaConfig::get();
        $apiUrl = MpesaConfig::getApiUrl();
        
        // Log credentials (masked for security)
        error_log("🔑 M-Pesa Auth Attempt:");
        error_log("  - API URL: " . $apiUrl);
        error_log("  - Consumer Key: " . substr($config['consumer_key'], 0, 10) . '...');
        error_log("  - Environment: " . $config['environment']);
        
        // Check if credentials are empty
        if (empty($config['consumer_key']) || empty($config['consumer_secret'])) {
            error_log("❌ ERROR: Consumer Key or Secret is empty!");
            throw new Exception('M-Pesa credentials are not configured. Please check your .env file.');
        }
        
        $credentials = base64_encode(
            $config['consumer_key'] . ':' . $config['consumer_secret']
        );
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl . '/oauth/v1/generate?grant_type=client_credentials',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Set to true in production
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true, // Include headers in response
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log detailed response
        error_log("📡 M-Pesa Auth Response:");
        error_log("  - HTTP Status: " . $httpCode);
        error_log("  - Headers: " . $headers);
        error_log("  - Body: " . $body);
        
        if ($curlError) {
            error_log("❌ CURL Error: " . $curlError);
            throw new Exception('CURL Error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($body, true);
            $errorMessage = $errorData['errorMessage'] ?? $errorData['error'] ?? $body;
            error_log("❌ M-Pesa Auth Failed: " . $errorMessage);
            throw new Exception('M-Pesa authentication failed: ' . $errorMessage);
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['access_token'])) {
            error_log("❌ No access token in response: " . json_encode($data));
            throw new Exception('No access token in M-Pesa response');
        }
        
        // Cache token (expires in 3600 seconds, cache for 3500)
        self::$accessToken = $data['access_token'];
        self::$tokenExpiry = time() + 3500;
        
        error_log("✅ M-Pesa Authentication successful!");
        return self::$accessToken;
    }
}