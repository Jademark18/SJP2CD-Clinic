<?php
session_start();
require_once '../includes/auth_admin.php';
require_once '../config/db.php';
require_once '../config/smtp.php';

$msg = $err = '';

// Toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    $conn->query("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=$uid AND id!=1");
    header('Location: users.php?success=User+status+updated');
    exit();
}
// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid == $_SESSION['user_id']) { header('Location: users.php?error=Cannot+delete+yourself'); exit(); }
    $conn->query("DELETE FROM users WHERE id=$uid");
    header('Location: users.php?success=User+deleted');
    exit();
}
// Add user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_user'])) {
    $fn = trim($_POST['full_name']); $em = trim($_POST['email']); $un = trim($_POST['username']); $pw = $_POST['password']; $rl = $_POST['role'];
    if (empty($fn)||empty($em)||empty($un)||empty($pw)) { $err='All fields required'; }
    else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name,email,username,password,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss',$fn,$em,$un,$hash,$rl);
        if ($stmt->execute()) {
            send_welcome_email_to_user($fn, $em);
            send_new_user_admin_alert($fn, $em);
            $msg = 'User added successfully';
        } else {
            $err = 'Username/email already exists';
        }
    }
}

if (isset($_GET['success'])) $msg = htmlspecialchars($_GET['success']);
if (isset($_GET['error'])) $err = htmlspecialchars($_GET['error']);

$search = $_GET['search'] ?? '';
$filter_role = $_GET['role'] ?? '';
$query = "SELECT * FROM users WHERE 1=1";
if ($search) $query .= " AND (full_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR username LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR email LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($filter_role) $query .= " AND role='".mysqli_real_escape_string($conn,$filter_role)."'";
$query .= " ORDER BY created_at DESC";
$users = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Users - Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>👥 User Management <span class="admin-badge">ADMIN</span></h3>
        <p>Manage system users and access roles</p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <button onclick="document.getElementById('addModal').classList.add('open')" class="btn btn-yellow">➕ Add User</button>
        <a href="../logout.php" class="btn btn-danger btn-sm">🚪 Sign Out</a>
      </div>
    </div>
    <div class="page-content">
      <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
      <?php if($err): ?><div class="alert alert-error">⚠️ <?= $err ?></div><?php endif; ?>

      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search users..." value="<?= htmlspecialchars($search) ?>">
        <select name="role">
          <option value="">All Roles</option>
          <option value="admin" <?= $filter_role==='admin'?'selected':'' ?>>Admin</option>
          <option value="nurse" <?= $filter_role==='nurse'?'selected':'' ?>>Nurse</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="users.php" class="btn btn-outline">Reset</a>
      </form>

      <div class="card">
        <div class="card-header">
          <h3>👥 All System Users</h3>
          <span style="color:rgba(255,255,255,.7);font-size:.85rem"><?= $users->num_rows ?> user(s) found</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php while($u=$users->fetch_assoc()): ?>
              <tr>
                <td><?= $u['id'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:34px;height:34px;background:var(--yellow);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--blue-dark);flex-shrink:0"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
                    <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['status']==='active'?'btn-warning':'btn-success' ?>" style="background:<?= $u['status']==='active'?'#D69E2E':'#38A169' ?>;color:white" onclick="return confirm('Toggle user status?')"><?= $u['status']==='active'?'🔒 Deactivate':'✅ Activate' ?></a>
                    <?php if($u['id']!=$_SESSION['user_id']): ?>
                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">🗑️</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Add New User</h3>
      <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
    <div class="modal-body">
      <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required placeholder="Enter full name"></div>
      <div class="form-row">
        <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="email@domain.com"></div>
        <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Username"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Role</label>
          <select name="role"><option value="nurse">Nurse / Staff</option><option value="admin">Administrator</option></select>
        </div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="Min 6 chars"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
      <button type="submit" name="add_user" class="btn btn-primary">➕ Add User</button>
    </div>
    </form>
  </div>
</div>
</body>
</html>
