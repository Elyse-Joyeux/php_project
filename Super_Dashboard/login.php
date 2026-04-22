<?php
session_start();
require_once 'db.php';

// Already logged in → redirect away
if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
if (!empty($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit;
}

csrf_verify();

$conn  = db_connect();
$email = trim($_POST['userEmail']    ?? '');
$pwd   = $_POST['userPassword']      ?? '';

if (empty($email) || empty($pwd)) {
    header("Location: login.html?error=" . urlencode("Please fill in all fields."));
    exit;
}

// ─── Admin check ─────────────────────────────────────────────────────────
$adminStmt = $conn->prepare("SELECT id, username, password FROM admin WHERE email = ?");
$adminStmt->bind_param("s", $email);
$adminStmt->execute();
$adminStmt->bind_result($adminId, $adminUsername, $adminHash);
$adminStmt->store_result();
$adminStmt->fetch();

if ($adminStmt->num_rows > 0 && password_verify($pwd, $adminHash)) {
    session_regenerate_id(true);
    $_SESSION['admin_id']       = $adminId;
    $_SESSION['admin_username'] = $adminUsername;
    $adminStmt->close();
    header("Location: admin.php");
    exit;
}
$adminStmt->close();

// ─── Regular user login ───────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT id, fname, lname, email, username, password FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $fname, $lname, $userEmail, $username, $hash);
$stmt->store_result();
$stmt->fetch();

if ($stmt->num_rows === 0) {
    // Timing-safe: still run a dummy verify so response time doesn't leak user existence
    password_verify($pwd, '$2y$12$invaliddummyhashfortimingsafety000000000000000000000000');
    header("Location: login.html?error=" . urlencode("Invalid email or password."));
    exit;
}

$loggedIn   = false;
$needsRehash = false;

if (password_verify($pwd, $hash)) {
    $loggedIn = true;
    // Upgrade cost if needed
    if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
        $needsRehash = true;
    }
} elseif (strlen($hash) === 32 && md5($pwd) === $hash) {
    // ─── Legacy MD5: verify then immediately upgrade to bcrypt ───────────
    $loggedIn    = true;
    $needsRehash = true;
}

$stmt->close();

if (!$loggedIn) {
    header("Location: login.html?error=" . urlencode("Invalid email or password."));
    exit;
}

// ─── Upgrade hash if needed (MD5 → bcrypt, or low-cost bcrypt → higher) ──
if ($needsRehash) {
    $newHash  = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
    $upd      = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
    $upd->bind_param("si", $newHash, $id);
    $upd->execute();
    $upd->close();
}

session_regenerate_id(true);
$_SESSION['user_id']  = $id;
$_SESSION['username'] = $username;
$_SESSION['fname']    = $fname;
$_SESSION['email']    = $userEmail;
header("Location: dashboard.php");
exit;
