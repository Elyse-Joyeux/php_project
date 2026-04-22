<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sign_up.php");
    exit;
}

csrf_verify();

$conn = db_connect();

$fname    = trim($_POST['userFname']          ?? '');
$lname    = trim($_POST['userLname']          ?? '');
$email    = trim($_POST['userEmail']          ?? '');
$username = trim($_POST['userName']           ?? '');
$pwd      = $_POST['userPassword']            ?? '';
$pwdConf  = $_POST['userPasswordConfirm']     ?? '';
$gender   = $_POST['gender']                  ?? '';

function redirect_error(string $msg): never {
    header("Location: sign_up.php?error=" . urlencode($msg));
    exit;
}

if (empty($fname) || empty($lname) || empty($email) || empty($username) || empty($pwd)) {
    redirect_error("All fields are required.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error("Please enter a valid email address.");
}
if (strlen($pwd) < 8) {
    redirect_error("Password must be at least 8 characters.");
}
if ($pwd !== $pwdConf) {
    redirect_error("Passwords do not match.");
}
if (!preg_match('/[A-Z]/', $pwd) || !preg_match('/[0-9]/', $pwd)) {
    redirect_error("Password must contain at least one uppercase letter and one number.");
}

$check = $conn->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
$check->bind_param("ss", $email, $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    redirect_error("That email or username is already taken. Please choose another.");
}
$check->close();

$hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $conn->prepare(
    "INSERT INTO user (fname, lname, email, username, gender, password) VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssssss", $fname, $lname, $email, $username, $gender, $hash);

if ($stmt->execute()) {
    session_regenerate_id(true);
    $_SESSION['user_id']  = $conn->insert_id;
    $_SESSION['username'] = $username;
    $_SESSION['fname']    = $fname;
    $_SESSION['email']    = $email;
    header("Location: dashboard.php");
    exit;
} else {
    error_log("Account creation error: " . $conn->error);
    redirect_error("An error occurred while creating your account. Please try again.");
}
