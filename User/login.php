<?php
session_start();

// Already logged in
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

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['userEmail']);
    $pwd   = $_POST['userPassword'];

    if (empty($email) || empty($pwd)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, fname, username, password FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $fname, $username, $hash);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($pwd, $hash)) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $id;
            $_SESSION['username'] = $username;
            $_SESSION['fname']    = $fname;
            $_SESSION['email']    = $email;
            header("Location: dashboard.php");
            exit;
        } else {
            // Support legacy MD5 passwords (from old create.php)
            $stmt2 = $conn->prepare("SELECT id, fname, username, password FROM user WHERE email = ? AND password = ?");
            $md5pwd = md5($pwd);
            $stmt2->bind_param("ss", $email, $md5pwd);
            $stmt2->execute();
            $stmt2->store_result();
            $stmt2->bind_result($id2, $fname2, $username2, $hash2);
            $stmt2->fetch();

            if ($stmt2->num_rows > 0) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $id2;
                $_SESSION['username'] = $username2;
                $_SESSION['fname']    = $fname2;
                $_SESSION['email']    = $email;
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}

if ($error) {
    $_SESSION['login_error'] = $error;
    header("Location: login.html");
    exit;
}
?>