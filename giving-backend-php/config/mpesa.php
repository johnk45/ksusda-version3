<?php
// config/mpesa.php

class MpesaConfig {
    private static $config = null;
    
    public static function get() {
        if (self::$config === null) {
            // Try to load .env file
            $env = [];
            $envPath = __DIR__ . '/../.env';
            
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Skip comments
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    // Parse key=value
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $key = trim($parts[0]);
                        $value = trim($parts[1]);
                        $env[$key] = $value;
                    }
                }
            }
            
            // Get values from environment or .env file
            $consumerKey = getenv('MPESA_CONSUMER_KEY') ?: ($env['MPESA_CONSUMER_KEY'] ?? '');
            $consumerSecret = getenv('MPESA_CONSUMER_SECRET') ?: ($env['MPESA_CONSUMER_SECRET'] ?? '');
            
            self::$config = [
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
                'passkey' => getenv('MPESA_PASSKEY') ?: ($env['MPESA_PASSKEY'] ?? 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
                'shortcode' => getenv('MPESA_SHORTCODE') ?: ($env['MPESA_SHORTCODE'] ?? '174379'),
                'environment' => getenv('MPESA_ENVIRONMENT') ?: ($env['MPESA_ENVIRONMENT'] ?? 'sandbox'),
                'callback_url' => getenv('MPESA_CALLBACK_URL') ?: ($env['MPESA_CALLBACK_URL'] ?? ''),
                'result_url' => getenv('MPESA_RESULT_URL') ?: ($env['MPESA_RESULT_URL'] ?? ''),
            ];
        }
        return self::$config;
    }
    
    public static function getApiUrl() {
        $config = self::get();
        return $config['environment'] === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }
}