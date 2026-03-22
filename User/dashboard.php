<?php
session_start();

// Protect this page — redirect to login if not authenticated
if (empty($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

// DB connection
$serverName = "localhost";
$dbUser     = "root";
$dbPass     = "joyeux@2010";
$db_name    = "userSignUp";

$conn = new mysqli($serverName, $dbUser, $dbPass, $db_name);
if ($conn->connect_error) exit("Connection failed: " . $conn->connect_error);

// Fetch latest user data
$stmt = $conn->prepare("SELECT fname, lname, email, username, gender, created_at FROM user WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($fname, $lname, $email, $username, $gender, $created_at);
$stmt->fetch();

$joinDate   = date("F j, Y", strtotime($created_at));
$initial    = strtoupper($fname[0] . $lname[0]);
$genderIcon = ($gender === 'female') ? '♀' : (($gender === 'male') ? '♂' : '⚧');
?>
