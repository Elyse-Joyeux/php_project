<?php
session_start();
require_once __DIR__ . '/db.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['admin_id'])) {
    header("Location: UserLogin.html"); exit;
}

$conn = db_connect();

//  Handle POST actions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Delete user
    if (isset($_POST['delete_id'])) {
        $delId = (int)$_POST['delete_id'];
        $del   = $conn->prepare("DELETE FROM user WHERE id = ?");
        $del->bind_param("i", $delId);
        $del->execute();
        $del->close();
        header("Location: Admin.php?msg=deleted"); exit;
    }

    // Suspend / Activate user
    if (isset($_POST['toggle_id'], $_POST['new_status'])) {
        $tid    = (int)$_POST['toggle_id'];
        $newSt  = in_array($_POST['new_status'], ['active','suspended']) ? $_POST['new_status'] : 'active';
        $upd    = $conn->prepare("UPDATE user SET status = ? WHERE id = ?");
        $upd->bind_param("si", $newSt, $tid);
        $upd->execute();
        $upd->close();
        header("Location: Admin.php?msg=" . ($newSt === 'suspended' ? 'suspended' : 'activated')); exit;
    }
}

//  Search, filter, paginate 
$search     = trim($_GET['search']   ?? '');
$filterSt   = $_GET['status']        ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;
$msg        = $_GET['msg']           ?? '';

$where  = "WHERE 1=1";
$params = [];
$types  = '';

if ($search !== '') {
    $like     = "%$search%";
    $where   .= " AND (fname LIKE ? OR lname LIKE ? OR email LIKE ? OR username LIKE ? OR student_id LIKE ?)";
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
    $types   .= 'sssss';
}
if ($filterSt !== '') {
    $where  .= " AND status = ?";
    $params[] = $filterSt;
    $types   .= 's';
}

// Count for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) FROM user $where");
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countStmt->bind_result($totalFiltered);
$countStmt->fetch();
$countStmt->close();
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch page of users
$res = $conn->prepare(
    "SELECT id, fname, lname, email, username, gender, student_id, cohort, track, status, created_at
     FROM user $where ORDER BY created_at DESC LIMIT ? OFFSET ?"
);
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$res->bind_param($allTypes, ...$allParams);
$res->execute();
$users = $res->get_result()->fetch_all(MYSQLI_ASSOC);

//  Stats 
$totalUsers  = $conn->query("SELECT COUNT(*) FROM user")->fetch_row()[0];
$activeUsers = $conn->query("SELECT COUNT(*) FROM user WHERE status='active'")->fetch_row()[0];
$todayNew    = $conn->query("SELECT COUNT(*) FROM user WHERE DATE(created_at)=CURDATE()")->fetch_row()[0];
$suspended   = $conn->query("SELECT COUNT(*) FROM user WHERE status='suspended'")->fetch_row()[0];

$adminUsername = htmlspecialchars($_SESSION['admin_username']);

