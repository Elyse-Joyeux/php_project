<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['admin_id'])) {
    header("Location: login.html");
    exit;
}

$serverName = "localhost";
$dbUser     = "root";
$dbPass     = "joyeux@2010";
$db_name    = "userSignUp";

$conn = new mysqli($serverName, $dbUser, $dbPass, $db_name);
if ($conn->connect_error) exit("Connection failed: " . $conn->connect_error);

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $del = $conn->prepare("DELETE FROM user WHERE id = ?");
    $del->bind_param("i", $delId);
    $del->execute();
    header("Location: admin.php?msg=deleted");
    exit;
}

// Search filter
$search = trim($_GET['search'] ?? '');
$msg    = $_GET['msg'] ?? '';

// Fetch all users
if ($search !== '') {
    $res = $conn->prepare(
        "SELECT id, fname, lname, email, username, gender, created_at 
         FROM user 
         WHERE fname LIKE ? OR lname LIKE ? OR email LIKE ? OR username LIKE ?
         ORDER BY created_at DESC"
    );
    $like = "%$search%";
    $res->bind_param("ssss", $like, $like, $like, $like);
} else {
    $res = $conn->prepare(
        "SELECT id, fname, lname, email, username, gender, created_at 
         FROM user ORDER BY created_at DESC"
    );
}
$res->execute();
$result = $res->get_result();
$users  = $result->fetch_all(MYSQLI_ASSOC);

// Stats
$totalRes   = $conn->query("SELECT COUNT(*) as c FROM user");
$totalRow   = $totalRes->fetch_assoc();
$totalUsers = $totalRow['c'];

$todayRes   = $conn->query("SELECT COUNT(*) as c FROM user WHERE DATE(created_at) = CURDATE()");
$todayRow   = $todayRes->fetch_assoc();
$todayNew   = $todayRow['c'];

$maleRes    = $conn->query("SELECT COUNT(*) as c FROM user WHERE gender = 'male'");
$maleRow    = $maleRes->fetch_assoc();
$maleCount  = $maleRow['c'];

$femaleRes   = $conn->query("SELECT COUNT(*) as c FROM user WHERE gender = 'female'");
$femaleRow   = $femaleRes->fetch_assoc();
$femaleCount = $femaleRow['c'];

