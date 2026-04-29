<?php
$password = 'Admin@RCA2026';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo 'Plain Password: ' . $password . PHP_EOL;
echo 'Hashed Password: ' . $hash . PHP_EOL;
