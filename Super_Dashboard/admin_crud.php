<?php
/**
 * ADMIN CRUD OPERATIONS
 * Handles Create, Read, Update operations for student management
 * All operations require admin session and CSRF token verification
 */
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit("Unauthorized access.");
}

$conn = db_connect();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

//  CREATE NEW STUDENT 
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $fname      = trim($_POST['fname'] ?? '');
    $lname      = trim($_POST['lname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $pwd        = $_POST['password'] ?? '';
    $pwdConf    = $_POST['password_confirm'] ?? '';
    $gender     = $_POST['gender'] ?? 'other';
    $student_id = trim($_POST['student_id'] ?? '');
    $cohort     = trim($_POST['cohort'] ?? '');
    $track      = trim($_POST['track'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    
    $errors = [];
    
    if (empty($fname) || empty($lname)) $errors[] = "First and last names are required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($username)) $errors[] = "Username is required.";
    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) $errors[] = "Username must be 3–50 characters (letters, numbers, underscore only).";
    if (empty($pwd)) $errors[] = "Password is required.";
    if (strlen($pwd) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $pwd) || !preg_match('/[0-9]/', $pwd)) $errors[] = "Password must contain uppercase letter and number.";
    if ($pwd !== $pwdConf) $errors[] = "Passwords do not match.";
    if (!in_array($gender, ['male', 'female', 'other'])) $gender = 'other';
    if (!empty($phone) && !preg_match('/^\+?[\d\s\-]{7,20}$/', $phone)) $errors[] = "Invalid phone number.";
    
    if (!empty($errors)) {
        $_SESSION['crud_errors'] = $errors;
        header("Location: Admin.php?tab=create");
        exit;
    }
    
    // Check duplicates
    $check = $conn->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        $_SESSION['crud_errors'] = ["Email or username already exists."];
        header("Location: Admin.php?tab=create");
        exit;
    }
    $check->close();
    
    if (!empty($student_id)) {
        $sidCheck = $conn->prepare("SELECT id FROM user WHERE student_id = ?");
        $sidCheck->bind_param("s", $student_id);
        $sidCheck->execute();
        $sidCheck->store_result();
        if ($sidCheck->num_rows > 0) {
            $sidCheck->close();
            $_SESSION['crud_errors'] = ["Student ID already registered."];
            header("Location: Admin.php?tab=create");
            exit;
        }
        $sidCheck->close();
    }
    
    $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $conn->prepare(
        "INSERT INTO user (fname, lname, email, username, gender, password, student_id, cohort, track, phone)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "ssssssssss",
        $fname, $lname, $email, $username, $gender, $hash,
        ($student_id ?: null), ($cohort ?: null), ($track ?: null), ($phone ?: null)
    );
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        log_activity($conn, null, "Student created", "Admin created student ID: $newId");
        $_SESSION['crud_success'] = "Student created successfully (ID: $newId)";
        header("Location: Admin.php?tab=list");
        exit;
    } else {
        $_SESSION['crud_errors'] = ["Database error: " . $conn->error];
    }
    $stmt->close();
}

//  READ/VIEW STUDENT DETAILS 
else if ($action === 'view' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user = get_user_by_id($conn, $id);
    
    if (!$user) {
        http_response_code(404);
        exit("Student not found.");
    }
    
    header('Content-Type: application/json');
    echo json_encode($user);
    exit;
}

//  UPDATE STUDENT 
else if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $id         = (int)$_POST['id'] ?? 0;
    $fname      = trim($_POST['fname'] ?? '');
    $lname      = trim($_POST['lname'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $gender     = $_POST['gender'] ?? 'other';
    $student_id = trim($_POST['student_id'] ?? '');
    $cohort     = trim($_POST['cohort'] ?? '');
    $track      = trim($_POST['track'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    
    if ($id <= 0) {
        $_SESSION['crud_errors'] = ["Invalid student ID."];
        header("Location: Admin.php?tab=list");
        exit;
    }
    
    $errors = [];
    if (empty($fname) || empty($lname)) $errors[] = "First and last names are required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if (empty($username)) $errors[] = "Username is required.";
    if (!in_array($gender, ['male', 'female', 'other'])) $gender = 'other';
    if (!empty($phone) && !preg_match('/^\+?[\d\s\-]{7,20}$/', $phone)) $errors[] = "Invalid phone number.";
    
    if (!empty($errors)) {
        $_SESSION['crud_errors'] = $errors;
        header("Location: Admin.php?tab=list&edit=$id");
        exit;
    }
    
    // Check email/username uniqueness
    $check = $conn->prepare("SELECT id FROM user WHERE (email = ? OR username = ?) AND id != ?");
    $check->bind_param("ssi", $email, $username, $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        $_SESSION['crud_errors'] = ["Email or username already in use by another student."];
        header("Location: Admin.php?tab=list&edit=$id");
        exit;
    }
    $check->close();
    
    if (!empty($student_id)) {
        $sidCheck = $conn->prepare("SELECT id FROM user WHERE student_id = ? AND id != ?");
        $sidCheck->bind_param("si", $student_id, $id);
        $sidCheck->execute();
        $sidCheck->store_result();
        if ($sidCheck->num_rows > 0) {
            $sidCheck->close();
            $_SESSION['crud_errors'] = ["Student ID already in use."];
            header("Location: Admin.php?tab=list&edit=$id");
            exit;
        }
        $sidCheck->close();
    }
    
    $updates = [
        'fname' => $fname,
        'lname' => $lname,
        'email' => $email,
        'username' => $username,
        'gender' => $gender,
        'student_id' => ($student_id ?: null),
        'cohort' => ($cohort ?: null),
        'track' => ($track ?: null),
        'phone' => ($phone ?: null)
    ];
    
    if (update_user($conn, $id, $updates)) {
        log_activity($conn, null, "Student updated", "Admin updated student ID: $id");
        $_SESSION['crud_success'] = "Student updated successfully.";
        header("Location: Admin.php?tab=list");
        exit;
    } else {
        $_SESSION['crud_errors'] = ["Update failed. Please try again."];
    }
}

else {
    http_response_code(400);
    exit("Invalid request.");
}
