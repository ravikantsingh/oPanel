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
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Login | Architect Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">

<div class="card shadow-lg border-0" style="width: 100%; max-width: 400px; border-radius: 12px;">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold"><i class="bi bi-hdd-network text-primary"></i> Server Admin</h3>
            <p class="text-muted small">Secure Hosting Architecture</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 text-sm text-center"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Username</label>
                    <input type="text" name="username" class="form-control form-control-lg bg-light" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Password</label>
                    <input type="password" name="password" class="form-control form-control-lg bg-light" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Authenticate <i class="bi bi-arrow-right"></i></button>
            </form>

        <?php elseif ($step === 2): ?>
            <div class="text-center">
                <div class="alert alert-warning small py-2"><i class="bi bi-shield-lock"></i> 2FA Setup Required on first login.</div>
                <p class="small text-muted mb-3">Scan this QR code with Google Authenticator or Authy.</p>
                <img src="<?= TOTP::getQRCodeUrl('AdminPanel', $_SESSION['temp_2fa_secret']) ?>" class="img-fluid rounded border mb-3" alt="QR Code">
                
                <form method="POST">
                    <input type="hidden" name="action" value="verify_2fa">
                    <input type="text" name="totp_code" class="form-control form-control-lg text-center font-monospace mb-3" placeholder="000000" maxlength="6" autocomplete="off" required autofocus>
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold"><i class="bi bi-check-circle"></i> Verify & Enable 2FA</button>
                </form>
            </div>

        <?php elseif ($step === 3): ?>
            <div class="text-center">
                <i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i>
                <h5 class="fw-bold mt-2">Two-Factor Authentication</h5>
                <p class="small text-muted mb-4">Enter the 6-digit code from your authenticator app.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="verify_2fa">
                    <input type="text" name="totp_code" class="form-control form-control-lg text-center font-monospace mb-3" placeholder="• • • • • •" maxlength="6" autocomplete="off" required autofocus>
                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Verify <i class="bi bi-unlock"></i></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>