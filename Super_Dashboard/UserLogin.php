<?php
session_start();
require_once __DIR__ . '/db.php';

// Already logged in → redirect away
if (!empty($_SESSION['user_id']))  { header("Location: Userdashboard.php"); exit; }
if (!empty($_SESSION['admin_id'])) { header("Location: Admin.php");     exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: UserLogin.html"); exit;
}

csrf_verify();

$conn  = db_connect();
$email = trim($_POST['userEmail']   ?? '');
$pwd   = $_POST['userPassword']     ?? '';

function login_error(string $msg): never {
    header("Location: UserLogin.html?error=" . urlencode($msg)); exit;
}

if (empty($email) || empty($pwd)) {
    login_error("Please fill in all fields.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    login_error("Invalid email format.");
}

//  Rate limit check 
if (rate_limit_check($conn, $email)) {
    login_error("Too many login attempts. Please wait 15 minutes and try again.");
}

//  Admin check 
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
    header("Location: Admin.php"); exit;
}
$adminStmt->close();

//  Student login 
$stmt = $conn->prepare(
    "SELECT id, fname, lname, email, username, password, status FROM user WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $fname, $lname, $userEmail, $username, $hash, $status);
$stmt->store_result();
$stmt->fetch();

if ($stmt->num_rows === 0) {
    // Timing-safe dummy hash to prevent user-enumeration via response time
    password_verify($pwd, '$2y$12$invaliddummyhashfortimingsafety000000000000000000000000');
    rate_limit_record($conn, $email);
    login_error("Invalid email or password.");
}
$stmt->close();

//  Status check 
if ($status === 'suspended') {
    login_error("Your account has been suspended. Contact an administrator.");
}

//  Verify password 
$loggedIn    = false;
$needsRehash = false;

if (password_verify($pwd, $hash)) {
    $loggedIn = true;
    if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
        $needsRehash = true;
    }
} elseif (strlen($hash) === 32 && md5($pwd) === $hash) {
    // Legacy MD5: verify then upgrade immediately
    $loggedIn    = true;
    $needsRehash = true;
}

if (!$loggedIn) {
    rate_limit_record($conn, $email);
    login_error("Invalid email or password.");
}

//  Upgrade hash if needed 
if ($needsRehash) {
    $newHash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
    $upd     = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
    $upd->bind_param("si", $newHash, $id);
    $upd->execute();
    $upd->close();
}

//  Log activity 
$log = $conn->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, 'Signed in')");
if ($log) { $log->bind_param("i", $id); $log->execute(); $log->close(); }

session_regenerate_id(true);
$_SESSION['user_id']  = $id;
$_SESSION['username'] = $username;
$_SESSION['fname']    = $fname;
$_SESSION['email']    = $userEmail;
header("Location: Userdashboard.php"); exit;
