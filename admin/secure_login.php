<?php
// secure_login.php - Enhanced login with security features
require_once 'config/database.php';
require_once 'config/security.php';

session_start();

// Check for brute force attacks
$ip = $_SERVER['REMOTE_ADDR'];
$attemptsFile = sys_get_temp_dir() . '/login_attempts_' . md5($ip);

if (file_exists($attemptsFile)) {
    $attempts = json_decode(file_get_contents($attemptsFile), true);
    if ($attempts['count'] >= 5 && $attempts['last_attempt'] > time() - 900) {
        Security::logSecurityEvent('Brute Force Detected', ['ip' => $ip]);
        die("Too many login attempts. Please try again after 15 minutes.");
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        Security::logSecurityEvent('CSRF Attack Attempt', ['ip' => $ip]);
        die("Security validation failed.");
    }
    
    $username = Security::sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    // Check for SQL injection patterns
    if (Security::detectSQLInjection($username)) {
        Security::logSecurityEvent('SQL Injection Attempt in Login', ['username' => $username]);
        $error = "Invalid login credentials.";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Use prepared statement
        $query = "SELECT user_id, username, password, role, status, member_id 
                  FROM users 
                  WHERE username = :username AND status = 'Active'";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Update last login
            $update = "UPDATE users SET last_login = NOW(), last_ip = :ip WHERE user_id = :user_id";
            $stmt = $db->prepare($update);
            $stmt->execute([
                ':ip' => $_SERVER['REMOTE_ADDR'],
                ':user_id' => $user['user_id']
            ]);
            
            // Clear login attempts
            unlink($attemptsFile);
            
            Security::logSecurityEvent('Successful Login', ['username' => $username]);
            
            header("Location: dashboard.php");
            exit();
        } else {
            // Failed login
            if (file_exists($attemptsFile)) {
                $attempts = json_decode(file_get_contents($attemptsFile), true);
                $attempts['count']++;
                $attempts['last_attempt'] = time();
            } else {
                $attempts = ['count' => 1, 'last_attempt' => time()];
            }
            file_put_contents($attemptsFile, json_encode($attempts));
            
            Security::logSecurityEvent('Failed Login Attempt', ['username' => $username]);
            $error = "Invalid username or password!";
        }
    }
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h3>Kisii University SDA Church</h3>
            <p>Church Management System</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo Security::sanitizeOutput($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autocomplete="off">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">Secure System - All activities are logged</small>
        </div>
    </div>
</body>
</html>