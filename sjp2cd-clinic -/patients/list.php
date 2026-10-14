<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

// Archive patient
if (isset($_GET['archive']) && is_numeric($_GET['archive'])) {
    $pid = (int)$_GET['archive'];
    $stmt = $conn->prepare("UPDATE patients SET status='archived', archived_at=NOW(), archived_by=? WHERE id=?");
    $stmt->bind_param('ii', $_SESSION['user_id'], $pid);
    $stmt->execute();
    header('Location: list.php?success=Patient+archived+successfully');
    exit();
}

$search = $_GET['search'] ?? '';
$gender = $_GET['gender'] ?? '';
$year   = $_GET['year'] ?? '';

$q = "SELECT * FROM patients WHERE status='active'";
if ($search) $q .= " AND (full_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR student_id LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($gender) $q .= " AND gender='".mysqli_real_escape_string($conn,$gender)."'";
if ($year)   $q .= " AND year_level='".mysqli_real_escape_string($conn,$year)."'";
$q .= " ORDER BY created_at DESC";
$patients = $conn->query($q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Patients - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>🧑‍⚕️ Patient Management</h3>
        <p>View and manage all registered patients</p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <a href="add.php" class="btn btn-yellow">➕ Add Patient</a>
      </div>
    </div>
    <div class="page-content">
      <?php if(isset($_GET['success'])): ?><div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>

      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <span>Patients</span></div>

      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search by name or student ID..." value="<?= htmlspecialchars($search) ?>">
        <select name="gender">
          <option value="">All Genders</option>
          <option value="Male" <?= $gender==='Male'?'selected':'' ?>>Male</option>
          <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
          <option value="Other" <?= $gender==='Other'?'selected':'' ?>>Other</option>
        </select>
        <select name="year">
          <option value="">All Year Levels</option>
          <?php foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year','Graduate'] as $y): ?>
          <option value="<?= $y ?>" <?= $year===$y?'selected':'' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="list.php" class="btn btn-outline">Reset</a>
      </form>

      <div class="card">
        <div class="card-header">
          <h3>🧑‍⚕️ Active Patients</h3>
          <span style="color:rgba(255,255,255,.7);font-size:.85rem"><?= $patients->num_rows ?> patient(s)</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Student ID</th><th>Full Name</th><th>Age/Gender</th><th>Course</th><th>Year</th><th>Contact</th><th>Blood Type</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if($patients->num_rows===0): ?>
              <tr class="table-empty"><td colspan="8">No patients found. <a href="add.php">Add one now →</a></td></tr>
              <?php else: while($p=$patients->fetch_assoc()): ?>
              <tr>
                <td><span style="font-family:monospace;font-weight:700;color:var(--blue-mid)"><?= htmlspecialchars($p['student_id']) ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:34px;height:34px;background:var(--yellow);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--blue-dark);flex-shrink:0"><?= strtoupper(substr($p['full_name'],0,1)) ?></div>
                    <strong><?= htmlspecialchars($p['full_name']) ?></strong>
                  </div>
                </td>
                <td><?= $p['age'] ?> / <span class="badge badge-<?= $p['gender'] ?>"><?= $p['gender'] ?></span></td>
                <td><?= htmlspecialchars($p['course']??'-') ?></td>
                <td><?= htmlspecialchars($p['year_level']??'-') ?></td>
                <td><?= htmlspecialchars($p['contact_number']??'-') ?></td>
                <td><span style="background:#E8F4FD;color:#003087;padding:3px 8px;border-radius:6px;font-weight:700;font-size:.8rem"><?= $p['blood_type']??'?' ?></span></td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">👁 View</a>
                    <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-yellow btn-sm">✏️ Edit</a>
                    <a href="?archive=<?= $p['id'] ?>" class="btn btn-outline btn-sm" onclick="return confirm('Archive this patient?')">🗃️</a>
                  </div>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
