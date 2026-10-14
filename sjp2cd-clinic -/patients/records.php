<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

$search = $_GET['search'] ?? '';
$date   = $_GET['date']   ?? '';
$gender = $_GET['gender'] ?? '';

$q = "SELECT mr.*, p.full_name, p.student_id, p.gender, p.course, p.year_level, u.full_name as nurse_name
      FROM medical_records mr
      JOIN patients p ON p.id=mr.patient_id
      JOIN users u ON u.id=mr.attended_by
      WHERE 1=1";
if ($search) $q .= " AND (p.full_name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR p.student_id LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR mr.diagnosis LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR mr.chief_complaint LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
if ($date)   $q .= " AND mr.visit_date='".mysqli_real_escape_string($conn,$date)."'";
if ($gender) $q .= " AND p.gender='".mysqli_real_escape_string($conn,$gender)."'";
$q .= " ORDER BY mr.visit_date DESC, mr.created_at DESC";
$records = $conn->query($q);
$total   = $records->num_rows;

// Build print URL for admin — always prints ALL records from admin page
$print_url = '../admin/print_records.php?report_type=medical_records';
if ($date)   $print_url .= '&date_from='.$date.'&date_to='.$date;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Medical Records — SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>📋 All Medical Records</h3>
        <p>Complete visit history across all patients</p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <?php if($_SESSION['role']==='admin'): ?>
          <a href="<?= $print_url ?>" class="btn btn-print">🖨️ Print All Records</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="page-content">
      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <span>Medical Records</span></div>

      <!-- Search & Filter -->
      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search by patient name, ID, or diagnosis..." value="<?= htmlspecialchars($search) ?>">
        <select name="gender" style="padding:10px 14px;border:2px solid var(--gray-200);border-radius:var(--radius-xs);font-size:.875rem;outline:none;background:white;font-family:inherit">
          <option value="">All Genders</option>
          <option value="Male"   <?= $gender==='Male'?'selected':'' ?>>Male</option>
          <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
          <option value="Other"  <?= $gender==='Other'?'selected':'' ?>>Other</option>
        </select>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" style="padding:10px 14px;border:2px solid var(--gray-200);border-radius:var(--radius-xs);font-size:.875rem;outline:none;font-family:inherit">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="records.php" class="btn btn-outline">Reset</a>
      </form>

      <div class="card">
        <div class="card-header">
          <h3>📋 Medical Visit Records</h3>
          <div style="display:flex;align-items:center;gap:12px">
            <span style="color:rgba(255,255,255,.75);font-size:.82rem"><?= $total ?> record<?= $total!==1?'s':'' ?></span>
            <?php if($_SESSION['role']==='admin'): ?>
              <a href="<?= $print_url ?>" class="btn btn-print btn-sm">🖨️ Print All Records</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Visit Date</th>
                <th>Patient</th>
                <th>Course / Year</th>
                <th>Chief Complaint</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>Medicines Given</th>
                <th>Attended By</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if($records->num_rows===0): ?>
              <tr class="table-empty">
                <td colspan="9">
                  <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No records found</h3>
                    <p>Try adjusting your search or filters.</p>
                  </div>
                </td>
              </tr>
              <?php else: while($r=$records->fetch_assoc()): ?>
              <tr>
                <td style="white-space:nowrap">
                  <strong><?= date('M d, Y',strtotime($r['visit_date'])) ?></strong>
                </td>
                <td>
                  <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                  <small style="color:var(--gray-500);font-family:monospace"><?= htmlspecialchars($r['student_id']) ?></small>
                </td>
                <td style="font-size:.8rem">
                  <?= htmlspecialchars($r['course']??'—') ?><br>
                  <span style="color:var(--gray-500)"><?= htmlspecialchars($r['year_level']??'') ?></span>
                </td>
                <td style="font-size:.82rem;max-width:160px"><?= htmlspecialchars($r['chief_complaint']) ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['diagnosis']??'—') ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['treatment']??'—') ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['medicines_given']??'—') ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['nurse_name']) ?></td>
                <td>
                  <a href="view.php?id=<?= $r['patient_id'] ?>" class="btn btn-primary btn-sm">👁 View</a>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if($total > 0 && $_SESSION['role']==='admin'): ?>
        <div class="card-footer">
          <a href="<?= $print_url ?>" class="btn btn-print">🖨️ Print All Records</a>
          <a href="../admin/print_records.php?report_type=all_records" class="btn btn-outline btn-sm">📄 Full Clinic Report</a>
          <span style="font-size:.78rem;color:var(--gray-500)">Opens printable records document with all clinic data</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
