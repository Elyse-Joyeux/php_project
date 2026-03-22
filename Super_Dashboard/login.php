<?php
session_start();

// ── THE REAL FIX ──────────────────────────────────────────────────────────────
// NEVER redirect an already-logged-in session back to dashboard here.
// If the user explicitly visits login.php, they want to log in as someone else.
// Destroy whatever session exists first, then process the new login cleanly.
session_unset();
session_destroy();
session_write_close();

// Start a brand-new empty session for the incoming login
session_start();
// ─────────────────────────────────────────────────────────────────────────────

$serverName = "localhost";
$userName   = "root";
$password   = "joyeux@2010";
$db_name    = "userSignUp";

$conn = new mysqli($serverName, $userName, $password, $db_name);
if ($conn->connect_error)
    exit("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['userEmail']);
    $pwd   = $_POST['userPassword'];

    if (empty($email) || empty($pwd)) {
        header("Location: login.html?error=" . urlencode("Please fill in all fields."));
        exit;
    }

    // Also check admin table first
    $adminStmt = $conn->prepare("SELECT id, username, password FROM admin WHERE email = ?");
    if ($adminStmt) {
        $adminStmt->bind_param("s", $email);
        $adminStmt->execute();
        $adminStmt->bind_result($adminId, $adminUsername, $adminHash);
        $adminStmt->store_result();
        $adminStmt->fetch();

        if ($adminStmt->num_rows > 0 && password_verify($pwd, $adminHash)) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $adminId;
            $_SESSION['admin_username'] = $adminUsername;
            header("Location: admin.php");
            exit;
        }
        $adminStmt->close();
    }

    // Regular user login
    $stmt = $conn->prepare("SELECT id, fname, username, password FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $fname, $username, $hash);
    $stmt->store_result();
    $stmt->fetch();

    $loggedIn = false;

    if ($stmt->num_rows > 0) {
        if (password_verify($pwd, $hash)) {
            $loggedIn = true;
        } elseif (md5($pwd) === $hash) {
            $loggedIn = true; // legacy MD5 support
        }
    }

    if ($loggedIn) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $id;
        $_SESSION['username'] = $username;
        $_SESSION['fname']    = $fname;
        $_SESSION['email']    = $email;
        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: login.html?error=" . urlencode("Invalid email or password."));
        exit;
    }
}
?>
