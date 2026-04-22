<?php
session_start();
require_once 'C:/xampp/private_configs/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sign_up.php"); exit;
}

csrf_verify();

$conn = db_connect();

function redirect_error(string $msg): never {
    header("Location: sign_up.php?error=" . urlencode($msg)); exit;
}

// ─── Collect & sanitize inputs ────────────────────────────────────────────
$fname      = trim($_POST['userFname']           ?? '');
$lname      = trim($_POST['userLname']           ?? '');
$email      = trim($_POST['userEmail']           ?? '');
$username   = trim($_POST['userName']            ?? '');
$pwd        = $_POST['userPassword']              ?? '';
$pwdConf    = $_POST['userPasswordConfirm']       ?? '';
$gender     = $_POST['gender']                    ?? 'other';
$student_id = trim($_POST['studentId']           ?? '');
$cohort     = trim($_POST['cohort']              ?? '');
$track      = trim($_POST['track']               ?? '');
$phone      = trim($_POST['phone']               ?? '');

// ─── Validation ───────────────────────────────────────────────────────────
if (empty($fname) || empty($lname) || empty($email) || empty($username) || empty($pwd)) {
    redirect_error("All required fields must be filled.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error("Please enter a valid email address.");
}
if (strlen($fname) > 80 || strlen($lname) > 80) {
    redirect_error("Name fields cannot exceed 80 characters.");
}
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    redirect_error("Username must be 3–50 characters and contain only letters, numbers, or underscores.");
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
if (!in_array($gender, ['male', 'female', 'other'], true)) {
    $gender = 'other';
}
if (!empty($phone) && !preg_match('/^\+?[\d\s\-]{7,20}$/', $phone)) {
    redirect_error("Please enter a valid phone number.");
}

// ─── Duplicate check ──────────────────────────────────────────────────────
$check = $conn->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
$check->bind_param("ss", $email, $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    redirect_error("That email or username is already taken. Please choose another.");
}
$check->close();

// ─── Student ID uniqueness ────────────────────────────────────────────────
if (!empty($student_id)) {
    $sidCheck = $conn->prepare("SELECT id FROM user WHERE student_id = ?");
    $sidCheck->bind_param("s", $student_id);
    $sidCheck->execute();
    $sidCheck->store_result();
    if ($sidCheck->num_rows > 0) {
        redirect_error("That student ID is already registered.");
    }
    $sidCheck->close();
}

// ─── Insert ───────────────────────────────────────────────────────────────
$hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);

$sidVal    = $student_id ?: null;
$cohortVal = $cohort     ?: null;
$trackVal  = $track      ?: null;
$phoneVal  = $phone      ?: null;

$stmt = $conn->prepare(
    "INSERT INTO user (fname, lname, email, username, gender, password, student_id, cohort, track, phone)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "ssssssssss",
    $fname, $lname, $email, $username, $gender, $hash,
    $sidVal, $cohortVal, $trackVal, $phoneVal
);

if ($stmt->execute()) {
    $newId = $conn->insert_id;

    // Log creation
    $log = $conn->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, 'Account created')");
    if ($log) { $log->bind_param("i", $newId); $log->execute(); $log->close(); }

    session_regenerate_id(true);
    $_SESSION['user_id']  = $newId;
    $_SESSION['username'] = $username;
    $_SESSION['fname']    = $fname;
    $_SESSION['email']    = $email;
    header("Location: Userdashboard.php"); exit;
} else {
    error_log("Account creation error: " . $conn->error);
    redirect_error("An error occurred while creating your account. Please try again.");
}
