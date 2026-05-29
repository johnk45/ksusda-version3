<?php
// includes/SessionManager.php - Secure session management

class SessionManager {
    
    public static function startSecureSession() {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Validate session
        self::validateSession();
    }
    
    public static function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
            self::destroySession();
            header("Location: login.php?timeout=1");
            exit();
        }
        
        // Check IP address consistency
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroySession();
            Security::logSecurityEvent('IP Mismatch - Possible Session Hijacking');
            header("Location: login.php");
            exit();
        }
        
        // Check user agent consistency
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            self::destroySession();
            Security::logSecurityEvent('User Agent Mismatch - Possible Session Hijacking');
            header("Location: login.php");
            exit();
        }
        
        // Update last activity
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    public static function destroySession() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['session_id'] = session_id();
        $_SESSION['session_regenerated'] = time();
    }
}
?>