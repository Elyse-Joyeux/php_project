<?php

require_once 'config.php';

if (isLoggedIn() && isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    $user_id = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE user_settings SET theme='$theme' WHERE user_id=$user_id");
}
?>