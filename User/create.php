<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
</head>
<body>
    <?php
    $serverName = "localhost";
    $userName = "root";
    $password = "joyeux@2010";
    $db_name = "userSignUp";
    $conn = new mysqli($serverName, $userName, $password, $db_name);
    if ($conn->connect_error)
        exit("connection failed".$conn->connect_error);

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $fname = $_POST['userFname'];
        $lname = $_POST['userLname'];
        $email = $_POST['userEmail'];
        $username = $_POST['userName'];
        $password = $_POST['userPassword'];
        $encrypted = md5($password);
        $gender = $_POST['gender'];

        if($_POST['userPassword']!== $_POST['userPasswordConfirm'])
            exit("Passwords do not match!!");

        $sql = "INSERT INTO user(fname, lname, email, username, gender, password) values ('$fname', '$lname', '$email', '$username', '$gender', '$encrypted');";

        if ($conn->query($sql))
            echo "User Created Successfully!!";
        else 
            echo "Error in creating user!".$conn->error;

    }

    
    ?>
</body>
</html>