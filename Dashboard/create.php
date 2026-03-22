<?php
session_start();

$serverName = "localhost";
$userName   = "root";
$password   = "joyeux@2010";
$db_name    = "userSignUp";

$conn = new mysqli($serverName, $userName, $password, $db_name);
if ($conn->connect_error)
    exit("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname    = trim($_POST['userFname']);
    $lname    = trim($_POST['userLname']);
    $email    = trim($_POST['userEmail']);
    $username = trim($_POST['userName']);
    $pwd      = $_POST['userPassword'];
    $pwdConf  = $_POST['userPasswordConfirm'];
    $gender   = $_POST['gender'] ?? '';

    // Server-side validation
    if (empty($fname) || empty($lname) || empty($email) || empty($username) || empty($pwd)) {
        exit("All fields are required.");
    }
    if ($pwd !== $pwdConf) {
        exit("Passwords do not match!");
    }
    if (strlen($pwd) < 6) {
        exit("Password must be at least 6 characters.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Invalid email address.");
    }

    // Check for duplicate email or username
    $check = $conn->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        exit("Email or username is already taken.");
    }

    // Use password_hash (bcrypt) — NOT md5
    $encrypted = password_hash($pwd, PASSWORD_BCRYPT);

    $stmt = $conn->prepare(
        "INSERT INTO user (fname, lname, email, username, gender, password) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssss", $fname, $lname, $email, $username, $gender, $encrypted);

    if ($stmt->execute()) {
        // Log user in immediately after signup
        $_SESSION['user_id']  = $conn->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['fname']    = $fname;
        $_SESSION['email']    = $email;
        header("Location: dashboard.php");
        exit;
    } else {
        exit("Error creating account: " . $conn->error);
    }
}
?>