//  CSV Export 
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','First Name','Last Name','Email','Username','Gender','Student ID','Cohort','Track','Status','Joined']);
    $expStmt = $conn->prepare("SELECT id,fname,lname,email,username,gender,student_id,cohort,track,status,created_at FROM user ORDER BY created_at DESC");
    $expStmt->execute();
    $rows = $expStmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — RCA Student Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --ink:#0f0e0d; --cream:#f5f0e8; --rust:#c94a2a; --rust-lt:#e8683e;
            --warm:#e8dfc8; --mid:#8a7f72; --white:#ffffff;
            --green:#2a9d2a; --red:#d63a3a; --sidebar-w:260px;
        }
        body { min-height:100vh; background:var(--cream); font-family:'DM Sans',sans-serif; color:var(--ink); display:flex; }

        /*  SIDEBAR  */
        .sidebar { width:var(--sidebar-w); background:var(--ink); min-height:100vh; display:flex; flex-direction:column; padding:2rem 1.5rem; position:fixed; top:0; left:0; z-index:100; }
        .sidebar-brand { font-family:'DM Serif Display',serif; font-size:1.4rem; color:var(--cream); margin-bottom:.3rem; }
        .sidebar-role { font-size:.7rem; text-transform:uppercase; letter-spacing:.12em; color:var(--rust); margin-bottom:2.5rem; }
        .nav-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.12em; color:var(--mid); margin-bottom:.6rem; margin-top:1.5rem; }
        .nav-item { display:flex; align-items:center; gap:.75rem; padding:.65rem .85rem; border-radius:8px; color:var(--mid); font-size:.9rem; text-decoration:none; margin-bottom:2px; transition:background .15s,color .15s; }
        .nav-item:hover { background:rgba(255,255,255,.06); color:var(--cream); }
        .nav-item.active { background:var(--rust); color:white; }
        .nav-icon { font-size:1rem; width:20px; text-align:center; }
        .sidebar-footer { margin-top:auto; }
        .admin-mini { display:flex; align-items:center; gap:.75rem; padding:.75rem 0; border-top:1px solid rgba(255,255,255,.07); }
        .admin-avatar { width:38px; height:38px; background:var(--rust); border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'DM Serif Display',serif; font-size:.9rem; color:white; flex-shrink:0; }
        .admin-info .name { color:var(--cream); font-size:.875rem; font-weight:500; }
        .admin-info .role { color:var(--rust); font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; }
        .logout-btn { display:flex; align-items:center; gap:.75rem; padding:.65rem .85rem; border-radius:8px; color:var(--mid); font-size:.875rem; text-decoration:none; margin-top:.5rem; transition:background .15s,color .15s; }
        .logout-btn:hover { background:rgba(201,74,42,.15); color:#e8683e; }

        /*  MAIN  */
        .main { margin-left:var(--sidebar-w); flex:1; padding:2.5rem 3rem; }
        .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:2.5rem; flex-wrap:wrap; gap:1rem; }
        .topbar h1 { font-family:'DM Serif Display',serif; font-size:1.9rem; letter-spacing:-.02em; }
        .topbar-right { display:flex; align-items:center; gap:.75rem; }
        .date-badge { background:var(--white); border:1px solid var(--warm); border-radius:20px; padding:.4rem .9rem; font-size:.8rem; color:var(--mid); }
        .export-btn { background:var(--ink); color:var(--cream); border:none; border-radius:8px; padding:.5rem 1rem; font-family:'DM Sans',sans-serif; font-size:.85rem; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:.4rem; transition:background .15s; }
        .export-btn:hover { background:var(--rust); }

        /*  STATS  */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1.25rem; margin-bottom:2rem; }
        .stat-card { background:var(--white); border-radius:14px; padding:1.4rem 1.5rem; border:1px solid var(--warm); transition:transform .2s,box-shadow .2s; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,.07); }
        .stat-card.dark { background:var(--ink); border-color:var(--ink); }
        .stat-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.1em; color:var(--mid); margin-bottom:.6rem; }
        .stat-card.dark .stat-label { color:rgba(255,255,255,.4); }
        .stat-value { font-family:'DM Serif Display',serif; font-size:2.4rem; color:var(--ink); line-height:1; }
        .stat-card.dark .stat-value { color:var(--cream); }
        .stat-sub { font-size:.78rem; color:var(--mid); margin-top:.3rem; }

        /*  TOAST  */
        .toast { padding:.75rem 1rem; border-radius:8px; font-size:.875rem; margin-bottom:1.5rem; display:none; }
        .toast.show { display:block; }
        .toast.success { background:#d4edda; border-left:3px solid var(--green); color:#1a5c2a; }
        .toast.warning { background:#fff3cd; border-left:3px solid #e8a020; color:#7a5000; }

        /*  TABLE PANEL  */
        .table-panel { background:var(--white); border-radius:14px; border:1px solid var(--warm); overflow:hidden; }
        .table-header { display:flex; align-items:center; justify-content:space-between; padding:1.5rem 1.75rem; border-bottom:1px solid var(--warm); flex-wrap:wrap; gap:.75rem; }
        .table-header h2 { font-family:'DM Serif Display',serif; font-size:1.3rem; }
        .count { font-size:.8rem; color:var(--mid); background:var(--cream); padding:.3rem .75rem; border-radius:20px; border:1px solid var(--warm); }

        /*  FILTERS  */
        .filters { display:flex; gap:.75rem; padding:1rem 1.75rem; border-bottom:1px solid var(--warm); background:#fafaf8; flex-wrap:wrap; }
        .filters input, .filters select {
            padding:.65rem 1rem; border:1.5px solid var(--warm); border-radius:8px;
            font-family:'DM Sans',sans-serif; font-size:.9rem; outline:none; background:white;
            transition:border-color .2s;
        }
        .filters input { flex:1; min-width:180px; }
        .filters input:focus, .filters select:focus { border-color:var(--rust); }
        .filters button { padding:.65rem 1.25rem; background:var(--ink); color:var(--cream); border:none; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:.875rem; cursor:pointer; transition:background .15s; }
        .filters button:hover { background:var(--rust); }
        .filters a.clear { padding:.65rem 1rem; background:transparent; color:var(--mid); border:1.5px solid var(--warm); border-radius:8px; font-size:.875rem; text-decoration:none; display:flex; align-items:center; white-space:nowrap; }
        .filters a.clear:hover { border-color:var(--mid); color:var(--ink); }

        /*  TABLE  */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        thead tr { background:#fafaf8; }
        th { padding:.75rem 1rem; text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.1em; color:var(--mid); font-weight:600; border-bottom:1px solid var(--warm); white-space:nowrap; }
        td { padding:.85rem 1rem; font-size:.875rem; border-bottom:1px solid #f0ebe0; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fdfcf8; }

        .user-cell { display:flex; align-items:center; gap:.75rem; }
        .user-avatar { width:34px; height:34px; border-radius:50%; background:var(--ink); display:flex; align-items:center; justify-content:center; font-family:'DM Serif Display',serif; font-size:.85rem; color:var(--cream); flex-shrink:0; }
        .user-fullname { font-weight:500; }
        .user-handle { font-size:.78rem; color:var(--mid); }
        .user-meta { font-size:.75rem; color:var(--mid); margin-top:2px; }

        .badge { display:inline-block; padding:.2rem .6rem; border-radius:20px; font-size:.75rem; font-weight:500; }
        .gender-male   { background:#e8f0fe; color:#3b5bdb; }
        .gender-female { background:#fce4ec; color:#c2185b; }
        .gender-other  { background:#f3e5f5; color:#7b1fa2; }
        .status-active     { background:#e8f5e9; color:var(--green); }
        .status-suspended  { background:#fde8e4; color:var(--red); }
        .status-graduated  { background:#e3f2fd; color:#1565c0; }

        .action-btns { display:flex; gap:.4rem; }
        .btn-action { padding:.35rem .75rem; border:none; border-radius:6px; font-size:.78rem; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .15s; }
        .btn-suspend { background:#fff3cd; color:#7a5000; }
        .btn-activate { background:#d4edda; color:#1a5c2a; }
        .btn-delete  { background:#fde8e4; color:var(--red); }
        .btn-action:hover { opacity:.8; }

        /*  PAGINATION  */
        .pagination { display:flex; align-items:center; justify-content:center; gap:.5rem; padding:1.25rem; border-top:1px solid var(--warm); }
        .pagination a, .pagination span {
            padding:.4rem .8rem; border-radius:6px; font-size:.85rem; text-decoration:none;
            border:1px solid var(--warm); color:var(--mid);
        }
        .pagination a:hover { background:var(--cream); color:var(--ink); }
        .pagination .current { background:var(--rust); color:white; border-color:var(--rust); }

        /*  EMPTY STATE  */
        .empty-state { text-align:center; padding:4rem 2rem; color:var(--mid); }
        .empty-state .icon { font-size:3rem; margin-bottom:1rem; opacity:.3; }

        /*  MODAL  */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:200; align-items:center; justify-content:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:var(--white); border-radius:16px; padding:2rem; max-width:400px; width:90%; box-shadow:0 24px 48px rgba(0,0,0,.15); }
        .modal h3 { font-family:'DM Serif Display',serif; font-size:1.4rem; margin-bottom:.5rem; }
        .modal p { color:var(--mid); font-size:.9rem; margin-bottom:1.75rem; line-height:1.6; }
        .modal-btns { display:flex; gap:.75rem; justify-content:flex-end; }
        .btn-cancel { padding:.65rem 1.25rem; border:1.5px solid var(--warm); border-radius:8px; background:transparent; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:.9rem; color:var(--mid); }
        .btn-cancel:hover { border-color:var(--mid); color:var(--ink); }
        .btn-confirm-del { padding:.65rem 1.25rem; background:var(--red); color:white; border:none; border-radius:8px; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:.9rem; }

        @media (max-width:1100px) { .stats-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width:768px) { .sidebar { display:none; } .main { margin-left:0; padding:1.5rem; } .stats-grid { grid-template-columns:1fr 1fr; } }
    </style>
</head>
<body>

<aside class="sidebar" role="navigation">
    <div class="sidebar-brand">RCA Portal.</div>
    <div class="sidebar-role">Admin Panel</div>
    <div class="nav-label">Management</div>
    <a href="admin.php" class="nav-item active"><span class="nav-icon">⊞</span> Students</a>
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
        <a href="UserLogout.php" class="logout-btn"><span class="nav-icon">⎋</span> Log Out</a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <h1>Student Management</h1>
        <div class="topbar-right">
            <span class="date-badge"><?= date('l, F j Y') ?></span>
            <a href="Admin.php?export=csv" class="export-btn">⬇ Export CSV</a>
        </div>
    </div>

    <?php
    $toastMap = [
        'deleted'   => ['success', '✓ Student deleted successfully.'],
        'suspended' => ['warning', '⚠ Student account suspended.'],
        'activated' => ['success', '✓ Student account activated.'],
    ];
    if (isset($toastMap[$msg])):
        [$tc, $tm] = $toastMap[$msg];
    ?>
    <div class="toast <?= $tc ?> show" id="toast"><?= $tm ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card dark">
            <div class="stat-label">Total Students</div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-sub">Registered accounts</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-value" style="color:var(--green)"><?= $activeUsers ?></div>
            <div class="stat-sub"><?= $totalUsers > 0 ? round($activeUsers/$totalUsers*100) : 0 ?>% of total</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Joined Today</div>
            <div class="stat-value"><?= $todayNew ?></div>
            <div class="stat-sub">New registrations</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Suspended</div>
            <div class="stat-value" style="color:var(--red)"><?= $suspended ?></div>
            <div class="stat-sub">Restricted accounts</div>
        </div>
    </div>

    <div class="table-panel">
        <div class="table-header">
            <h2>Registered Students</h2>
            <span class="count"><?= $totalFiltered ?> <?= $search || $filterSt ? 'results' : 'total' ?></span>
        </div>
        <div class="filters">
            <form method="GET" style="display:flex;gap:.75rem;flex:1;flex-wrap:wrap">
                <input type="text" name="search"
                       placeholder="Search by name, email, username, student ID…"
                       value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active"    <?= $filterSt==='active'    ? 'selected':'' ?>>Active</option>
                    <option value="suspended" <?= $filterSt==='suspended' ? 'selected':'' ?>>Suspended</option>
                    <option value="graduated" <?= $filterSt==='graduated' ? 'selected':'' ?>>Graduated</option>
                </select>
                <button type="submit">Search</button>
                <?php if ($search || $filterSt): ?>
                <a href="admin.php" class="clear">✕ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-wrap">
            <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="icon">◎</div>
                <p><?= $search || $filterSt ? 'No students match your search.' : 'No students registered yet.' ?></p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Student ID</th>
                        <th>Track</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u):
                    $uInit  = strtoupper(($u['fname'][0]??'?') . ($u['lname'][0]??''));
                    $gClass = 'gender-' . ($u['gender']?:'other');
                    $sClass = 'status-' . ($u['status']?:'active');
                    $joined = date("M j, Y", strtotime($u['created_at']));
                ?>
                <tr>
                    <td style="color:var(--mid);font-size:.78rem"><?= (int)$u['id'] ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar"><?= htmlspecialchars($uInit) ?></div>
                            <div>
                                <div class="user-fullname"><?= htmlspecialchars($u['fname'].' '.$u['lname']) ?></div>
                                <div class="user-handle">@<?= htmlspecialchars($u['username']) ?></div>
                                <?php if ($u['cohort']): ?>
                                <div class="user-meta"><?= htmlspecialchars($u['cohort']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($u['student_id'] ?: '—') ?></td>
                    <td style="color:var(--mid);font-size:.82rem"><?= htmlspecialchars($u['track'] ?: '—') ?></td>
                    <td><span class="badge <?= $gClass ?>"><?= ucfirst($u['gender']?:'N/A') ?></span></td>
                    <td><span class="badge <?= $sClass ?>"><?= ucfirst($u['status']?:'active') ?></span></td>
                    <td style="color:var(--mid)"><?= $joined ?></td>
                    <td>
                        <div class="action-btns">
                            <?php if (($u['status']?:'active') === 'active'): ?>
                            <button class="btn-action btn-suspend"
                                onclick="confirmToggle(<?= (int)$u['id'] ?>,'suspended','<?= htmlspecialchars($u['fname'].' '.$u['lname'],ENT_QUOTES) ?>')">
                                Suspend
                            </button>
                            <?php else: ?>
                            <button class="btn-action btn-activate"
                                onclick="confirmToggle(<?= (int)$u['id'] ?>,'active','<?= htmlspecialchars($u['fname'].' '.$u['lname'],ENT_QUOTES) ?>')">
                                Activate
                            </button>
                            <?php endif; ?>
                            <button class="btn-action btn-delete"
                                onclick="confirmDelete(<?= (int)$u['id'] ?>,'<?= htmlspecialchars($u['fname'].' '.$u['lname'],ENT_QUOTES) ?>')">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterSt) ?>">‹ Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
                <?php if ($p === $page): ?>
                <span class="current"><?= $p ?></span>
                <?php else: ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterSt) ?>"><?= $p ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterSt) ?>">Next ›</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <div class="modal">
        <h3 id="deleteTitle">Delete Student?</h3>
        <p id="deleteMsg">This action cannot be undone.</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModals()">Cancel</button>
            <form method="POST" id="deleteForm" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="delete_id" id="deleteId">
                <button type="submit" class="btn-confirm-del">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- Suspend/Activate Modal -->
<div class="modal-overlay" id="toggleModal" role="dialog" aria-modal="true" aria-labelledby="toggleTitle">
    <div class="modal">
        <h3 id="toggleTitle">Change Status?</h3>
        <p id="toggleMsg">Confirm status change.</p>
        <div class="modal-btns">
            <button class="btn-cancel" onclick="closeModals()">Cancel</button>
            <form method="POST" id="toggleForm" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="toggle_id" id="toggleId">
                <input type="hidden" name="new_status" id="newStatus">
                <button type="submit" class="btn-confirm-del" id="toggleConfirmBtn">Confirm</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteMsg').textContent = `Delete "${name}"? All their data will be permanently removed.`;
    document.getElementById('deleteModal').classList.add('show');
}
function confirmToggle(id, newSt, name) {
    document.getElementById('toggleId').value  = id;
    document.getElementById('newStatus').value = newSt;
    const action = newSt === 'suspended' ? 'suspend' : 'activate';
    document.getElementById('toggleTitle').textContent = (newSt === 'suspended' ? 'Suspend' : 'Activate') + ' Student?';
    document.getElementById('toggleMsg').textContent = `Are you sure you want to ${action} "${name}"?`;
    document.getElementById('toggleConfirmBtn').textContent = newSt === 'suspended' ? 'Yes, Suspend' : 'Yes, Activate';
    document.getElementById('toggleModal').classList.add('show');
}
function closeModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show'));
}
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModals(); });
});
// Close modal on Escape key
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModals(); });
// Auto-hide toast
setTimeout(() => { const t = document.getElementById('toast'); if (t) t.style.display = 'none'; }, 5000);
</script>
</body>
</html>
