<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinic_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("<div style='text-align:center;padding:50px;font-family:Arial'><h2 style='color:red'>Database Connection Failed</h2><p>" . $conn->connect_error . "</p><p>Make sure XAMPP MySQL is running and the database <b>clinic_db</b> has been imported.</p></div>");
}
$conn->set_charset('utf8');
?>
