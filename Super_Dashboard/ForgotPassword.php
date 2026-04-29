<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) { header("Location: Userdashboard.php"); exit; }

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $conn  = db_connect();
        $token = create_reset_token($conn, $email);
        // Always show success to prevent email enumeration
        $success = true;

        if ($token) {
            // In production, send an email here. For now, log it.
            $resetLink = "http://localhost/php_project/Super_Dashboard/ResetPassword.php?token=" . urlencode($token);
            error_log("[Password Reset] Link for $email: $resetLink");
            // Uncomment below and configure mail() or PHPMailer for real email sending:
            // mail($email, "Reset your RCA Portal password",
            //     "Click the link to reset your password (expires in 1 hour):\n\n$resetLink\n\nIf you didn't request this, ignore this email.",
            //     "From: noreply@rca.ac.rw");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="UserLogin.css">
    <style>
        .success-box { background:#e8f5e9; border-left:3px solid #2a9d2a; padding:.85rem 1.1rem; border-radius:6px; font-size:.875rem; color:#1a6e1a; margin-bottom:1.5rem; }
        .back-link { display:inline-flex; align-items:center; gap:.4rem; font-size:.875rem; color:var(--mid); text-decoration:none; margin-bottom:1.5rem; }
        .back-link:hover { color:var(--ink); }
        .icon-lock { font-size:2.5rem; margin-bottom:1rem; display:block; }
        .dev-note { background:#fff8e1; border:1px solid #ffe082; border-radius:8px; padding:.85rem 1rem; font-size:.8rem; color:#7a5c00; margin-top:1rem; }
    </style>
</head>
<body>
<div class="panel-left" aria-hidden="true">
    <div class="brand">RCA Portal.</div>
    <div class="panel-tagline">
        <h1>Reset your <em>password</em>.</h1>
        <p>Enter the email address linked to your account and we'll send you a secure reset link.</p>
    </div>
    <div class="panel-bottom">
        <div class="stat"><div class="num">Secure</div><div class="label">Reset</div></div>
        <div class="stat"><div class="num">1hr</div><div class="label">Link Valid</div></div>
        <div class="stat"><div class="num">Safe</div><div class="label">Always</div></div>
    </div>
</div>

<div class="panel-right">
    <div class="form-card">
        <a href="UserLogin-form.php" class="back-link">← Back to Sign In</a>

        <?php if ($success): ?>
            <span class="icon-lock">📬</span>
            <h2>Check your inbox</h2>
            <p class="sub">If an account exists for that email, a reset link has been sent. Check your inbox (and spam folder).</p>
            <div class="success-box">Reset link sent! It expires in <strong>1 hour</strong>.</div>
            <div class="dev-note">
                <strong>Development note:</strong> Email sending is not configured. Check your PHP error log for the reset link:<br>
                <code style="font-size:.75rem">C:\xampp\php\logs\php_error_log</code>
            </div>
            <div style="margin-top:1.5rem; text-align:center; font-size:.9rem; color:var(--mid);">
                <a href="ForgotPassword.php" style="color:var(--rust); font-weight:500;">Send another link</a>
            </div>
        <?php else: ?>
            <span class="icon-lock">🔑</span>
            <h2>Forgot Password</h2>
            <p class="sub">Enter your email and we'll send you a reset link</p>

            <?php if ($error): ?>
            <div class="error-box show"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="ForgotPassword.php" id="forgotForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="field">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email"
                           placeholder="you@rca.ac.rw" required autocomplete="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-login" id="submitBtn">
                    <span>Send Reset Link →</span>
                </button>
            </form>

            <div class="divider">or</div>
            <div class="link-signup">Remember your password? <a href="UserLogin-form.php">Sign in</a></div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('forgotForm')?.addEventListener('submit', e => {
    const email = document.getElementById('email').value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        e.preventDefault();
        return;
    }
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').querySelector('span').textContent = 'Sending…';
});
</script>
</body>
</html>
