<?php
// /opt/panel/www/login.php
// ---> NEW: FORCE SECURE COOKIES <---
session_name('PANEL_SESSION');
session_set_cookie_params([
    'secure' => true,      // Only transmit over HTTPS
    'httponly' => true,    // Block Javascript from reading the cookie
    'samesite' => 'Strict' // Prevent Cross-Site Request Forgery
]);
// -----------------------------------
session_start();
require_once 'classes/Database.php';
require_once 'classes/TOTP.php';

// If already logged in, send to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /index.php');
    exit;
}

$error = '';
$step = 1; // 1: Login, 2: 2FA Setup, 3: 2FA Verify
$db = Database::getInstance()->getConnection();

// Phase 1: Password Authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM panel_admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['pre_auth_admin_id'] = $admin['id'];
        
        if ($admin['is_2fa_enabled'] == 1) {
            $step = 3; // Go straight to verification
        } else {
            // Generate a fresh secret for setup
            $_SESSION['temp_2fa_secret'] = TOTP::generateSecret();
            $step = 2; // Go to setup
        }
    } else {
        $error = "Invalid username or password.";
        //Log failed password attempt for fail2ban
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] oPanel Auth Failed: Invalid credentials for user '{$username}'. IP: {$ip}\n";
        file_put_contents('/opt/panel/logs/auth.log', $logMessage, FILE_APPEND);
    }
}

// Phase 2 & 3: 2FA Verification (Handles both Setup and Login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_2fa') {
    if (!isset($_SESSION['pre_auth_admin_id'])) {
        header("Location: login.php"); exit;
    }

    $code = trim($_POST['totp_code']);
    $stmt = $db->prepare("SELECT * FROM panel_admins WHERE id = ?");
    $stmt->execute([$_SESSION['pre_auth_admin_id']]);
    $admin = $stmt->fetch();

    // Determine which secret to check against
    $secretToCheck = ($admin['is_2fa_enabled'] == 1) ? $admin['totp_secret'] : $_SESSION['temp_2fa_secret'];

    if (TOTP::verifyCode($secretToCheck, $code)) {
        // Code is perfect. If they were in setup mode, permanently save the secret.
        if ($admin['is_2fa_enabled'] == 0) {
            $update = $db->prepare("UPDATE panel_admins SET totp_secret = ?, is_2fa_enabled = 1 WHERE id = ?");
            $update->execute([$secretToCheck, $admin['id']]);
        }
        
        // Log them in fully
        $_SESSION['admin_logged_in'] = true;
        unset($_SESSION['pre_auth_admin_id']);
        unset($_SESSION['temp_2fa_secret']);
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Authenticator code. Try again.";
        $step = ($admin['is_2fa_enabled'] == 1) ? 3 : 2;
        //Log failed 2FA attempt for fail2ban
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] oPanel Auth Failed: Invalid 2FA code. IP: {$ip}\n";
        file_put_contents('/opt/panel/logs/auth.log', $logMessage, FILE_APPEND);
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>oPanel | Systems Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e1e2f 0%, #0d0d14 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }
        .form-control-dark {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 8px;
        }
        .form-control-dark:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: #0d6efd;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .text-gradient {
            background: linear-gradient(to right, #4facfe 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

<div class="login-glass text-light">
    <div class="text-center mb-4">
        <i class="bi bi-hexagon-fill fs-1 text-gradient"></i>
        <h3 class="fw-bold mt-2 mb-0">oPanel</h3>
        <p class="text-secondary small tracking-wide text-uppercase mt-1">Unified Server Management</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-25 text-danger py-2 text-sm text-center rounded-3"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label class="form-label small text-secondary fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary text-secondary"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control form-control-dark form-control-lg" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small text-secondary fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary text-secondary"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control form-control-dark form-control-lg" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 shadow-sm">Secure Access <i class="bi bi-shield-lock ms-1"></i></button>
        </form>

    <?php elseif ($step === 2): ?>
        <div class="text-center">
            <div class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill"><i class="bi bi-shield-lock"></i> 2FA Setup Required</div>
            <p class="small text-secondary mb-3">Scan this QR code with Google Authenticator or Authy to secure your admin account.</p>
            <div class="bg-white p-2 rounded-3 d-inline-block mb-3">
                <img src="<?= TOTP::getQRCodeUrl('oPanel_Admin', $_SESSION['temp_2fa_secret']) ?>" class="img-fluid" alt="QR Code" style="width: 150px;">
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="verify_2fa">
                <input type="text" name="totp_code" class="form-control form-control-dark form-control-lg text-center font-monospace mb-3" placeholder="000000" maxlength="6" autocomplete="off" required autofocus>
                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-3"><i class="bi bi-check-circle"></i> Activate 2FA</button>
            </form>
        </div>

    <?php elseif ($step === 3): ?>
        <div class="text-center">
            <i class="bi bi-shield-lock text-primary mb-2" style="font-size: 3rem;"></i>
            <h5 class="fw-bold mt-2">Identity Verification</h5>
            <p class="small text-secondary mb-4">Please enter the 6-digit code from your authenticator device.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="verify_2fa">
                <input type="text" name="totp_code" class="form-control form-control-dark form-control-lg text-center font-monospace mb-3 letter-spacing-lg" placeholder="• • • • • •" maxlength="6" autocomplete="off" required autofocus style="letter-spacing: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3">Verify <i class="bi bi-unlock ms-1"></i></button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>