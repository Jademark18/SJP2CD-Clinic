<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/login.php?msg=Please+log+in+first');
    exit();
}
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'nurse') {
    header('Location: ../dashboard.php?error=Access+denied');
    exit();
}
?>
