<?php

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_result'])) {
        $student_id = $_POST['student_id'];
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        $marks = (int)$_POST['marks'];
        $exam_type = $_POST['exam_type'];
        $term = date('n') <= 4 ? 1 : (date('n') <= 8 ? 2 : 3);
        $year = date('Y');
        
        $grade = '';
        if ($marks >= 80) $grade = 'A';
        elseif ($marks >= 70) $grade = 'B';
        elseif ($marks >= 60) $grade = 'C';
        else $grade = 'D';
        
        $insert = "INSERT INTO results (student_id, subject, exam_type, marks, grade, term, year) 
                   VALUES ($student_id, '$subject', '$exam_type', $marks, '$grade', $term, $year)";
        mysqli_query($conn, $insert);
        logAction($_SESSION['user_id'], 'Add Result', "Added result for student ID: $student_id");
    }
    
    if (isset($_POST['delete_result'])) {
        $result_id = $_POST['result_id'];
        mysqli_query($conn, "DELETE FROM results WHERE id=$result_id");
        logAction($_SESSION['user_id'], 'Delete Result', "Deleted result ID: $result_id");
    }
}

redirect('admin_dashboard.php?msg=Results updated');
?>