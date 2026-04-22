<?php
session_start();
require_once 'C:/xampp/private_configs/db.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id'])) {
    header("Location: UserLogin.html"); exit;
}

$conn = db_connect();

$stmt = $conn->prepare(
    "SELECT fname, lname, email, username, gender, student_id,
            cohort, track, phone, status, created_at
     FROM user WHERE id = ?"
);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($fname, $lname, $email, $username, $gender,
                   $student_id, $cohort, $track, $phone, $status, $created_at);
$stmt->store_result();

if (!$stmt->fetch()) {
    session_destroy();
    header("Location: UserLogin.html"); exit;
}
$stmt->close();

// ─── Activity log (last 5 actions) ───────────────────────────────────────
$actStmt = $conn->prepare(
    "SELECT action, logged_at FROM activity_log
     WHERE user_id = ? ORDER BY logged_at DESC LIMIT 5"
);
$actStmt->bind_param("i", $_SESSION['user_id']);
$actStmt->execute();
$activities = $actStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$actStmt->close();

$joinDate    = date("F j, Y", strtotime($created_at));
$initial     = strtoupper(($fname[0] ?? '?') . ($lname[0] ?? ''));
$genderIcon  = ($gender === 'female') ? '♀' : (($gender === 'male') ? '♂' : '⚧');
$hour        = (int)date('H');
$greeting    = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
$statusColor = $status === 'active' ? '#2a9d2a' : ($status === 'suspended' ? '#d63a3a' : '#8a7f72');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Userdashboard.css">
    <style>.avatar-large::after { content: '<?= $genderIcon ?>'; }</style>
</head>
<body>

<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-brand">RCA Portal.</div>
    <div class="nav-label">Menu</div>
    <a href="Userdashboard.php" class="nav-item active" aria-current="page"><span class="nav-icon">⊞</span> Dashboard</a>
    <a href="#" class="nav-item"><span class="nav-icon">◎</span> Profile</a>
    <a href="#" class="nav-item"><span class="nav-icon">◈</span> Settings</a>
    <div class="nav-label">Account</div>
    <a href="#" class="nav-item"><span class="nav-icon">◇</span> Security</a>
    <a href="#" class="nav-item"><span class="nav-icon">⬡</span> Notifications</a>
    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar-mini" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
            <div class="user-mini-info">
                <div class="name"><?= htmlspecialchars($fname . ' ' . $lname) ?></div>
                <div class="handle">@<?= htmlspecialchars($username) ?></div>
            </div>
        </div>
        <a href="UserLogout.php" class="logout-btn"><span class="nav-icon">⎋</span> Log Out</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <h1>Good <?= $greeting ?>, <?= htmlspecialchars($fname) ?> 👋</h1>
        <span class="date-badge"><?= date('l, F j Y') ?></span>
    </div>
    <div class="welcome-banner">
        <div>
            <h2>Your dashboard is ready.</h2>
            <p>Manage your profile, review your academic details, and stay in control.</p>
        </div>
        <span class="welcome-emoji">✦</span>
    </div>

    <div class="cards-grid">
        <div class="card card-accent">
            <div class="card-label">Member Since</div>
            <div class="card-value" style="font-size:1.4rem"><?= htmlspecialchars($joinDate) ?></div>
            <div class="card-sub">Welcome to RCA Portal</div>
        </div>
        <div class="card">
            <div class="card-label">Student ID</div>
            <div class="card-value" style="font-size:1.4rem"><?= htmlspecialchars($student_id ?: '—') ?></div>
            <div class="card-sub"><?= htmlspecialchars($cohort ?: 'Cohort not set') ?></div>
        </div>
        <div class="card">
            <div class="card-label">Account Status</div>
            <div class="card-value" style="font-size:1.4rem; color:<?= $statusColor ?>"><?= ucfirst(htmlspecialchars($status)) ?></div>
            <div class="card-sub"><?= htmlspecialchars($track ?: 'Track not set') ?></div>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="profile-panel">
            <div class="profile-header">
                <div class="avatar-large" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
                <div class="profile-name">
                    <h3><?= htmlspecialchars($fname . ' ' . $lname) ?></h3>
                    <span>@<?= htmlspecialchars($username) ?></span>
                </div>
            </div>
            <div class="info-row"><span class="info-key">First Name</span><span class="info-val"><?= htmlspecialchars($fname) ?></span></div>
            <div class="info-row"><span class="info-key">Last Name</span><span class="info-val"><?= htmlspecialchars($lname) ?></span></div>
            <div class="info-row"><span class="info-key">Email</span><span class="info-val"><?= htmlspecialchars($email) ?></span></div>
            <div class="info-row"><span class="info-key">Username</span><span class="info-val">@<?= htmlspecialchars($username) ?></span></div>
            <div class="info-row"><span class="info-key">Gender</span><span class="info-val"><?= ucfirst(htmlspecialchars($gender ?: 'Not specified')) ?></span></div>
            <?php if ($phone): ?>
            <div class="info-row"><span class="info-key">Phone</span><span class="info-val"><?= htmlspecialchars($phone) ?></span></div>
            <?php endif; ?>
            <?php if ($student_id): ?>
            <div class="info-row"><span class="info-key">Student ID</span><span class="info-val"><?= htmlspecialchars($student_id) ?></span></div>
            <?php endif; ?>
            <?php if ($cohort): ?>
            <div class="info-row"><span class="info-key">Cohort</span><span class="info-val"><?= htmlspecialchars($cohort) ?></span></div>
            <?php endif; ?>
            <?php if ($track): ?>
            <div class="info-row"><span class="info-key">Track</span><span class="info-val"><?= htmlspecialchars($track) ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-key">Joined</span><span class="info-val"><?= htmlspecialchars($joinDate) ?></span></div>
        </div>

        <div class="activity-panel">
            <h3>Recent Activity</h3>
            <?php if (empty($activities)): ?>
            <div class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div class="activity-text"><strong>Account created</strong> — Welcome aboard!</div>
                    <div class="activity-time"><?= htmlspecialchars($joinDate) ?></div>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($activities as $act): ?>
            <div class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div class="activity-text"><strong><?= htmlspecialchars($act['action']) ?></strong></div>
                    <div class="activity-time"><?= date("M j, Y · g:i A", strtotime($act['logged_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
