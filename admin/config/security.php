<?php
// config/security.php - Central security configuration

class Security {
    
    // Prevent SQL Injection
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Generate CSRF Token
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF Token
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token validation failed');
        }
        return true;
    }
    
    // Generate CSRF Token HTML
    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCSRFToken() . '">';
    }
    
    // Rate Limiting
    public static function checkRateLimit($key, $limit = 100, $timeWindow = 3600) {
        $rateLimitFile = sys_get_temp_dir() . '/rate_limit_' . md5($key);
        
        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true);
            if ($data['time'] > time() - $timeWindow) {
                if ($data['count'] >= $limit) {
                    return false;
                }
                $data['count']++;
            } else {
                $data = ['count' => 1, 'time' => time()];
            }
        } else {
            $data = ['count' => 1, 'time' => time()];
        }
        
        file_put_contents($rateLimitFile, json_encode($data));
        return true;
    }
    
    // Validate File Upload
    public static function validateFile($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code: ' . $file['error'];
        }
        
        if ($file['size'] > $maxSize) {
            $errors[] = 'File too large. Maximum size: ' . ($maxSize / 1048576) . 'MB';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
        }
        
        // Scan for malware (basic)
        $content = file_get_contents($file['tmp_name']);
        $suspiciousPatterns = ['<?php', 'eval(', 'base64_decode', 'system(', 'exec('];
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                $errors[] = 'Suspicious content detected in file';
                break;
            }
        }
        
        return $errors;
    }
    
    // Generate Secure Random String
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Log Security Events
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'event' => $event,
            'details' => $details
        ];
        
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    // Check if user has permission
    public static function hasPermission($requiredRole) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            return false;
        }
        
        $roleHierarchy = [
            'Admin' => 4,
            'Pastor' => 3,
            'Secretary' => 2,
            'Department Head' => 1,
            'Viewer' => 0
        ];
        
        $userRoleLevel = $roleHierarchy[$_SESSION['role']] ?? 0;
        $requiredRoleLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userRoleLevel >= $requiredRoleLevel;
    }
    
    // Validate Email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate Phone (Kenyan format)
    public static function validatePhone($phone) {
        return preg_match('/^(07|01|\\+254)[0-9]{8,9}$/', $phone);
    }
    
    // Encrypt sensitive data
    public static function encrypt($data, $key = null) {
        $key = $key ?? $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-me';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    // Decrypt sensitive data
    public static function decrypt($data, $key = null) {
        $key = $key ?? $_ENV['ENCRYPTION_KEY'] ?? 'default-key-change-me';
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    // Prevent XSS
    public static function sanitizeOutput($data) {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    // Check for SQL Injection patterns
    public static function detectSQLInjection($input) {
        $patterns = [
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bOR\b.*\b=.*\b=)/i',
            '/;.*--/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('SQL Injection Attempt', ['input' => $input, 'pattern' => $pattern]);
                return true;
            }
        }
        return false;
    }
}

// Initialize security session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>