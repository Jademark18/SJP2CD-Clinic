<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
require_once 'config/db.php';
require_once 'config/smtp.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $full_name = trim($_POST['full_name']??'');
    $school_id = trim($_POST['school_id']??'');
    $email     = trim($_POST['email']??'');
    $username  = trim($_POST['username']??'');
    $password  = $_POST['password']??'';
    $confirm   = $_POST['confirm_password']??'';

    if (empty($full_name)||empty($school_id)||empty($email)||empty($username)||empty($password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^\d{8}$/', $school_id)) {
        $error = 'School ID must be exactly 8 digits.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password)<6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? OR email=? OR school_id=?");
        $chk->bind_param('sss',$username,$email,$school_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Username, email, or School ID already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name,school_id,email,username,password,role) VALUES (?,?,?,?,?,'nurse')");
            $stmt->bind_param('sssss',$full_name,$school_id,$email,$username,$hash);
            if ($stmt->execute()) {
                send_welcome_email_to_user($full_name, $email);
                send_new_user_admin_alert($full_name, $email);
                header('Location: index.php?success=Account+created+successfully!+Please+log+in.');
                exit();
            } else {
                $error = 'Registration failed. Try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sign Up - SJPIICD Clinic System</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="school-logo">🏫</div>
      <h1>ST. JOHN PAUL II<br>COLLEGE OF DAVAO</h1>
      <h2>School Clinic Management System</h2>
      <div class="clinic-badge">🏥 Staff Registration</div>
      <div style="margin-top:40px;opacity:.7;font-size:.85rem;line-height:2">
        <p>✅ Manage Patient Records</p>
        <p>✅ Track Medical Visits</p>
        <p>✅ Monitor Medicine Inventory</p>
        <p>✅ Generate Health Reports</p>
      </div>
    </div>
  </div>
  <div class="auth-right" style="overflow-y:auto">
    <h2>Create Account ✨</h2>
    <p class="sub">Register as clinic staff</p>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['full_name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>School ID Number <small>(8 digits)</small></label>
        <div class="input-icon">
          <i>🪪</i>
          <input type="text" name="school_id" placeholder="e.g. 20240001"
            maxlength="8" pattern="\d{8}" inputmode="numeric"
            value="<?= htmlspecialchars($_POST['school_id']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Re-enter password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-yellow btn-full" style="margin-top:8px">
        📝 Create Account
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;font-size:.9rem">
      Already have an account? <a href="index.php" style="color:#003087;font-weight:700">Sign In →</a>
    </div>
  </div>
</div>
</body>
</html>
