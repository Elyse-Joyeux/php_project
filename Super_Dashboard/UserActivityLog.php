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

// Fetch user
$user = get_user_by_id($conn, $userId);
if (!$user) { session_destroy(); header("Location: UserLogin-form.php"); exit; }

// Fetch activity log with pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// Total count
$countResult = $conn->query("SELECT COUNT(*) as total FROM activity_log WHERE user_id = $userId");
$countRow    = $countResult->fetch_assoc();
$total       = (int)$countRow['total'];
$totalPages  = max(1, ceil($total / $limit));
$page        = min($page, $totalPages);
$offset      = ($page - 1) * $limit;

// Fetch logs
$logsStmt = $conn->prepare(
    "SELECT action, details, logged_at FROM activity_log
     WHERE user_id = ? ORDER BY logged_at DESC LIMIT ? OFFSET ?"
);
$logsStmt->bind_param("iii", $userId, $limit, $offset);
$logsStmt->execute();
$logs = $logsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$logsStmt->close();

$initial    = strtoupper(($user['fname'][0] ?? '?') . ($user['lname'][0] ?? ''));
$genderIcon = ($user['gender'] === 'female') ? '♀' : (($user['gender'] === 'male') ? '♂' : '⚧');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="styles.css">
    <style>
        .log-timestamp { font-size: 0.8rem; color: var(--text-muted); }
    </style>
</head>
<body>

<aside class="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-brand">RCA Portal.</div>
    <div class="nav-label">Menu</div>
    <a href="Userdashboard.php" class="nav-item"><span class="nav-icon">⊞</span> Dashboard</a>
    <a href="UserProfile.php"   class="nav-item"><span class="nav-icon">◎</span> Profile</a>
    <a href="UserSettings.php"  class="nav-item"><span class="nav-icon">◈</span> Settings</a>
    <div class="nav-label">Account</div>
    <a href="UserActivityLog.php" class="nav-item active" aria-current="page"><span class="nav-icon">📋</span> Activity Log</a>
    <a href="UserSettings.php?section=security" class="nav-item"><span class="nav-icon">◇</span> Security</a>
    <div class="sidebar-footer">
        <div class="user-mini">
            <div class="avatar-mini" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
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
        <h1>Activity Log</h1>
        <span class="date-badge"><?= date('l, F j Y') ?></span>
    </div>

    <div class="page-card">
        <h2>Recent Activity</h2>
        <?php if (empty($logs)): ?>
        <div class="empty-state">
            <p>No activity recorded yet.</p>
        </div>
        <?php else: ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <span class="action-badge <?= strtolower(preg_replace('/\s+/', '', $log['action'])) ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                    <td class="log-timestamp"><?= date('M d, Y · H:i', strtotime($log['logged_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=1">« First</a>
            <a href="?page=<?= $page - 1 ?>">‹ Prev</a>
            <?php else: ?>
            <span class="disabled">« First</span>
            <span class="disabled">‹ Prev</span>
            <?php endif; ?>

            <span style="margin:0 .5rem; color:var(--mid);">
                Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong>
            </span>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Next ›</a>
            <a href="?page=<?= $totalPages ?>">Last »</a>
            <?php else: ?>
            <span class="disabled">Next ›</span>
            <span class="disabled">Last »</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