$adminUsername = htmlspecialchars($_SESSION['admin_username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — UserSpace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:   #0f0e0d;
            --cream: #f5f0e8;
            --rust:  #c94a2a;
            --warm:  #e8dfc8;
            --mid:   #8a7f72;
            --white: #ffffff;
            --green: #2a9d2a;
            --red:   #d63a3a;
            --sidebar-w: 260px;
        }
        body { min-height: 100vh; background: var(--cream); font-family: 'DM Sans', sans-serif; color: var(--ink); display: flex; }

        /* SIDEBAR */
        .sidebar { width: var(--sidebar-w); background: var(--ink); min-height: 100vh; display: flex; flex-direction: column; padding: 2rem 1.5rem; position: fixed; top: 0; left: 0; z-index: 100; }
        .sidebar-brand { font-family: 'DM Serif Display', serif; font-size: 1.4rem; color: var(--cream); margin-bottom: 0.3rem; }
        .sidebar-role { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--rust); margin-bottom: 2.5rem; }
        .nav-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mid); margin-bottom: 0.6rem; margin-top: 1.5rem; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 8px; color: var(--mid); font-size: 0.9rem; text-decoration: none; margin-bottom: 2px; transition: background 0.15s, color 0.15s; }
        .nav-item:hover { background: rgba(255,255,255,0.06); color: var(--cream); }
        .nav-item.active { background: var(--rust); color: white; }
        .nav-icon { font-size: 1rem; width: 20px; text-align: center; }
        .sidebar-footer { margin-top: auto; }
        .admin-mini { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-top: 1px solid rgba(255,255,255,0.07); }
        .admin-avatar { width: 38px; height: 38px; background: var(--rust); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: 'DM Serif Display', serif; font-size: 0.9rem; color: white; flex-shrink: 0; }
        .admin-info .name { color: var(--cream); font-size: 0.875rem; font-weight: 500; }
        .admin-info .role { color: var(--rust); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .logout-btn { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.85rem; border-radius: 8px; color: var(--mid); font-size: 0.875rem; cursor: pointer; text-decoration: none; margin-top: 0.5rem; transition: background 0.15s, color 0.15s; }
        .logout-btn:hover { background: rgba(201,74,42,0.15); color: #e8683e; }

        /* MAIN */
        .main { margin-left: var(--sidebar-w); flex: 1; padding: 2.5rem 3rem; }
        .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2.5rem; }
        .topbar h1 { font-family: 'DM Serif Display', serif; font-size: 1.9rem; letter-spacing: -0.02em; }
        .date-badge { background: var(--white); border: 1px solid var(--warm); border-radius: 20px; padding: 0.4rem 0.9rem; font-size: 0.8rem; color: var(--mid); }

        /* STAT CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card { background: var(--white); border-radius: 14px; padding: 1.4rem 1.5rem; border: 1px solid var(--warm); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.07); }
        .stat-card.dark { background: var(--ink); border-color: var(--ink); }
        .stat-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid); margin-bottom: 0.6rem; }
        .stat-card.dark .stat-label { color: rgba(255,255,255,0.4); }
        .stat-value { font-family: 'DM Serif Display', serif; font-size: 2.4rem; color: var(--ink); line-height: 1; }
        .stat-card.dark .stat-value { color: var(--cream); }
        .stat-sub { font-size: 0.78rem; color: var(--mid); margin-top: 0.3rem; }
        .stat-card.dark .stat-sub { color: rgba(255,255,255,0.3); }

        /* TOAST */
        .toast { background: #d4edda; border-left: 3px solid var(--green); color: #1a5c2a; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.5rem; display: none; }
        .toast.show { display: block; }

        /* USER TABLE PANEL */
        .table-panel { background: var(--white); border-radius: 14px; border: 1px solid var(--warm); overflow: hidden; }
        .table-header { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--warm); }
        .table-header h2 { font-family: 'DM Serif Display', serif; font-size: 1.3rem; }
        .table-header .count { font-size: 0.8rem; color: var(--mid); background: var(--cream); padding: 0.3rem 0.75rem; border-radius: 20px; border: 1px solid var(--warm); }

        /* SEARCH */
        .search-bar { display: flex; gap: 0.75rem; padding: 1rem 1.75rem; border-bottom: 1px solid var(--warm); background: #fafaf8; }
        .search-bar input { flex: 1; padding: 0.65rem 1rem; border: 1.5px solid var(--warm); border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; outline: none; transition: border-color 0.2s; }
        .search-bar input:focus { border-color: var(--rust); }
        .search-bar button { padding: 0.65rem 1.25rem; background: var(--ink); color: var(--cream); border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.875rem; cursor: pointer; transition: background 0.15s; }
        .search-bar button:hover { background: var(--rust); }
        .search-bar a { padding: 0.65rem 1rem; background: transparent; color: var(--mid); border: 1.5px solid var(--warm); border-radius: 8px; font-size: 0.875rem; text-decoration: none; display: flex; align-items: center; transition: all 0.15s; }
        .search-bar a:hover { border-color: var(--mid); color: var(--ink); }

        /* TABLE */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #fafaf8; }
        th { padding: 0.75rem 1.25rem; text-align: left; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid); font-weight: 600; border-bottom: 1px solid var(--warm); white-space: nowrap; }
        td { padding: 1rem 1.25rem; font-size: 0.875rem; border-bottom: 1px solid #f0ebe0; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fdfcf8; }

        .user-cell { display: flex; align-items: center; gap: 0.75rem; }
        .user-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); display: flex; align-items: center; justify-content: center; font-family: 'DM Serif Display', serif; font-size: 0.85rem; color: var(--cream); flex-shrink: 0; }
        .user-fullname { font-weight: 500; }
        .user-handle { font-size: 0.78rem; color: var(--mid); }

        .gender-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .gender-male   { background: #e8f0fe; color: #3b5bdb; }
        .gender-female { background: #fce4ec; color: #c2185b; }
        .gender-other  { background: #f3e5f5; color: #7b1fa2; }

        .status-active { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; color: var(--green); }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); }

        .btn-delete { background: #fde8e4; color: var(--red); border: none; border-radius: 6px; padding: 0.4rem 0.8rem; font-size: 0.78rem; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background 0.15s; }
        .btn-delete:hover { background: var(--red); color: white; }

        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--mid); }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .empty-state p { font-size: 0.9rem; }

        /* CONFIRM MODAL */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 999; display: none; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal { background: white; border-radius: 14px; padding: 2rem; max-width: 380px; width: 90%; text-align: center; }
        .modal h3 { font-family: 'DM Serif Display', serif; font-size: 1.4rem; margin-bottom: 0.5rem; }
        .modal p { color: var(--mid); font-size: 0.9rem; margin-bottom: 1.5rem; }
        .modal-btns { display: flex; gap: 0.75rem; justify-content: center; }
        .modal-btns button { padding: 0.7rem 1.5rem; border-radius: 8px; border: none; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; cursor: pointer; }
        .btn-cancel { background: var(--warm); color: var(--ink); }
        .btn-confirm-del { background: var(--red); color: white; }

        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; padding: 1.5rem; } .stats-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">UserSpace.</div>
    <div class="sidebar-role">Admin Panel</div>
    <div class="nav-label">Overview</div>
    <a href="admin.php" class="nav-item active"><span class="nav-icon">◉</span> All Users</a>
    <div class="nav-label">System</div>
    <a href="#" class="nav-item"><span class="nav-icon">◈</span> Settings</a>
    <a href="#" class="nav-item"><span class="nav-icon">⬡</span> Logs</a>
    <div class="sidebar-footer">
        <div class="admin-mini">
            <div class="admin-avatar">A</div>
            <div class="admin-info">
                <div class="name"><?= $adminUsername ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn"><span class="nav-icon">⎋</span> Log Out</a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <h1>Admin Dashboard</h1>
        <span class="date-badge"><?= date('l, F j Y') ?></span>
    </div>

    <?php if ($msg === 'deleted'): ?>
    <div class="toast show">✓ User deleted successfully.</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card dark">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-sub">Registered accounts</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Joined Today</div>
            <div class="stat-value"><?= $todayNew ?></div>
            <div class="stat-sub">New today</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Male Users</div>
            <div class="stat-value"><?= $maleCount ?></div>
            <div class="stat-sub"><?= $totalUsers > 0 ? round($maleCount/$totalUsers*100) : 0 ?>% of total</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Female Users</div>
            <div class="stat-value"><?= $femaleCount ?></div>
            <div class="stat-sub"><?= $totalUsers > 0 ? round($femaleCount/$totalUsers*100) : 0 ?>% of total</div>
        </div>
    </div>

    <!-- User Table -->
    <div class="table-panel">
        <div class="table-header">
            <h2>Registered Users</h2>
            <span class="count"><?= count($users) ?> <?= $search ? 'results' : 'total' ?></span>
        </div>
        <div class="search-bar">
            <form method="GET" style="display:flex;gap:0.75rem;flex:1">
                <input type="text" name="search" placeholder="Search by name, email or username…" value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
                <?php if ($search): ?>
                    <a href="admin.php">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-wrap">
            <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="icon">◎</div>
                <p><?= $search ? 'No users match your search.' : 'No users registered yet.' ?></p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $i => $u):
                    $uInitial = strtoupper(($u['fname'][0] ?? '?') . ($u['lname'][0] ?? ''));
                    $gClass   = 'gender-' . ($u['gender'] ?: 'other');
                    $joined   = date("M j, Y", strtotime($u['created_at']));
                ?>
                <tr>
                    <td style="color:var(--mid);font-size:0.78rem"><?= $u['id'] ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar"><?= htmlspecialchars($uInitial) ?></div>
                            <div>
                                <div class="user-fullname"><?= htmlspecialchars($u['fname'] . ' ' . $u['lname']) ?></div>
                                <div class="user-handle">@<?= htmlspecialchars($u['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="gender-badge <?= $gClass ?>"><?= ucfirst($u['gender'] ?: 'N/A') ?></span></td>
                    <td style="color:var(--mid)"><?= $joined ?></td>
                    <td><span class="status-active"><span class="status-dot"></span>Active</span></td>
                    <td>
                        <button class="btn-delete" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['fname'] . ' ' . $u['lname'], ENT_QUOTES) ?>')">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Delete User?</h3>
        <p id="deleteMsg">This action cannot be undone.</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_id" id="deleteId">
                <button type="submit" class="btn-confirm-del">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMsg').textContent = `Delete "${name}"? This cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('show');
}
// Auto-hide toast
setTimeout(() => {
    const t = document.querySelector('.toast');
    if (t) t.style.display = 'none';
}, 4000);
</script>
</body>
</html>
