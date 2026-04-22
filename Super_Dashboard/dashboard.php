<?php
session_start();
require_once 'db.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$conn = db_connect();

$stmt = $conn->prepare(
    "SELECT fname, lname, email, username, gender, created_at FROM user WHERE id = ?"
);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($fname, $lname, $email, $username, $gender, $created_at);
$stmt->store_result();

if (!$stmt->fetch()) {
    // User ID in session no longer exists in DB
    session_destroy();
    header("Location: login.html");
    exit;
}

$joinDate   = date("F j, Y", strtotime($created_at));
$initial    = strtoupper(($fname[0] ?? '?') . ($lname[0] ?? ''));
$genderIcon = ($gender === 'female') ? '♀' : (($gender === 'male') ? '♂' : '⚧');
$greeting   = (date('H') < 12) ? 'morning' : ((date('H') < 17) ? 'afternoon' : 'evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — UserSpace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <style>.avatar-large::after { content: '<?= $genderIcon ?>'; }</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">UserSpace.</div>
    <div class="nav-label">Menu</div>
    <a href="#" class="nav-item active"><span class="nav-icon">⊞</span> Dashboard</a>
    <a href="#" class="nav-item"><span class="nav-icon">◎</span> Profile</a>
    <a href="#" class="nav-item"><span class="nav-icon">◈</span> Settings</a>
    <div class="nav-label">Account</div>
    <a href="#" class="nav-item"><span class="nav-icon">◇</span> Security</a>
    <a href="#" class="nav-item"><span class="nav-icon">⬡</span> Notifications</a>
    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar-mini"><?= htmlspecialchars($initial) ?></div>
            <div class="user-mini-info">
                <div class="name"><?= htmlspecialchars($fname . ' ' . $lname) ?></div>
                <div class="handle">@<?= htmlspecialchars($username) ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn"><span class="nav-icon">⎋</span> Log Out</a>
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
            <p>Manage your profile, review your account details, and stay in control.</p>
        </div>
        <span class="welcome-emoji">✦</span>
    </div>
    <div class="cards-grid">
        <div class="card card-accent">
            <div class="card-label">Member Since</div>
            <div class="card-value" style="font-size:1.4rem"><?= htmlspecialchars($joinDate) ?></div>
            <div class="card-sub">Welcome to UserSpace</div>
        </div>
        <div class="card">
            <div class="card-label">Username</div>
            <div class="card-value" style="font-size:1.6rem">@<?= htmlspecialchars($username) ?></div>
            <div class="card-sub">Your unique handle</div>
        </div>
        <div class="card">
            <div class="card-label">Account Status</div>
            <div class="card-value" style="font-size:1.6rem; color:#2a9d2a">Active</div>
            <div class="card-sub">All systems normal</div>
        </div>
    </div>
    <div class="bottom-grid">
        <div class="profile-panel">
            <div class="profile-header">
                <div class="avatar-large"><?= htmlspecialchars($initial) ?></div>
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
            <div class="info-row"><span class="info-key">Joined</span><span class="info-val"><?= htmlspecialchars($joinDate) ?></span></div>
        </div>
        <div class="activity-panel">
            <h3>Recent Activity</h3>
            <div class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div class="activity-text"><strong>Account created</strong> — Welcome aboard!</div>
                    <div class="activity-time"><?= htmlspecialchars($joinDate) ?></div>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-dot" style="background:#e8683e"></div>
                <div>
                    <div class="activity-text"><strong>Signed in</strong> to your dashboard</div>
                    <div class="activity-time">Just now</div>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-dot" style="background:#555"></div>
                <div>
                    <div class="activity-text">Profile information <strong>verified</strong></div>
                    <div class="activity-time">Today</div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
