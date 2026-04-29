<?php
session_start();
require_once __DIR__ . '/db.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id'])) {
    header("Location: UserLogin-form.php"); exit;
}

$conn   = db_connect();
$userId = (int)$_SESSION['user_id'];

$success = '';
$errors  = [];
$section = $_GET['section'] ?? 'account'; // account | security

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // --- Change email / username ---
    if ($action === 'update_account') {
        $section  = 'account';
        $email    = trim($_POST['email']    ?? '');
        $username = trim($_POST['username'] ?? '');

        if (empty($email) || empty($username)) $errors[] = "Email and username are required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) $errors[] = "Username: 3–50 chars, letters/numbers/underscores only.";

        if (empty($errors)) {
            // Uniqueness check
            $chk = $conn->prepare("SELECT id FROM user WHERE (email = ? OR username = ?) AND id != ?");
            $chk->bind_param("ssi", $email, $username, $userId);
            $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) $errors[] = "That email or username is already taken.";
            $chk->close();
        }

        if (empty($errors)) {
            $upd = $conn->prepare("UPDATE user SET email = ?, username = ? WHERE id = ?");
            $upd->bind_param("ssi", $email, $username, $userId);
            if ($upd->execute()) {
                $_SESSION['email']    = $email;
                $_SESSION['username'] = $username;
                log_activity($conn, $userId, 'Account details updated');
                $success = "Account details updated successfully.";
            } else {
                $errors[] = "Update failed. Please try again.";
            }
            $upd->close();
        }
    }

    // --- Change password ---
    if ($action === 'change_password') {
        $section  = 'security';
        $current  = $_POST['current_password']  ?? '';
        $newPwd   = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_password']   ?? '';

        if (empty($current) || empty($newPwd) || empty($confirm)) $errors[] = "All password fields are required.";
        if (strlen($newPwd) < 8)  $errors[] = "New password must be at least 8 characters.";
        if (!preg_match('/[A-Z]/', $newPwd) || !preg_match('/[0-9]/', $newPwd)) $errors[] = "New password must contain at least one uppercase letter and one number.";
        if ($newPwd !== $confirm) $errors[] = "New passwords do not match.";

        if (empty($errors)) {
            // Verify current password
            $row = $conn->query("SELECT password FROM user WHERE id = $userId")->fetch_assoc();
            if (!password_verify($current, $row['password'])) {
                $errors[] = "Current password is incorrect.";
            }
        }

        if (empty($errors)) {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd  = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
            $upd->bind_param("si", $hash, $userId);
            if ($upd->execute()) {
                log_activity($conn, $userId, 'Password changed');
                $success = "Password changed successfully.";
            } else {
                $errors[] = "Failed to update password.";
            }
            $upd->close();
        }
    }
}

