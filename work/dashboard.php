<?php
session_start();
require_once 'db.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
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
    session_destroy();
    header("Location: login.php");
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --night:    #0c0b0a;
        --surface:  #141210;
        --panel:    #1a1714;
        --border:   #2a2520;
        --border-lt:#342e28;
        --gold:     #c9974a;
        --gold-lt:  #e0b870;
        --mist:     #f7f3ed;
        --fog:      #9a9088;
        --mid:      #665e56;
        --sidebar:  240px;
    }

    body {
        min-height: 100vh;
        background: var(--night);
        font-family: 'Outfit', sans-serif;
        color: var(--mist);
        display: flex;
    }

    /* ── SIDEBAR ── */
    .sidebar {
        width: var(--sidebar);
        background: var(--surface);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 2rem 1.25rem;
        position: fixed; top: 0; left: 0;
        z-index: 100;
        border-right: 1px solid var(--border);
    }

    .sidebar-brand {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        color: var(--gold);
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        letter-spacing: 0.04em;
    }

    .brand-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gold); }

    .sidebar-tagline {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--mid);
        margin-bottom: 2.5rem;
        padding-left: 0.75rem;
    }

    .nav-section {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--mid);
        margin: 1.25rem 0 0.5rem;
        padding: 0 0.5rem;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        color: var(--fog);
        font-size: 0.875rem;
        text-decoration: none;
        margin-bottom: 2px;
        transition: background 0.15s, color 0.15s;
    }

    .nav-item:hover { background: rgba(201,151,74,0.07); color: var(--mist); }
    .nav-item.active { background: rgba(201,151,74,0.12); color: var(--gold); border: 1px solid rgba(201,151,74,0.2); }

    .nav-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        opacity: 0.75;
        flex-shrink: 0;
    }

    .nav-item.active .nav-icon { opacity: 1; }

    .sidebar-footer { margin-top: auto; }

    .user-pill {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: rgba(255,255,255,0.02);
        margin-bottom: 0.5rem;
    }

    .avatar-sm {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--gold) 0%, #a07038 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Playfair Display', serif;
        font-size: 0.8rem;
        color: var(--night);
        font-weight: 700;
        flex-shrink: 0;
    }

    .user-info .uname { color: var(--mist); font-size: 0.825rem; font-weight: 500; }
    .user-info .uhandle { color: var(--mid); font-size: 0.72rem; }

    .logout-btn {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.6rem 0.75rem;
        border-radius: 8px;
        color: var(--mid);
        font-size: 0.85rem;
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }

    .logout-btn:hover { background: rgba(224,82,82,0.1); color: #f08080; }

    /* ── MAIN ── */
    .main {
        margin-left: var(--sidebar);
        flex: 1;
        padding: 2.5rem 2.75rem;
        min-height: 100vh;
    }

    /* ── TOPBAR ── */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2.5rem;
    }

    .topbar-left .eyebrow {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: var(--gold);
        margin-bottom: 0.3rem;
    }

    .topbar-left h1 {
        font-family: 'Playfair Display', serif;
        font-size: 1.9rem;
        letter-spacing: -0.01em;
        color: var(--mist);
    }

    .date-pill {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 0.45rem 1rem;
        font-size: 0.78rem;
        color: var(--fog);
        letter-spacing: 0.02em;
    }

    /* ── BANNER ── */
    .welcome-banner {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--border-lt);
        border-radius: 14px;
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--gold) 0%, #a07038 100%);
    }

    .welcome-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 60% 100% at 0% 50%, rgba(201,151,74,0.06) 0%, transparent 70%);
        pointer-events: none;
    }

    .banner-text .greeting-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--gold);
        margin-bottom: 0.4rem;
    }

    .banner-text h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.45rem;
        color: var(--mist);
        margin-bottom: 0.3rem;
    }

    .banner-text p {
        font-size: 0.85rem;
        color: var(--fog);
        font-weight: 300;
    }

    .banner-glyph {
        font-family: 'Playfair Display', serif;
        font-size: 3.5rem;
        color: rgba(201,151,74,0.15);
        font-style: italic;
        position: relative; z-index: 1;
        line-height: 1;
        user-select: none;
    }

    /* ── CARDS GRID ── */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.1rem;
        margin-bottom: 1.75rem;
    }

    .card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.4rem 1.5rem;
        transition: border-color 0.2s, transform 0.2s;
    }

    .card:hover {
        border-color: var(--border-lt);
        transform: translateY(-2px);
    }

    .card-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--mid);
        margin-bottom: 0.7rem;
    }

    .card-value {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: var(--mist);
        line-height: 1.2;
    }

    .card-sub {
        font-size: 0.75rem;
        color: var(--mid);
        margin-top: 0.35rem;
        font-weight: 300;
    }

    .card-gold {
        background: linear-gradient(135deg, rgba(201,151,74,0.12) 0%, rgba(201,151,74,0.04) 100%);
        border-color: rgba(201,151,74,0.25);
    }

    .card-gold .card-label { color: rgba(201,151,74,0.6); }
    .card-gold .card-value { color: var(--gold-lt); }
    .card-gold .card-sub { color: rgba(201,151,74,0.45); }

    /* ── BOTTOM GRID ── */
    .bottom-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 1.1rem;
    }

    /* ── PROFILE PANEL ── */
    .profile-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.75rem;
    }

    .profile-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
        margin-bottom: 1.25rem;
    }

    .avatar-lg {
        width: 60px; height: 60px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--gold) 0%, #a07038 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: var(--night);
        font-weight: 700;
        flex-shrink: 0;
        position: relative;
    }

    .avatar-lg .gender-badge {
        position: absolute;
        bottom: -4px; right: -4px;
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 50%;
        width: 20px; height: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.65rem;
        color: var(--gold);
    }

    .profile-name h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        color: var(--mist);
        margin-bottom: 0.2rem;
    }

    .profile-name .handle {
        font-size: 0.8rem;
        color: var(--gold);
    }

    .info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
    }

    .info-row:last-child { border-bottom: none; }

    .info-key {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--mid);
    }

    .info-val {
        font-size: 0.875rem;
        color: var(--mist);
        font-weight: 400;
    }

    /* ── ACTIVITY ── */
    .activity-panel {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.75rem;
    }

    .activity-panel h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        color: var(--mist);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .activity-panel h3 span {
        font-family: 'Outfit', sans-serif;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--mid);
        font-weight: 400;
    }

    .activity-item {
        display: flex;
        gap: 0.85rem;
        margin-bottom: 1.25rem;
        align-items: flex-start;
    }

    .activity-item:last-child { margin-bottom: 0; }

    .a-line {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
        flex-shrink: 0;
        padding-top: 4px;
    }

    .a-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--gold);
        flex-shrink: 0;
    }

    .a-dot.dim { background: var(--border-lt); }

    .a-connector {
        width: 1px;
        flex: 1;
        min-height: 20px;
        background: var(--border);
        margin-top: 4px;
    }

    .a-text { font-size: 0.85rem; color: var(--fog); line-height: 1.5; }
    .a-text strong { color: var(--mist); font-weight: 500; }
    .a-time { font-size: 0.72rem; color: var(--mid); margin-top: 3px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1100px) {
        .cards-grid { grid-template-columns: 1fr 1fr; }
        .bottom-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .sidebar { display: none; }
        .main { margin-left: 0; padding: 1.5rem; }
        .cards-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-dot"></span>
        UserSpace
    </div>
    <div class="sidebar-tagline">Personal Dashboard</div>

    <div class="nav-section">Navigate</div>
    <a href="#" class="nav-item active">
        <span class="nav-icon">⊞</span> Dashboard
    </a>
    <a href="#" class="nav-item">
        <span class="nav-icon">◎</span> Profile
    </a>
    <a href="#" class="nav-item">
        <span class="nav-icon">◈</span> Settings
    </a>

    <div class="nav-section">Account</div>
    <a href="#" class="nav-item">
        <span class="nav-icon">◇</span> Security
    </a>
    <a href="#" class="nav-item">
        <span class="nav-icon">⬡</span> Notifications
    </a>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="avatar-sm"><?= htmlspecialchars($initial) ?></div>
            <div class="user-info">
                <div class="uname"><?= htmlspecialchars($fname . ' ' . $lname) ?></div>
                <div class="uhandle">@<?= htmlspecialchars($username) ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <span class="nav-icon">⎋</span> Log Out
        </a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <div class="eyebrow">Overview</div>
            <h1>Good <?= $greeting ?>, <?= htmlspecialchars($fname) ?></h1>
        </div>
        <span class="date-pill"><?= date('l, F j Y') ?></span>
    </div>

    <div class="welcome-banner">
        <div class="banner-text">
            <div class="greeting-label">Welcome back</div>
            <h2>Your dashboard is ready.</h2>
            <p>Manage your profile, review account details, and stay in control.</p>
        </div>
        <div class="banner-glyph">U</div>
    </div>

    <div class="cards-grid">
        <div class="card card-gold">
            <div class="card-label">Member Since</div>
            <div class="card-value" style="font-size:1.15rem"><?= htmlspecialchars($joinDate) ?></div>
            <div class="card-sub">Welcome to UserSpace</div>
        </div>
        <div class="card">
            <div class="card-label">Username</div>
            <div class="card-value">@<?= htmlspecialchars($username) ?></div>
            <div class="card-sub">Your unique handle</div>
        </div>
        <div class="card">
            <div class="card-label">Account Status</div>
            <div class="card-value" style="color:#52c47a;font-size:1.2rem">● Active</div>
            <div class="card-sub">All systems normal</div>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="profile-panel">
            <div class="profile-header">
                <div class="avatar-lg">
                    <?= htmlspecialchars($initial) ?>
                    <span class="gender-badge"><?= $genderIcon ?></span>
                </div>
                <div class="profile-name">
                    <h3><?= htmlspecialchars($fname . ' ' . $lname) ?></h3>
                    <div class="handle">@<?= htmlspecialchars($username) ?></div>
                </div>
            </div>

            <div class="info-row">
                <span class="info-key">First Name</span>
                <span class="info-val"><?= htmlspecialchars($fname) ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Last Name</span>
                <span class="info-val"><?= htmlspecialchars($lname) ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Email</span>
                <span class="info-val"><?= htmlspecialchars($email) ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Username</span>
                <span class="info-val">@<?= htmlspecialchars($username) ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Gender</span>
                <span class="info-val"><?= ucfirst(htmlspecialchars($gender ?: 'Not specified')) ?></span>
            </div>
            <div class="info-row">
                <span class="info-key">Joined</span>
                <span class="info-val"><?= htmlspecialchars($joinDate) ?></span>
            </div>
        </div>

        <div class="activity-panel">
            <h3>Activity <span>Recent</span></h3>

            <div class="activity-item">
                <div class="a-line">
                    <div class="a-dot"></div>
                    <div class="a-connector"></div>
                </div>
                <div>
                    <div class="a-text"><strong>Account created</strong> — Welcome aboard!</div>
                    <div class="a-time"><?= htmlspecialchars($joinDate) ?></div>
                </div>
            </div>

            <div class="activity-item">
                <div class="a-line">
                    <div class="a-dot"></div>
                    <div class="a-connector"></div>
                </div>
                <div>
                    <div class="a-text"><strong>Signed in</strong> to your dashboard</div>
                    <div class="a-time">Just now</div>
                </div>
            </div>

            <div class="activity-item">
                <div class="a-line">
                    <div class="a-dot dim"></div>
                </div>
                <div>
                    <div class="a-text">Profile <strong>verified</strong></div>
                    <div class="a-time">Today</div>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
