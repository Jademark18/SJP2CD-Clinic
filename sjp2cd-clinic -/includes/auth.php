<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    $login_path = strpos($_SERVER['PHP_SELF'], '/patients/') !== false
        || strpos($_SERVER['PHP_SELF'], '/medicines/') !== false
        || strpos($_SERVER['PHP_SELF'], '/archive/') !== false
        ? '../index.php'
        : 'index.php';
    header('Location: ' . $login_path . '?msg=Please+log+in+first');
    exit();
}
?>
