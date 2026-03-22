<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Connection</title>
</head>
<body>
    <?php
    $serverName = "localhost";
    $userName = "root";
    $password = "joyeux@2010";
    $db_name = "universitydb";
    $conn = mysqli_connect($serverName, $userName, $password, $db_name);
    if (!$conn)
        echo "Connection failed";
    else 
        echo "Connection successfull!";
    ?>
</body>
</html>