$user    = get_user_by_id($conn, $userId);
if (!$user) { session_destroy(); header("Location: UserLogin-form.php"); exit; }
$initial = strtoupper(($user['fname'][0] ?? '?') . ($user['lname'][0] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="styles.css">
    <style>
        .notification-row { display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 0; border-bottom: 1px solid var(--border); }
        .notification-row:last-child { border-bottom: none; }
        .toggle-switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; inset: 0; background: #ccc; border-radius: 12px; cursor: pointer; transition: 0.3s; }
        .slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: 0.3s; }
        .toggle-switch input:checked + .slider { background: var(--primary); }
        .toggle-switch input:checked + .slider::before { transform: translateX(20px); }
        .danger-zone { border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 1.5rem 2rem; background: rgba(239, 68, 68, 0.05); }
        .danger-zone h2 { color: var(--danger); margin-bottom: 0.75rem; }
    </style>
</head>
<body>
<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-brand">RCA Portal.</div>
    <div class="nav-label">Menu</div>
    <a href="Userdashboard.php" class="nav-item"><span class="nav-icon">⊞</span> Dashboard</a>
    <a href="UserProfile.php"   class="nav-item"><span class="nav-icon">◎</span> Profile</a>
    <a href="UserSettings.php"  class="nav-item active" aria-current="page"><span class="nav-icon">◈</span> Settings</a>
    <div class="nav-label">Account</div>
    <a href="UserSettings.php?section=security"      class="nav-item"><span class="nav-icon">◇</span> Security</a>
    <a href="UserSettings.php?section=notifications" class="nav-item"><span class="nav-icon">⬡</span> Notifications</a>
    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar-mini"><?= htmlspecialchars($initial) ?></div>
            <div class="user-mini-info">
                <div class="name"><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></div>
                <div class="handle">@<?= htmlspecialchars($user['username']) ?></div>
            </div>
        </div>
        <a href="UserLogout.php" class="logout-btn"><span class="nav-icon">⎋</span> Log Out</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <h1>Settings</h1>
        <span class="date-badge"><?= date('l, F j Y') ?></span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="settings-tabs">
        <button class="tab-btn <?= $section==='account'?'active':'' ?>"       onclick="switchTab('account')">Account</button>
        <button class="tab-btn <?= $section==='security'?'active':'' ?>"      onclick="switchTab('security')">Security</button>
        <button class="tab-btn <?= $section==='notifications'?'active':'' ?>" onclick="switchTab('notifications')">Notifications</button>
    </div>

    <!-- ACCOUNT TAB -->
    <div class="tab-panel <?= $section==='account'?'active':'' ?>" id="tab-account">
        <div class="page-card">
            <h2>Account Details</h2>
            <form method="POST" action="UserSettings.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="update_account">
                <div class="form-grid">
                    <div class="field">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="field">
                        <label>Username *</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
                        <span class="hint">Letters, numbers, underscores only</span>
                    </div>
                </div>
                <button type="submit" class="btn-save">Update Account</button>
            </form>
        </div>
    </div>

    <!-- SECURITY TAB -->
    <div class="tab-panel <?= $section==='security'?'active':'' ?>" id="tab-security">
        <div class="page-card" id="security">
            <h2>🔐 Change Password</h2>
            <form method="POST" action="UserSettings.php" id="pwdForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="field">
                    <label>Current Password *</label>
                    <div class="password-wrap">
                        <input type="password" name="current_password" id="currentPwd" required autocomplete="current-password">
                        <button type="button" class="toggle-pwd" onclick="togglePwd('currentPwd',this)">👁</button>
                    </div>
                </div>
                <div class="field">
                    <label>New Password *</label>
                    <div class="password-wrap">
                        <input type="password" name="new_password" id="newPwd" required autocomplete="new-password"
                               placeholder="Min. 8 chars, 1 uppercase, 1 number">
                        <button type="button" class="toggle-pwd" onclick="togglePwd('newPwd',this)">👁</button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <span class="hint" id="strengthLabel"></span>
                </div>
                <div class="field">
                    <label>Confirm New Password *</label>
                    <div class="password-wrap">
                        <input type="password" name="confirm_password" id="confirmPwd" required autocomplete="new-password">
                        <button type="button" class="toggle-pwd" onclick="togglePwd('confirmPwd',this)">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn-save">Change Password</button>
                <div style="margin-top:.75rem; font-size:.85rem; color:var(--mid);">
                    Forgot your password? <a href="ForgotPassword.php" style="color:var(--rust); font-weight:500;">Reset it here</a>
                </div>
            </form>
        </div>

        <div class="danger-zone">
            <h2>Danger Zone</h2>
            <p>Logging out will end your current session immediately.</p>
            <a href="UserLogout.php" class="btn-danger">Log Out Now</a>
        </div>
    </div>

    <!-- NOTIFICATIONS TAB -->
    <div class="tab-panel <?= $section==='notifications'?'active':'' ?>" id="tab-notifications">
        <div class="page-card" id="notifications">
            <h2>🔔 Notification Preferences</h2>
            <p style="font-size:.875rem; color:var(--mid); margin-bottom:1.25rem;">
                Email notifications are sent to <strong><?= htmlspecialchars($user['email']) ?></strong>.
            </p>
            <div class="notification-row">
                <div class="notification-label">
                    <strong>Account Activity</strong>
                    <span>Get notified when you sign in from a new device</span>
                </div>
                <label class="toggle-switch"><input type="checkbox" checked><span class="slider"></span></label>
            </div>
            <div class="notification-row">
                <div class="notification-label">
                    <strong>Profile Updates</strong>
                    <span>Confirmation emails when your profile changes</span>
                </div>
                <label class="toggle-switch"><input type="checkbox" checked><span class="slider"></span></label>
            </div>
            <div class="notification-row">
                <div class="notification-label">
                    <strong>Portal Announcements</strong>
                    <span>News and updates from RCA Portal</span>
                </div>
                <label class="toggle-switch"><input type="checkbox"><span class="slider"></span></label>
            </div>
            <p style="font-size:.78rem; color:var(--mid); margin-top:1.25rem;">
                ⓘ Notification preferences are saved per browser session in this demo.
            </p>
        </div>
    </div>
</main>

<script>
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b => {
        if (b.textContent.toLowerCase().includes(name)) b.classList.add('active');
    });
    history.replaceState(null, '', '?section=' + name);
}

// Active tab from URL
const sec = new URLSearchParams(window.location.search).get('section');
if (sec) switchTab(sec);
// Anchor links
if (window.location.hash === '#security')      switchTab('security');
if (window.location.hash === '#notifications') switchTab('notifications');

function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.textContent = show ? '🙈' : '👁';
}

document.getElementById('newPwd').addEventListener('input', function() {
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
    fill.style.width = (score * 25) + '%';
    fill.style.background = colors[score] || 'transparent';
    label.textContent = levels[score] || '';
    label.style.color = colors[score] || '';
});

document.getElementById('pwdForm').addEventListener('submit', e => {
    const np = document.getElementById('newPwd').value;
    const cp = document.getElementById('confirmPwd').value;
    if (np !== cp) { e.preventDefault(); alert('New passwords do not match.'); }
});
</script>
</body>
</html>
