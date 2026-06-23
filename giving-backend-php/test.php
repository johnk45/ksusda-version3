<?php
// test_env.php - Check if .env is loading

echo "<h1>Environment Test</h1>";

// Check if .env exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✅ .env file exists at: " . $envFile . "<br>";
    echo "File size: " . filesize($envFile) . " bytes<br>";
} else {
    echo "❌ .env file NOT found at: " . $envFile . "<br>";
}

// Read and display .env content (masked)
echo "<h2>.env Content:</h2>";
echo "<pre>";
$content = file_get_contents($envFile);
$lines = explode("\n", $content);
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, '#') === 0) {
        echo $line . "\n";
        continue;
    }
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Mask sensitive values
        if (strpos($key, 'SECRET') !== false || strpos($key, 'PASSKEY') !== false) {
            echo $key . "=***MASKED***\n";
        } else {
            echo $line . "\n";
        }
    }
}
echo "</pre>";

// Test M-Pesa config loading
require_once __DIR__ . '/config/mpesa.php';

echo "<h2>M-Pesa Config:</h2>";
echo "<pre>";
$config = MpesaConfig::get();
foreach ($config as $key => $value) {
    if (strpos($key, 'secret') !== false || strpos($key, 'passkey') !== false) {
        echo $key . ": ***MASKED***\n";
    } else {
        echo $key . ": " . ($value ?: '❌ EMPTY') . "\n";
    }
}
echo "</pre>";

// Test authentication
echo "<h2>Authentication Test:</h2>";
try {
    require_once __DIR__ . '/includes/auth.php';
    $token = MpesaAuth::getAccessToken();
    echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
    echo "✅ <strong>Authentication Successful!</strong><br>";
    echo "Token: " . substr($token, 0, 30) . "...";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;color:#721c24;'>";
    echo "❌ <strong>Authentication Failed:</strong><br>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}