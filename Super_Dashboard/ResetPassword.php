<?php
session_start();
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) { header("Location: Userdashboard.php"); exit; }

$conn    = db_connect();
$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$success = false;
$errors  = [];

// Validate token upfront
$tokenEmail = $token ? verify_reset_token($conn, $token) : null;
$invalid    = !$tokenEmail;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$invalid) {
    csrf_verify();

    $newPwd  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($newPwd) < 8)                                          $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $newPwd) || !preg_match('/[0-9]/', $newPwd)) $errors[] = "Password must contain at least one uppercase letter and one number.";
    if ($newPwd !== $confirm)                                         $errors[] = "Passwords do not match.";

    // Re-verify token hasn't expired between page load and submit
    $tokenEmail = verify_reset_token($conn, $token);
    if (!$tokenEmail) $errors[] = "Reset link has expired or already been used. Please request a new one.";

    if (empty($errors)) {
        $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd  = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
        $upd->bind_param("ss", $hash, $tokenEmail);
        $upd->execute();
        $upd->close();

        consume_reset_token($conn, $token);
        log_activity($conn, null, 'Password reset via email token', "Email: $tokenEmail");
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="UserLogin.css">
    <style>
        .success-box { background:#e8f5e9; border-left:3px solid #2a9d2a; padding:.85rem 1.1rem; border-radius:6px; font-size:.875rem; color:#1a6e1a; margin-bottom:1.5rem; }
        .icon-key { font-size:2.5rem; margin-bottom:1rem; display:block; }
        .strength-bar { height:4px; background:var(--warm); border-radius:2px; margin-top:.5rem; overflow:hidden; }
        .strength-fill { height:100%; width:0; border-radius:2px; transition:width .3s,background .3s; }
        .hint { font-size:.72rem; color:var(--mid); margin-top:.3rem; display:block; }
    </style>
</head>
<body>
<div class="panel-left" aria-hidden="true">
    <div class="brand">RCA Portal.</div>
    <div class="panel-tagline">
        <h1>Create a new <em>password</em>.</h1>
        <p>Choose a strong password you haven't used before to secure your account.</p>
    </div>
    <div class="panel-bottom">
        <div class="stat"><div class="num">Bcrypt</div><div class="label">Hashed</div></div>
        <div class="stat"><div class="num">Safe</div><div class="label">One-time</div></div>
        <div class="stat"><div class="num">Yours</div><div class="label">Always</div></div>
    </div>
</div>

<div class="panel-right">
    <div class="form-card">

        <?php if ($success): ?>
            <span class="icon-key">✅</span>
            <h2>Password Reset!</h2>
            <p class="sub">Your password has been updated successfully.</p>
            <div class="success-box">You can now sign in with your new password.</div>
            <a href="UserLogin-form.php" class="btn-login" style="display:block; text-align:center; text-decoration:none; margin-top:1rem;">
                <span>Go to Sign In →</span>
            </a>

        <?php elseif ($invalid): ?>
            <span class="icon-key">⚠️</span>
            <h2>Link Expired</h2>
            <p class="sub">This reset link is invalid, has expired, or has already been used.</p>
            <a href="ForgotPassword.php" class="btn-login" style="display:block; text-align:center; text-decoration:none; margin-top:1.5rem;">
                <span>Request a New Link →</span>
            </a>

        <?php else: ?>
            <span class="icon-key">🔑</span>
            <h2>Set New Password</h2>
            <p class="sub">Resetting password for <strong><?= htmlspecialchars($tokenEmail) ?></strong></p>

            <?php if ($errors): ?>
            <div class="error-box show"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
            <?php endif; ?>

            <form method="POST" action="ResetPassword.php" id="resetForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="field">
                    <label for="new_password">New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="new_password" id="new_password"
                               placeholder="Min. 8 chars, 1 uppercase, 1 number"
                               required autocomplete="new-password">
                        <button type="button" class="toggle-pwd" onclick="togglePwd('new_password',this)">👁</button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <span class="hint" id="strengthLabel"></span>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="confirm_password" id="confirm_password"
                               placeholder="Repeat your new password"
                               required autocomplete="new-password">
                        <button type="button" class="toggle-pwd" onclick="togglePwd('confirm_password',this)">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <span>Reset Password →</span>
                </button>
            </form>
        <?php endif; ?>

    </div>
</div>

<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}

document.getElementById('new_password')?.addEventListener('input', function() {
    const v = this.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    const fill   = document.getElementById('strengthFill');
    const label  = document.getElementById('strengthLabel');
    const levels = ['','Weak','Fair','Good','Strong'];
    const colors = ['','#d63a3a','#e8a020','#2a9d2a','#1a6e1a'];
    fill.style.width  = (score * 25) + '%';
    fill.style.background = colors[score] || 'transparent';
    label.textContent = levels[score] || '';
    label.style.color = colors[score] || '';
});

document.getElementById('resetForm')?.addEventListener('submit', e => {
    const np = document.getElementById('new_password').value;
    const cp = document.getElementById('confirm_password').value;
    if (np !== cp) { e.preventDefault(); alert('Passwords do not match.'); return; }
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').querySelector('span').textContent = 'Resetting…';
});
</script>
</body>
</html>
