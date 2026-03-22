<?php
session_start();

// Already logged in — skip login page
if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

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

    $stmt = $conn->prepare("SELECT id, fname, username, password FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // ✅ CRITICAL FIX: bind_result MUST come BEFORE store_result
    // Wrong order = variables stay null = always saves wrong user to session
    $stmt->bind_result($id, $fname, $username, $hash);
    $stmt->store_result();
    $stmt->fetch();

    $loggedIn = false;

    if ($stmt->num_rows > 0) {
        if (password_verify($pwd, $hash)) {
            $loggedIn = true;
        } elseif (md5($pwd) === $hash) {
            // Legacy MD5 support
            $loggedIn = true;
        }
    }

    if ($loggedIn) {
        // ✅ Wipe any previous user's session data before writing new one
        session_unset();
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