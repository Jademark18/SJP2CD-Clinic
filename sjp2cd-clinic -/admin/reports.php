<?php
session_start();
require_once '../includes/auth_admin.php';
require_once '../config/db.php';

$month = $_GET['month'] ?? date('Y-m');
$year  = substr($month, 0, 4);
$mon   = substr($month, 5, 2);

$visits_month   = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE YEAR(visit_date)=$year AND MONTH(visit_date)=$mon")->fetch_assoc()['c'];
$new_patients   = $conn->query("SELECT COUNT(*) c FROM patients WHERE YEAR(created_at)=$year AND MONTH(created_at)=$mon")->fetch_assoc()['c'];
$top_complaints = $conn->query("SELECT chief_complaint, COUNT(*) c FROM medical_records WHERE YEAR(visit_date)=$year AND MONTH(visit_date)=$mon GROUP BY chief_complaint ORDER BY c DESC LIMIT 10");

$gender_dist    = $conn->query("SELECT p.gender, COUNT(mr.id) c FROM medical_records mr JOIN patients p ON p.id=mr.patient_id WHERE YEAR(mr.visit_date)=$year AND MONTH(mr.visit_date)=$mon GROUP BY p.gender");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports - Admin SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>📈 Clinic Reports <span class="admin-badge">ADMIN</span></h3>
        <p>Monthly statistics and health analytics</p>
      </div>
      <div class="topbar-right">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
          <input type="month" name="month" value="<?= $month ?>" style="padding:8px 12px;border:2px solid #E8ECF0;border-radius:8px;font-size:.9rem">
          <button type="submit" class="btn btn-primary btn-sm">Generate</button>
        </form>
        <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Print</button>
      </div>
    </div>
    <div class="page-content">
      <div class="breadcrumb">🏠 <a href="dashboard.php">Admin Dashboard</a> / <span>Reports</span></div>

      <div style="background:var(--yellow-bg);border:2px solid var(--yellow);border-radius:12px;padding:16px 24px;margin-bottom:24px">
        <strong style="color:var(--blue-dark);font-size:1.1rem">📅 Report Period: <?= date('F Y', mktime(0,0,0,$mon,1,$year)) ?></strong>
      </div>

      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:24px">
        <div class="stat-card blue"><div class="stat-icon">📋</div><div class="stat-info"><p>Visits This Month</p><h3><?= $visits_month ?></h3></div></div>
        <div class="stat-card green"><div class="stat-icon">🧑‍⚕️</div><div class="stat-info"><p>New Patients</p><h3><?= $new_patients ?></h3></div></div>
      </div>

      <div class="dashboard-grid" style="grid-template-columns: 1fr;">
        <div class="card">
          <div class="card-header"><h3>🤒 Top Chief Complaints</h3></div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>#</th><th>Complaint</th><th>Cases</th></tr></thead>
              <tbody>
                <?php $i=1; while($r=$top_complaints->fetch_assoc()): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= htmlspecialchars($r['chief_complaint']) ?></td>
                  <td><span style="background:var(--yellow);color:var(--blue-dark);padding:3px 10px;border-radius:20px;font-weight:700;font-size:.8rem"><?= $r['c'] ?></span></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
