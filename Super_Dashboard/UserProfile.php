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

// Handle profile update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fname  = trim($_POST['fname']  ?? '');
    $lname  = trim($_POST['lname']  ?? '');
    $phone  = trim($_POST['phone']  ?? '');
    $bio    = trim($_POST['bio']    ?? '');
    $gender = $_POST['gender']      ?? 'other';
    $cohort = trim($_POST['cohort'] ?? '');
    $track  = trim($_POST['track']  ?? '');

    if (empty($fname) || empty($lname))  $errors[] = "First and last name are required.";
    if (strlen($fname) > 80 || strlen($lname) > 80) $errors[] = "Name cannot exceed 80 characters.";
    if (!in_array($gender, ['male','female','other'], true)) $gender = 'other';
    if (!empty($phone) && !preg_match('/^\+?[\d\s\-]{7,20}$/', $phone)) $errors[] = "Invalid phone number.";
    if (strlen($bio) > 500) $errors[] = "Bio cannot exceed 500 characters.";

    if (empty($errors)) {
        $updates = [
            'fname'  => $fname,
            'lname'  => $lname,
            'phone'  => $phone  ?: null,
            'bio'    => $bio    ?: null,
            'gender' => $gender,
            'cohort' => $cohort ?: null,
            'track'  => $track  ?: null,
        ];
        if (update_user($conn, $userId, $updates)) {
            log_activity($conn, $userId, 'Profile updated');
            $_SESSION['fname'] = $fname;
            $success = "Profile updated successfully.";
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

$user = get_user_by_id($conn, $userId);
if (!$user) { session_destroy(); header("Location: UserLogin-form.php"); exit; }

$initial    = strtoupper(($user['fname'][0] ?? '?') . ($user['lname'][0] ?? ''));
$genderIcon = ($user['gender'] === 'female') ? '♀' : (($user['gender'] === 'male') ? '♂' : '⚧');
$joinDate   = date("F j, Y", strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="styles.css">
    <style>
        .avatar-section { display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; }
        .avatar-display { width: 80px; height: 80px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.8rem; color: var(--white); position: relative; flex-shrink: 0; }
        .avatar-display::after { content: '<?= $genderIcon ?>'; position: absolute; bottom: -2px; right: -4px; font-size: 0.9rem; background: var(--secondary); border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; }
        .readonly-info { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .readonly-info strong { color: var(--text); font-weight: 600; }
    </style>
</head>
<body>
<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-brand">RCA Portal.</div>
    <div class="nav-label">Menu</div>
    <a href="Userdashboard.php" class="nav-item"><span class="nav-icon">⊞</span> Dashboard</a>
    <a href="UserProfile.php" class="nav-item active" aria-current="page"><span class="nav-icon">◎</span> Profile</a>
    <a href="UserSettings.php" class="nav-item"><span class="nav-icon">◈</span> Settings</a>
    <div class="nav-label">Account</div>
    <a href="UserSettings.php#security" class="nav-item"><span class="nav-icon">◇</span> Security</a>
    <a href="UserSettings.php#notifications" class="nav-item"><span class="nav-icon">⬡</span> Notifications</a>
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
        <h1>My Profile</h1>
        <span class="date-badge"><?= date('l, F j Y') ?></span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <!-- Read-only info -->
    <div class="page-card">
        <div class="avatar-section">
            <div class="avatar-display"><?= htmlspecialchars($initial) ?></div>
            <div>
                <p class="readonly-info"><strong><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></strong></p>
                <p class="readonly-info">@<?= htmlspecialchars($user['username']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($user['email']) ?></p>
                <p class="readonly-info">Member since <?= $joinDate ?> &nbsp;·&nbsp;
                    <?php $sc = $user['status']==='active'?'#2a9d2a':($user['status']==='suspended'?'#d63a3a':'#8a7f72'); ?>
                    <strong style="color:<?= $sc ?>"><?= ucfirst(htmlspecialchars($user['status'])) ?></strong>
                </p>
                <p class="readonly-info" style="margin-top:.3rem; font-size:.78rem; color:var(--mid);">
                    Username and email can be changed in <a href="UserSettings.php" style="color:var(--rust)">Settings</a>.
                </p>
            </div>
        </div>
    </div>

    <!-- Editable profile form -->
    <div class="page-card">
        <h2>Edit Profile</h2>
        <form method="POST" action="UserProfile.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-grid">
                <div class="field">
                    <label>First Name *</label>
                    <input type="text" name="fname" value="<?= htmlspecialchars($user['fname']) ?>" required maxlength="80">
                </div>
                <div class="field">
                    <label>Last Name *</label>
                    <input type="text" name="lname" value="<?= htmlspecialchars($user['lname']) ?>" required maxlength="80">
                </div>
                <div class="field">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+250 7XX XXX XXX">
                </div>
                <div class="field">
                    <label>Gender</label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="gender" value="male" <?= $user['gender']==='male'?'checked':'' ?>> Male</label>
                        <label class="radio-label"><input type="radio" name="gender" value="female" <?= $user['gender']==='female'?'checked':'' ?>> Female</label>
                        <label class="radio-label"><input type="radio" name="gender" value="other" <?= ($user['gender']==='other'||!$user['gender'])?'checked':'' ?>> Other</label>
                    </div>
                </div>
                <div class="field">
                    <label>Cohort / Year</label>
                    <input type="text" name="cohort" value="<?= htmlspecialchars($user['cohort'] ?? '') ?>" placeholder="Year 2 · 2024" maxlength="50">
                </div>
                <div class="field">
                    <label>Track / Programme</label>
                    <select name="track">
                        <option value="">— Select track —</option>
                        <?php $tracks = ['Software Development','Data Science & AI','Cybersecurity','Embedded Systems','Network & Infrastructure','Other'];
                        foreach ($tracks as $t): ?>
                        <option value="<?= $t ?>" <?= $user['track']===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full">
                    <label>Bio <span style="color:var(--mid); text-transform:none; font-weight:400">(max 500 chars)</span></label>
                    <textarea name="bio" maxlength="500" placeholder="Tell us a little about yourself…"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="btn-save">Save Changes</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
