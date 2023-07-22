<?php
if (!ISSET($config)) $config = require('./auth/config.php');
$servername = $config['db_host'];
$username = $config['db_user'];
$password = $config['db_password'];
$dbname = $config['db_name'];

//connect to db
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($servername, $username, $password, $dbname);
return $conn;
