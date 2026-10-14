<?php
session_start();
require_once 'includes/auth.php';
if ($_SESSION['role']==='admin') { header('Location: admin/dashboard.php'); exit(); }
require_once 'config/db.php';

$total_patients = $conn->query("SELECT COUNT(*) as c FROM patients WHERE status='active'")->fetch_assoc()['c'];
$today_visits   = $conn->query("SELECT COUNT(*) as c FROM medical_records WHERE visit_date=CURDATE()")->fetch_assoc()['c'];
$low_meds       = $conn->query("SELECT COUNT(*) as c FROM medicines WHERE status IN ('low','out_of_stock')")->fetch_assoc()['c'];
$total_records  = $conn->query("SELECT COUNT(*) as c FROM medical_records")->fetch_assoc()['c'];
$this_month     = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())")->fetch_assoc()['c'];

$recent = $conn->query("SELECT mr.*, p.full_name, p.student_id, u.full_name as nurse_name
    FROM medical_records mr
    JOIN patients p ON p.id=mr.patient_id
    JOIN users u ON u.id=mr.attended_by
    ORDER BY mr.created_at DESC LIMIT 8");

$low_med_list = $conn->query("SELECT * FROM medicines WHERE status IN ('low','out_of_stock','expired') ORDER BY FIELD(status,'out_of_stock','expired','low') LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — SJPIICD Clinic</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>🏠 Nurse Dashboard</h3>
        <p>ST. JOHN PAUL II COLLEGE OF DAVAO — School Clinic</p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <span style="font-size:.82rem;color:var(--gray-600)">Welcome, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong></span>
        <a href="logout.php" class="btn btn-danger btn-sm">🚪 Sign Out</a>
      </div>
    </div>

    <div class="page-content">
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon">🧑‍⚕️</div>
          <div class="stat-info">
            <p>Active Patients</p>
            <h3><?= $total_patients ?></h3>
            <small><a href="patients/list.php">View all →</a></small>
          </div>
        </div>
        <div class="stat-card yellow">
          <div class="stat-icon">📋</div>
          <div class="stat-info">
            <p>Today's Visits</p>
            <h3><?= $today_visits ?></h3>
            <small><?= date('F d, Y') ?></small>
          </div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">📊</div>
          <div class="stat-info">
            <p>Total Records</p>
            <h3><?= $total_records ?></h3>
            <small>This month: <strong><?= $this_month ?></strong></small>
          </div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon">💊</div>
          <div class="stat-info">
            <p>Medicine Alerts</p>
            <h3><?= $low_meds ?></h3>
            <small><a href="medicines/list.php" style="color:var(--danger)">Check →</a></small>
          </div>
        </div>
      </div>

      <div class="dashboard-grid">
        <div class="card">
          <div class="card-header">
            <h3>📋 Recent Medical Visits</h3>
            <a href="patients/records.php" class="btn btn-yellow btn-sm">View All</a>
          </div>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr><th>Patient</th><th>Complaint</th><th>Visit Date</th><th>Attended By</th><th>Action</th></tr>
              </thead>
              <tbody>
                <?php if ($recent->num_rows===0): ?>
                <tr class="table-empty"><td colspan="5">No records yet. <a href="patients/add.php">Add a patient</a> first.</td></tr>
                <?php else: while($r=$recent->fetch_assoc()): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                    <small style="color:var(--gray-500);font-family:monospace"><?= htmlspecialchars($r['student_id']) ?></small>
                  </td>
                  <td style="font-size:.82rem"><?= htmlspecialchars(substr($r['chief_complaint'],0,40)) ?>…</td>
                  <td><?= date('M d, Y', strtotime($r['visit_date'])) ?></td>
                  <td style="font-size:.82rem"><?= htmlspecialchars($r['nurse_name']) ?></td>
                  <td><a href="patients/view.php?id=<?= $r['patient_id'] ?>" class="btn btn-primary btn-sm">👁 View</a></td>
                </tr>
                <?php endwhile; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header card-header-yellow">
            <h3>⚠️ Medicine Alerts</h3>
            <a href="medicines/list.php" class="btn btn-primary btn-sm">Manage</a>
          </div>
          <div style="padding:0">
            <?php if ($low_med_list->num_rows===0): ?>
              <div style="padding:40px;text-align:center;color:var(--gray-500)">✅ All medicines well stocked</div>
            <?php else: while($m=$low_med_list->fetch_assoc()): ?>
            <div style="padding:13px 18px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
              <div>
                <strong style="font-size:.85rem"><?= htmlspecialchars($m['medicine_name']) ?></strong><br>
                <small style="color:var(--gray-500)">Qty: <?= $m['quantity'] ?> <?= $m['unit'] ?></small>
              </div>
              <span class="badge badge-<?= $m['status'] ?>"><?= str_replace('_',' ',$m['status']) ?></span>
            </div>
            <?php endwhile; endif; ?>
          </div>
          <div class="card-footer">
            <a href="medicines/add.php" class="btn btn-yellow btn-sm">➕ Restock Medicine</a>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>⚡ Quick Actions</h3>
        </div>
        <div class="card-body">
          <div class="quick-actions">
            <a href="patients/add.php" class="btn btn-yellow">➕ New Patient</a>
            <a href="patients/list.php" class="btn btn-primary">🧑‍⚕️ Patient List</a>
            <a href="patients/records.php" class="btn btn-outline">📋 Medical Records</a>
            <a href="medicines/add.php" class="btn btn-success">💊 Add Medicine</a>
            <a href="archive/index.php" class="btn btn-outline">🗃️ Archive</a>
            <a href="admin/print_records.php" class="btn btn-danger">🖨️ Print Final Records</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
