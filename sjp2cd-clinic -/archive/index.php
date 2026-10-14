<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

// Restore patient
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $pid = (int)$_GET['restore'];
    $conn->query("UPDATE patients SET status='active', archived_at=NULL, archived_by=NULL WHERE id=$pid");
    header('Location: index.php?success=Patient+restored+to+active');
    exit();
}

// Permanently delete (admin only)
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $_SESSION['role']==='admin') {
    $pid = (int)$_GET['delete'];
    $conn->query("DELETE FROM patients WHERE id=$pid AND status='archived'");
    header('Location: index.php?success=Patient+record+permanently+deleted');
    exit();
}

$search = $_GET['search']??'';
$q = "SELECT p.*, u.full_name as archived_by_name FROM patients p LEFT JOIN users u ON u.id=p.archived_by WHERE p.status='archived'";
if ($search) $q .= " AND (p.full_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR p.student_id LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
$q .= " ORDER BY p.archived_at DESC";
$archived = $conn->query($q);
$total = $conn->query("SELECT COUNT(*) c FROM patients WHERE status='archived'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Archive - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>🗃️ Patient Archive</h3>
        <p>View and manage archived patient records</p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <a href="../patients/list.php" class="btn btn-outline btn-sm">← Active Patients</a>
      </div>
    </div>
    <div class="page-content">
      <?php if(isset($_GET['success'])): ?><div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <span>Archive</span></div>

      <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
        <div class="stat-card" style="border-left-color:#8A94A6">
          <div class="stat-icon" style="background:rgba(138,148,166,.1)">🗃️</div>
          <div class="stat-info"><p>Total Archived</p><h3><?= $total ?></h3></div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon">🧑‍⚕️</div>
          <div class="stat-info"><p>Active Patients</p><h3><?= $conn->query("SELECT COUNT(*) c FROM patients WHERE status='active'")->fetch_assoc()['c'] ?></h3></div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">📋</div>
          <div class="stat-info"><p>Total Records</p><h3><?= $conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'] ?></h3></div>
        </div>
      </div>

      <div class="alert alert-warning">
        ⚠️ <strong>Archive Notice:</strong> Archived patients are hidden from active lists but their medical records are preserved. Restore them anytime or permanently delete (Admin only).
      </div>

      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search archived patients..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="index.php" class="btn btn-outline">Reset</a>
      </form>

      <div class="card">
        <div class="card-header">
          <h3>🗃️ Archived Patient Records</h3>
          <span style="color:rgba(255,255,255,.7);font-size:.85rem"><?= $archived->num_rows ?> record(s)</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Student ID</th><th>Full Name</th><th>Course/Year</th><th>Archived Date</th><th>Archived By</th><th>Medical Records</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if($archived->num_rows===0): ?>
              <tr class="table-empty"><td colspan="7">No archived patients. <a href="../patients/list.php">View active patients →</a></td></tr>
              <?php else: while($p=$archived->fetch_assoc()):
                $rec_count = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE patient_id=".$p['id'])->fetch_assoc()['c'];
              ?>
              <tr style="opacity:.85">
                <td><span style="font-family:monospace;font-weight:700;color:#8A94A6"><?= htmlspecialchars($p['student_id']) ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:34px;height:34px;background:#E8ECF0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#8A94A6"><?= strtoupper(substr($p['full_name'],0,1)) ?></div>
                    <div>
                      <strong><?= htmlspecialchars($p['full_name']) ?></strong><br>
                      <small style="color:#8A94A6"><?= $p['gender'] ?>, <?= $p['age'] ?> yrs | Blood: <?= $p['blood_type']??'?' ?></small>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($p['course']??'-') ?><br><small style="color:#8A94A6"><?= htmlspecialchars($p['year_level']??'-') ?></small></td>
                <td><?= $p['archived_at']?date('M d, Y',strtotime($p['archived_at'])):'-' ?></td>
                <td><?= htmlspecialchars($p['archived_by_name']??'System') ?></td>
                <td><span style="background:#E8F4FD;color:#003087;padding:4px 10px;border-radius:6px;font-weight:700;font-size:.8rem"><?= $rec_count ?> visit(s)</span></td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="?restore=<?= $p['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this patient to active?')">♻️ Restore</a>
                    <a href="../patients/view.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">👁 View</a>
                    <?php if($_SESSION['role']==='admin'): ?>
                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('PERMANENTLY DELETE this patient and all their records?\nThis CANNOT be undone!')">🗑️ Delete</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          <span style="font-size:.85rem;color:#8A94A6">💡 Tip: Restore patients to make them active again. Only admins can permanently delete records.</span>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
