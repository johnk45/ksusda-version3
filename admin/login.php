<?php
require_once 'config/database.php';
require_once 'includes/sessionManager.php';

if(session_status() == PHP_SESSION_NONE){
    session_start();
}

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = :username AND status = 'Active'";
    $stmt = $db->prepare($query);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $update = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
        $stmt = $db->prepare($update);
        $stmt->execute([':user_id' => $user['user_id']]);
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kisii University SDA Church</title>
    <style>
        /* =========================================================
           ADVENTIST GIVING — Donation Page Styles
           Matches AdventistGiving.org / donate / ANPINV layout
           ========================================================= */

        /* ---------- Reset & Base ---------- */
        body.giving-page-body {
          margin: 0;
          padding: 0;
          font-family: 'Lato', 'Noto Sans', Arial, sans-serif;
          background: #f4f5f7;
          color: #333;
          min-height: 100vh;
          display: flex;
          flex-direction: column;
        }
        *, *::before, *::after { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        /* ---------- HEADER ---------- */
        .ag-header {
          background: #1a2538;
          color: #fff;
          position: sticky;
          top: 0;
          z-index: 1000;
          box-shadow: 0 2px 8px rgba(0,0,0,.25);
        }
        .ag-header-inner {
          max-width: 1200px;
          margin: 0 auto;
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 0 24px;
          height: 60px;
        }
        .ag-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
        .ag-logo-icon {
          width: 34px;
          height: 34px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border: 1px solid rgba(255,255,255,.3);
          border-radius: 10px;
          font-size: 0.8rem;
          font-weight: 700;
        }
        .ag-logo-text { display: flex; flex-direction: column; line-height: 1.15; }
        .ag-logo-title { font-size: 0.95rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
        .ag-logo-sub { font-size: 0.6rem; letter-spacing: 1.5px; opacity: 0.55; text-transform: uppercase; }
        .ag-header-nav { display: flex; gap: 20px; align-items: center; }
        .ag-nav-link {
          color: #fff;
          text-decoration: none;
          font-size: 0.78rem;
          font-weight: 600;
          letter-spacing: 1px;
          display: flex; align-items: center; gap: 6px;
          padding: 6px 12px;
          border-radius: 4px;
          transition: background .2s;
        }
        .ag-nav-link:hover, .ag-nav-link.active { background: rgba(255,255,255,.12); }

        /* ---------- CHURCH NAME BANNER ---------- */
        .ag-church-banner {
          background: #2c3e50;
          color: #fff;
          text-align: center;
          padding: 28px 20px 22px;
        }
        .ag-church-name {
          margin: 0 0 4px;
          font-family: 'Cormorant Garamond', Georgia, serif;
          font-size: 2rem;
          font-weight: 600;
          letter-spacing: 0.5px;
        }
        .ag-church-address {
          margin: 0;
          font-size: 0.75rem;
          letter-spacing: 2.5px;
          text-transform: uppercase;
          opacity: 0.6;
        }

        /* ---------- LOGIN CARD ---------- */
        .ag-main {
          max-width: 520px;
          margin: 32px auto 40px;
          padding: 0 24px;
          width: 100%;
        }
        .ag-mpesa-section {
          background: #fff;
          border-radius: 12px;
          box-shadow: 0 12px 36px rgba(0,0,0,.08);
          overflow: hidden;
          border: 1px solid #e5e7eb;
        }
        .ag-mpesa-header {
          background: #006B75;
          color: #fff;
          padding: 20px 24px;
          display: flex;
          align-items: center;
          gap: 12px;
          font-weight: 700;
          font-size: 1rem;
          letter-spacing: 0.5px;
        }
        .ag-mpesa-body {
          padding: 32px 28px 28px;
        }
        .ag-mpesa-body label {
          display: block;
          font-weight: 600;
          font-size: 0.85rem;
          margin-bottom: 8px;
          color: #333;
        }
        .ag-form-group {
          margin-bottom: 18px;
        }
        .form-control {
          width: 100%;
          border: 2px solid #ddd;
          border-radius: 10px;
          padding: 14px 16px;
          font-size: 1rem;
          font-family: inherit;
          color: #222;
          background: #fafafa;
          transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus {
          outline: none;
          border-color: #006B75;
          box-shadow: 0 0 0 4px rgba(0,107,117,.12);
          background: #fff;
        }
        .alert {
          margin: 0 0 18px;
          padding: 16px 18px;
          border-radius: 10px;
          background: #fee2e2;
          color: #991b1b;
          border: 1px solid #fecaca;
          font-size: 0.95rem;
        }
        .ag-continue-btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 100%;
          padding: 16px 24px;
          background: #006B75;
          color: #fff;
          border: none;
          border-radius: 10px;
          font-size: 0.95rem;
          font-weight: 700;
          letter-spacing: 1px;
          cursor: pointer;
          transition: background .2s, transform .1s;
        }
        .ag-continue-btn:hover { background: #005a63; }
        .ag-continue-btn:active { transform: scale(0.98); }
        .ag-help-text {
          margin-top: 18px;
          font-size: 0.86rem;
          color: #556066;
          text-align: center;
        }
        .ag-footer-note {
          margin-top: 22px;
          text-align: center;
          font-size: 0.82rem;
          color: #64748b;
        }

        @media (max-width: 768px) {
          .ag-header-inner { padding: 0 16px; }
          .ag-church-banner { padding: 22px 16px; }
          .ag-church-name { font-size: 1.6rem; }
          .ag-main { margin: 20px auto 32px; padding: 0 16px; }
          .ag-mpesa-body { padding: 28px 22px 22px; }
        }
    </style>
</head>
<body class="giving-page-body">
    <header class="ag-header">
        <div class="ag-header-inner">
            <a href="../index.html" class="ag-logo">
                <span class="ag-logo-icon">SDA</span>
                <div class="ag-logo-text">
                    <span class="ag-logo-title">KUS SDA Church</span>
                    <span class="ag-logo-sub">Admin Login</span>
                </div>
            </a>
            <nav class="ag-header-nav">
                <a href="../index.html" class="ag-nav-link">Back to Site</a>
            </nav>
        </div>
    </header>

    <section class="ag-church-banner">
        <h1 class="ag-church-name">Kisii University SDA</h1>
        <p class="ag-church-address">Church Management System Login</p>
    </section>

    <main class="ag-main">
        <section class="ag-mpesa-section">
            <div class="ag-mpesa-header">Secure Admin Access</div>
            <div class="ag-mpesa-body">
                <?php if($error): ?>
                    <div class="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="ag-form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                    </div>
                       <div class="ag-form-group">
                        <label for="email">Email</label>
                        <input type="text" class="form-control" id="email" name="email" required autocomplete="email">
                    </div>
                    
                    
                    <div class="ag-form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="ag-continue-btn">Login</button>
                </form>

                <p class="ag-help-text">Need help? Contact the site administrator or use your official church credentials.</p>
                <p class="ag-footer-note">Please keep your login details confidential.</p>
            </div>
        </section>
    </main>
</body>
</html>