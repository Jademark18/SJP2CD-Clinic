<?php
session_start();
require_once '../includes/auth_admin.php';
require_once '../config/db.php';
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
    $old = $_POST['old_password']??'';
    $new = $_POST['new_password']??'';
    $cnf = $_POST['confirm_new']??'';
    $user = $conn->query("SELECT password FROM users WHERE id=".$_SESSION['user_id'])->fetch_assoc();
    if (!password_verify($old,$user['password'])) { $err = 'Current password is incorrect.'; }
    elseif (strlen($new)<6) { $err = 'New password must be at least 6 characters.'; }
    elseif ($new!==$cnf) { $err = 'Passwords do not match.'; }
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hash' WHERE id=".$_SESSION['user_id']);
        $msg = 'Password changed successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings - Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><h3>⚙️ Settings <span class="admin-badge">ADMIN</span></h3><p>System configuration</p></div>
    </div>
    <div class="page-content">
      <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
      <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

      <div class="card" style="max-width:500px">
        <div class="card-header"><h3>🔐 Change Password</h3></div>
        <form method="POST">
        <div class="card-body">
          <div class="form-group"><label>Current Password</label><input type="password" name="old_password" required></div>
          <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
          <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_new" required></div>
        </div>
        <div class="card-footer"><button type="submit" name="change_password" class="btn btn-primary">🔐 Update Password</button></div>
        </form>
      </div>

      <div class="card" style="max-width:500px;margin-top:24px">
        <div class="card-header card-header-yellow"><h3>ℹ️ System Information</h3></div>
        <div class="card-body">
          <div class="detail-grid" style="grid-template-columns:1fr">
            <div class="detail-item"><label>System Name</label><p>SJPIICD School Clinic Management System</p></div>
            <div class="detail-item"><label>Institution</label><p>ST. JOHN PAUL II COLLEGE OF DAVAO</p></div>
            <div class="detail-item"><label>Version</label><p>1.0.0</p></div>
            <div class="detail-item"><label>Logged In As</label><p><?= htmlspecialchars($_SESSION['full_name']) ?> (<?= $_SESSION['role'] ?>)</p></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
