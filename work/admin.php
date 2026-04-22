<?php
session_start();
require_once 'db.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$conn = db_connect();

// ─── Handle delete ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $delId = (int)$_POST['delete_id'];
    $del   = $conn->prepare("DELETE FROM user WHERE id = ?");
    $del->bind_param("i", $delId);
    $del->execute();
    $del->close();
    header("Location: admin.php?msg=deleted");
    exit;
}

// ─── Search & fetch ───────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$msg    = $_GET['msg'] ?? '';

if ($search !== '') {
    $res  = $conn->prepare(
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
$users = $res->get_result()->fetch_all(MYSQLI_ASSOC);

// ─── Stats ────────────────────────────────────────────────────────────────────
$totalUsers  = $conn->query("SELECT COUNT(*) FROM user")->fetch_row()[0];
$todayNew    = $conn->query("SELECT COUNT(*) FROM user WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];
$maleCount   = $conn->query("SELECT COUNT(*) FROM user WHERE gender = 'male'")->fetch_row()[0];
$femaleCount = $conn->query("SELECT COUNT(*) FROM user WHERE gender = 'female'")->fetch_row()[0];

$adminUsername = htmlspecialchars($_SESSION['admin_username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — UserSpace</title>
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
        --err:      #e05252;
        --green:    #52c47a;
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
        margin-bottom: 0.2rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        letter-spacing: 0.04em;
    }

    .brand-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--gold); }

    .sidebar-role {
        font-size: 0.62rem;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        color: var(--err);
        margin-bottom: 2.5rem;
        padding-left: 0.85rem;
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
        width: 20px; height: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; opacity: 0.75; flex-shrink: 0;
    }

    .nav-item.active .nav-icon { opacity: 1; }

    .sidebar-footer { margin-top: auto; }

    .admin-pill {
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
        background: linear-gradient(135deg, #e05252 0%, #a03030 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Playfair Display', serif;
        font-size: 0.85rem;
        color: white;
        font-weight: 700;
        flex-shrink: 0;
    }

    .admin-info .uname { color: var(--mist); font-size: 0.825rem; font-weight: 500; }
    .admin-info .urole { color: var(--err); font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.1em; }

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
        color: var(--err);
        margin-bottom: 0.3rem;
    }

    .topbar-left h1 {
        font-family: 'Playfair Display', serif;
        font-size: 1.9rem;
        color: var(--mist);
    }

    .date-pill {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 0.45rem 1rem;
        font-size: 0.78rem;
        color: var(--fog);
    }

    /* ── TOAST ── */
    .toast {
        background: rgba(82,196,122,0.1);
        border: 1px solid rgba(82,196,122,0.25);
        border-left: 3px solid var(--green);
        padding: 0.85rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        color: #7de0a3;
        margin-bottom: 1.75rem;
        display: none;
    }
    .toast.show { display: block; }

    /* ── STAT CARDS ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.3rem 1.4rem;
        transition: border-color 0.2s, transform 0.2s;
    }

    .stat-card:hover { border-color: var(--border-lt); transform: translateY(-2px); }

    .stat-card.highlight {
        background: linear-gradient(135deg, rgba(201,151,74,0.12) 0%, rgba(201,151,74,0.04) 100%);
        border-color: rgba(201,151,74,0.25);
    }

    .stat-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: var(--mid);
        margin-bottom: 0.65rem;
    }

    .stat-card.highlight .stat-label { color: rgba(201,151,74,0.55); }

    .stat-value {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        color: var(--mist);
        line-height: 1;
    }

    .stat-card.highlight .stat-value { color: var(--gold-lt); }

    .stat-sub {
        font-size: 0.75rem;
        color: var(--mid);
        margin-top: 0.35rem;
        font-weight: 300;
    }

    /* ── TABLE PANEL ── */
    .table-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .table-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.4rem 1.75rem;
        border-bottom: 1px solid var(--border);
    }

    .table-header h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.25rem;
        color: var(--mist);
    }

    .count-pill {
        background: rgba(201,151,74,0.1);
        border: 1px solid rgba(201,151,74,0.2);
        color: var(--gold);
        font-size: 0.75rem;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
    }

    /* ── SEARCH ── */
    .search-row {
        display: flex;
        gap: 0.75rem;
        padding: 1rem 1.75rem;
        border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,0.01);
    }

    .search-row form {
        display: flex;
        gap: 0.75rem;
        flex: 1;
    }

    .search-row input {
        flex: 1;
        padding: 0.65rem 1rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.875rem;
        color: var(--mist);
        outline: none;
        transition: border-color 0.2s;
    }

    .search-row input::placeholder { color: rgba(154,144,136,0.4); }
    .search-row input:focus { border-color: var(--gold); }

    .btn-search {
        padding: 0.65rem 1.25rem;
        background: var(--gold);
        color: var(--night);
        border: none;
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-search:hover { background: var(--gold-lt); }

    .btn-clear {
        padding: 0.65rem 1rem;
        background: transparent;
        color: var(--fog);
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.875rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.15s;
    }

    .btn-clear:hover { border-color: var(--border-lt); color: var(--mist); }

    /* ── TABLE ── */
    .table-wrap { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; }

    thead tr { background: rgba(255,255,255,0.01); }

    th {
        padding: 0.75rem 1.25rem;
        text-align: left;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--mid);
        font-weight: 600;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }

    td {
        padding: 1rem 1.25rem;
        font-size: 0.875rem;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        color: var(--fog);
    }

    tr:last-child td { border-bottom: none; }

    tbody tr { transition: background 0.12s; }
    tbody tr:hover td { background: rgba(255,255,255,0.02); }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--gold) 0%, #a07038 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Playfair Display', serif;
        font-size: 0.8rem;
        color: var(--night);
        font-weight: 700;
        flex-shrink: 0;
    }

    .user-fullname { color: var(--mist); font-weight: 500; font-size: 0.875rem; }
    .user-handle { font-size: 0.75rem; color: var(--mid); }

    /* Gender badges */
    .g-badge {
        display: inline-block;
        padding: 0.2rem 0.65rem;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 500;
    }

    .g-male   { background: rgba(59,91,219,0.15); color: #7b9aff; border: 1px solid rgba(59,91,219,0.2); }
    .g-female { background: rgba(194,24,91,0.15); color: #f08090; border: 1px solid rgba(194,24,91,0.2); }
    .g-other  { background: rgba(123,31,162,0.15); color: #c090f0; border: 1px solid rgba(123,31,162,0.2); }

    /* Status */
    .status-active {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.78rem;
        color: var(--green);
    }

    .status-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--green);
        box-shadow: 0 0 6px var(--green);
    }

    /* Delete button */
    .btn-del {
        padding: 0.35rem 0.8rem;
        background: rgba(224,82,82,0.1);
        border: 1px solid rgba(224,82,82,0.2);
        color: #f08080;
        border-radius: 6px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.78rem;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
    }

    .btn-del:hover { background: rgba(224,82,82,0.2); border-color: rgba(224,82,82,0.4); }

    /* Empty */
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        color: var(--mid);
    }

    .empty-state .icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.4; }
    .empty-state p { font-size: 0.9rem; }

    /* ── MODAL ── */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.75);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 999;
    }

    .modal-overlay.show { display: flex; }

    .modal {
        background: var(--surface);
        border: 1px solid var(--border-lt);
        border-radius: 14px;
        padding: 2rem;
        width: 100%;
        max-width: 380px;
        animation: modalIn 0.2s ease;
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.4rem;
        color: var(--mist);
        margin-bottom: 0.5rem;
    }

    .modal p {
        color: var(--fog);
        font-size: 0.875rem;
        line-height: 1.6;
        margin-bottom: 1.75rem;
        font-weight: 300;
    }

    .modal-btns {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .btn-cancel {
        padding: 0.7rem 1.25rem;
        background: transparent;
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--fog);
        font-family: 'Outfit', sans-serif;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-cancel:hover { border-color: var(--border-lt); color: var(--mist); }

    .btn-confirm-del {
        padding: 0.7rem 1.25rem;
        background: var(--err);
        border: none;
        border-radius: 8px;
        color: white;
        font-family: 'Outfit', sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-confirm-del:hover { background: #c94040; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1100px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) {
        .sidebar { display: none; }
        .main { margin-left: 0; padding: 1.5rem; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-dot"></span>
        UserSpace
    </div>
    <div class="sidebar-role">Administrator</div>

    <div class="nav-section">Management</div>
    <a href="#" class="nav-item active">
        <span class="nav-icon">⊞</span> Dashboard
    </a>
    <a href="#" class="nav-item">
        <span class="nav-icon">◎</span> Users
    </a>
    <a href="#" class="nav-item">
        <span class="nav-icon">◈</span> Settings
    </a>

    <div class="nav-section">System</div>
    <a href="#" class="nav-item">
        <span class="nav-icon">⬡</span> Logs
    </a>

    <div class="sidebar-footer">
        <div class="admin-pill">
            <div class="avatar-sm">A</div>
            <div class="admin-info">
                <div class="uname"><?= $adminUsername ?></div>
                <div class="urole">Admin</div>
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
            <div class="eyebrow">Admin Panel</div>
            <h1>User Management</h1>
        </div>
        <span class="date-pill"><?= date('l, F j Y') ?></span>
    </div>

    <?php if ($msg === 'deleted'): ?>
    <div class="toast show" id="toast">✓ User deleted successfully.</div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card highlight">
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
            <div class="stat-sub"><?= $totalUsers > 0 ? round($maleCount / $totalUsers * 100) : 0 ?>% of total</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Female Users</div>
            <div class="stat-value"><?= $femaleCount ?></div>
            <div class="stat-sub"><?= $totalUsers > 0 ? round($femaleCount / $totalUsers * 100) : 0 ?>% of total</div>
        </div>
    </div>

    <div class="table-panel">
        <div class="table-header">
            <h2>Registered Users</h2>
            <span class="count-pill"><?= count($users) ?> <?= $search ? 'results' : 'total' ?></span>
        </div>

        <div class="search-row">
            <form method="GET">
                <input type="text" name="search"
                       placeholder="Search by name, email or username…"
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if ($search): ?>
                    <a href="admin.php" class="btn-clear">Clear</a>
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
                <?php foreach ($users as $u):
                    $uInitial = strtoupper(($u['fname'][0] ?? '?') . ($u['lname'][0] ?? ''));
                    $gClass   = 'g-' . ($u['gender'] ?: 'other');
                    $joined   = date("M j, Y", strtotime($u['created_at']));
                ?>
                <tr>
                    <td style="color:var(--mid);font-size:0.75rem"><?= (int)$u['id'] ?></td>
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
                    <td><span class="g-badge <?= $gClass ?>"><?= ucfirst($u['gender'] ?: 'N/A') ?></span></td>
                    <td><?= $joined ?></td>
                    <td><span class="status-active"><span class="status-dot"></span>Active</span></td>
                    <td>
                        <button class="btn-del"
                            onclick="confirmDelete(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['fname'] . ' ' . $u['lname'], ENT_QUOTES) ?>')">
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

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Delete User?</h3>
        <p id="deleteMsg">This action cannot be undone.</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <form method="POST" id="deleteForm" style="display:contents">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="delete_id" id="deleteId">
                <button type="submit" class="btn-confirm-del">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMsg').textContent = `Remove "${name}" permanently? This cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
}

function closeModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

// Auto-dismiss toast
setTimeout(() => {
    const t = document.getElementById('toast');
    if (t) { t.style.opacity = '0'; t.style.transition = 'opacity 0.5s'; setTimeout(() => t.style.display = 'none', 500); }
}, 3500);
</script>
</body>
</html>
