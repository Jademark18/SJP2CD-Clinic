<?php
session_start();
require_once '../includes/auth_admin.php';
require_once '../config/db.php';

// Filters
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$gender    = $_GET['gender']    ?? '';
$course    = $_GET['course']    ?? '';
$report_type = $_GET['report_type'] ?? 'all_records';

// Sanitize
$date_from_s = mysqli_real_escape_string($conn, $date_from);
$date_to_s   = mysqli_real_escape_string($conn, $date_to);
$gender_s    = mysqli_real_escape_string($conn, $gender);
$course_s    = mysqli_real_escape_string($conn, $course);

// Summary stats
$total_patients  = $conn->query("SELECT COUNT(*) c FROM patients WHERE status='active'")->fetch_assoc()['c'];
$total_archived  = $conn->query("SELECT COUNT(*) c FROM patients WHERE status='archived'")->fetch_assoc()['c'];
$total_records   = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE visit_date BETWEEN '$date_from_s' AND '$date_to_s'")->fetch_assoc()['c'];
$total_users     = $conn->query("SELECT COUNT(*) c FROM users WHERE status='active'")->fetch_assoc()['c'];
$total_medicines = $conn->query("SELECT COUNT(*) c FROM medicines")->fetch_assoc()['c'];
$med_issues      = $conn->query("SELECT COUNT(*) c FROM medicines WHERE status IN ('low','out_of_stock','expired')")->fetch_assoc()['c'];

// Male/Female count
$male_count   = $conn->query("SELECT COUNT(*) c FROM patients WHERE gender='Male' AND status='active'")->fetch_assoc()['c'];
$female_count = $conn->query("SELECT COUNT(*) c FROM patients WHERE gender='Female' AND status='active'")->fetch_assoc()['c'];

// Build patient query
$patient_q = "SELECT p.*, 
    (SELECT COUNT(*) FROM medical_records mr WHERE mr.patient_id=p.id AND mr.visit_date BETWEEN '$date_from_s' AND '$date_to_s') as visit_count,
    (SELECT mr2.visit_date FROM medical_records mr2 WHERE mr2.patient_id=p.id ORDER BY mr2.visit_date DESC LIMIT 1) as last_visit
  FROM patients p WHERE p.status='active'";
if ($gender_s) $patient_q .= " AND p.gender='$gender_s'";
if ($course_s) $patient_q .= " AND p.course LIKE '%$course_s%'";
$patient_q .= " ORDER BY p.full_name ASC";

$patients = $conn->query($patient_q);

// Medical records query
$records_q = "SELECT mr.*, p.full_name as patient_name, p.student_id, p.gender, p.course, p.year_level, u.full_name as nurse_name
  FROM medical_records mr
  JOIN patients p ON p.id=mr.patient_id
  JOIN users u ON u.id=mr.attended_by
  WHERE mr.visit_date BETWEEN '$date_from_s' AND '$date_to_s'";
if ($gender_s) $records_q .= " AND p.gender='$gender_s'";
if ($course_s) $records_q .= " AND p.course LIKE '%$course_s%'";
$records_q .= " ORDER BY mr.visit_date DESC";
$records = $conn->query($records_q);

// Top complaints
$top_comp = $conn->query("SELECT chief_complaint, COUNT(*) c FROM medical_records WHERE visit_date BETWEEN '$date_from_s' AND '$date_to_s' GROUP BY chief_complaint ORDER BY c DESC LIMIT 10");

// Medicine inventory
$medicines = $conn->query("SELECT * FROM medicines ORDER BY status, medicine_name");

// Users list
$users_list = $conn->query("SELECT * FROM users ORDER BY role, full_name");

// Courses
$courses_q = $conn->query("SELECT DISTINCT course FROM patients WHERE course IS NOT NULL AND course!='' ORDER BY course");

// Generated on
$generated_at = date('F d, Y \a\t h:i A');
$period_label = date('F d, Y', strtotime($date_from)) . ' — ' . date('F d, Y', strtotime($date_to));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Print Records — Admin SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
<style>
  .filter-panel { background:white; border:1px solid var(--gray-200); border-radius:var(--radius); padding:24px; margin-bottom:24px; box-shadow:var(--shadow-sm); }
  .filter-panel h3 { font-size:.9rem; font-weight:700; color:var(--blue-dark); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
  .filter-grid { display:grid; grid-template-columns:repeat(4,1fr) auto; gap:12px; align-items:end; }
  .filter-grid label { font-size:.75rem; font-weight:600; color:var(--gray-700); text-transform:uppercase; letter-spacing:.3px; display:block; margin-bottom:5px; }
  .filter-grid input,.filter-grid select { width:100%; padding:9px 12px; border:2px solid var(--gray-200); border-radius:var(--radius-xs); font-size:.875rem; outline:none; transition:var(--transition); font-family:inherit; }
  .filter-grid input:focus,.filter-grid select:focus { border-color:var(--blue-mid); box-shadow:0 0 0 3px rgba(0,80,179,.12); }
  .report-type-tabs { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
  .rtype-btn { padding:9px 18px; border:2px solid var(--gray-200); border-radius:20px; font-size:.82rem; font-weight:600; cursor:pointer; background:white; color:var(--gray-600); transition:var(--transition); display:flex; align-items:center; gap:6px; }
  .rtype-btn:hover { border-color:var(--blue-mid); color:var(--blue-mid); }
  .rtype-btn.active { background:var(--blue-dark); color:white; border-color:var(--blue-dark); }
  .section-panel { background:white; border-radius:var(--radius); border:1px solid var(--gray-200); box-shadow:var(--shadow-sm); overflow:hidden; margin-bottom:24px; }
  .section-panel-header { background:var(--blue-dark); color:white; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; }
  .section-panel-header h3 { font-size:.9rem; font-weight:700; display:flex; align-items:center; gap:8px; }
  .rec-badge { background:var(--yellow); color:var(--blue-dark); font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:12px; }
  .summary-stat-row { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:24px; }
  .sstat { background:white; border-radius:var(--radius-sm); padding:16px; text-align:center; box-shadow:var(--shadow-sm); border:1px solid var(--gray-200); }
  .sstat .n { font-size:1.7rem; font-weight:800; color:var(--blue-dark); line-height:1; }
  .sstat .l { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:var(--gray-500); margin-top:4px; }
  .med-status-row { display:flex; align-items:center; justify-content:space-between; padding:11px 18px; border-bottom:1px solid var(--gray-100); }
  .med-status-row:last-child { border-bottom:none; }
  .med-status-row:hover { background:var(--gray-50); }
</style>
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>🖨️ Print Records <span class="admin-badge">ADMIN</span></h3>
        <p>Generate and print comprehensive clinic records</p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <button onclick="window.print()" class="btn btn-print no-print">🖨️ Print This Page</button>
        <a href="dashboard.php" class="btn btn-outline btn-sm no-print">← Back</a>
      </div>
    </div>
    <div class="page-content">
      <div class="breadcrumb no-print">🏠 <a href="dashboard.php">Admin Dashboard</a> / <span>Print Records</span></div>

      <!-- Filter Panel -->
      <div class="filter-panel no-print">
        <h3>⚙️ Filter & Report Options</h3>
        <form method="GET">
          <div class="filter-grid">
            <div>
              <label>Date From</label>
              <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div>
              <label>Date To</label>
              <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div>
              <label>Gender</label>
              <select name="gender">
                <option value="">All Genders</option>
                <option value="Male"   <?= $gender==='Male'?'selected':'' ?>>Male</option>
                <option value="Female" <?= $gender==='Female'?'selected':'' ?>>Female</option>
                <option value="Other"  <?= $gender==='Other'?'selected':'' ?>>Other</option>
              </select>
            </div>
            <div>
              <label>Course</label>
              <select name="course">
                <option value="">All Courses</option>
                <?php $courses_q->data_seek(0); while($c=$courses_q->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($c['course']) ?>" <?= $course===$c['course']?'selected':'' ?>><?= htmlspecialchars($c['course']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div>
              <input type="hidden" name="report_type" id="report_type_input" value="<?= htmlspecialchars($report_type) ?>">
              <button type="submit" class="btn btn-primary" style="width:100%">🔄 Apply Filters</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Report Type Selector -->
      <div class="report-type-tabs no-print">
        <button class="rtype-btn <?= $report_type==='all_records'?'active':'' ?>" onclick="switchReport('all_records')">📋 All Records</button>
        <button class="rtype-btn <?= $report_type==='patients'?'active':'' ?>" onclick="switchReport('patients')">🧑‍⚕️ Patient List</button>
        <button class="rtype-btn <?= $report_type==='medical_records'?'active':'' ?>" onclick="switchReport('medical_records')">🏥 Medical Visits</button>
        <button class="rtype-btn <?= $report_type==='medicines'?'active':'' ?>" onclick="switchReport('medicines')">💊 Medicine Inventory</button>
        <button class="rtype-btn <?= $report_type==='users'?'active':'' ?>" onclick="switchReport('users')">👥 System Users</button>
      </div>

      <!-- ===== PRINTABLE DOCUMENT ===== -->
      <div id="print-document">

        <!-- Document Header -->
        <div class="print-doc" style="background:white;border-radius:var(--radius);box-shadow:var(--shadow-sm);border:1px solid var(--gray-200);">
          <div class="print-doc-header">
            <div class="print-doc-logo">🏥</div>
            <div class="print-doc-school">
              <h1>ST. JOHN PAUL II COLLEGE OF DAVAO</h1>
              <p>School Clinic Management System</p>
              <p style="margin-top:4px;color:var(--gray-500)">Davao City, Philippines</p>
            </div>
            <div class="print-doc-meta">
              <strong>CLINIC RECORDS REPORT</strong>
              <span>Period: <?= $period_label ?></span><br>
              <span>Generated: <?= $generated_at ?></span><br>
              <span>Generated by: <b><?= htmlspecialchars($_SESSION['full_name']) ?></b></span>
              <?php if($gender): ?><br><span>Gender Filter: <?= htmlspecialchars($gender) ?></span><?php endif; ?>
              <?php if($course): ?><br><span>Course: <?= htmlspecialchars($course) ?></span><?php endif; ?>
            </div>
          </div>

          <!-- Summary Overview (always shown) -->
          <div class="print-section" id="section-summary">
            <div class="print-section-title">📊 SYSTEM OVERVIEW</div>
            <div class="print-summary-boxes">
              <div class="print-summary-box">
                <div class="num"><?= $total_patients ?></div>
                <div class="lbl">Active Patients</div>
              </div>
              <div class="print-summary-box">
                <div class="num"><?= $total_records ?></div>
                <div class="lbl">Records in Period</div>
              </div>
              <div class="print-summary-box">
                <div class="num"><?= $total_archived ?></div>
                <div class="lbl">Archived Patients</div>
              </div>
              <div class="print-summary-box">
                <div class="num"><?= $total_users ?></div>
                <div class="lbl">System Users</div>
              </div>
            </div>
            <div class="print-summary-boxes">
              <div class="print-summary-box">
                <div class="num"><?= $male_count ?></div>
                <div class="lbl">Male Patients</div>
              </div>
              <div class="print-summary-box">
                <div class="num"><?= $female_count ?></div>
                <div class="lbl">Female Patients</div>
              </div>
              <div class="print-summary-box">
                <div class="num"><?= $total_medicines ?></div>
                <div class="lbl">Medicine Types</div>
              </div>
              <div class="print-summary-box" style="border-color:<?= $med_issues>0?'var(--danger)':'var(--gray-200)' ?>">
                <div class="num" style="color:<?= $med_issues>0?'var(--danger)':'var(--blue-dark)' ?>"><?= $med_issues ?></div>
                <div class="lbl">Medicine Issues</div>
              </div>
            </div>
          </div>

          <!-- PATIENT LIST -->
          <div class="print-section" id="section-patients" <?= !in_array($report_type,['patients','all_records'])?'style="display:none"':'' ?>>
            <div class="print-section-title">🧑‍⚕️ PATIENT LIST
              <?php $patients->data_seek(0); $pc=0; while($patients->fetch_assoc()) $pc++; ?>
              <span style="margin-left:8px;font-size:.75rem;font-weight:400">(<?= $pc ?> patients)</span>
            </div>
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Blood Type</th>
                    <th>Contact</th>
                    <th>Visits (Period)</th>
                    <th>Last Visit</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $patients->data_seek(0); $i=1; while($p=$patients->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td style="font-family:monospace;font-weight:700;color:var(--blue-mid)"><?= htmlspecialchars($p['student_id']) ?></td>
                    <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
                    <td><?= $p['age'] ?></td>
                    <td><span class="badge badge-<?= $p['gender'] ?>"><?= $p['gender'] ?></span></td>
                    <td><?= htmlspecialchars($p['course']??'—') ?></td>
                    <td><?= htmlspecialchars($p['year_level']??'—') ?></td>
                    <td style="font-weight:700;color:var(--danger)"><?= $p['blood_type']??'?' ?></td>
                    <td><?= htmlspecialchars($p['contact_number']??'—') ?></td>
                    <td style="text-align:center"><strong><?= $p['visit_count'] ?></strong></td>
                    <td><?= $p['last_visit'] ? date('M d, Y',strtotime($p['last_visit'])) : '—' ?></td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if($pc===0): ?><tr class="table-empty"><td colspan="11">No patients found for selected filters.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- MEDICAL RECORDS -->
          <div class="print-section" id="section-records" <?= !in_array($report_type,['medical_records','all_records'])?'style="display:none"':'' ?>>
            <div class="print-section-title">📋 MEDICAL VISIT RECORDS
              <?php $records->data_seek(0); $rc=0; while($records->fetch_assoc()) $rc++; ?>
              <span style="margin-left:8px;font-size:.75rem;font-weight:400">(<?= $rc ?> records • <?= $period_label ?>)</span>
            </div>
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Visit Date</th>
                    <th>Patient</th>
                    <th>Student ID</th>
                    <th>Course/Year</th>
                    <th>Chief Complaint</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Medicines Given</th>
                    <th>BP</th>
                    <th>Temp</th>
                    <th>Attended By</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $records->data_seek(0); $i=1; while($r=$records->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td style="white-space:nowrap"><strong><?= date('M d, Y',strtotime($r['visit_date'])) ?></strong></td>
                    <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
                    <td style="font-family:monospace;font-size:.78rem;color:var(--blue-mid)"><?= htmlspecialchars($r['student_id']) ?></td>
                    <td style="font-size:.78rem"><?= htmlspecialchars($r['course']??'—') ?> <?= $r['year_level']?'/'.$r['year_level']:'' ?></td>
                    <td><?= htmlspecialchars($r['chief_complaint']) ?></td>
                    <td><?= htmlspecialchars($r['diagnosis']??'—') ?></td>
                    <td><?= htmlspecialchars($r['treatment']??'—') ?></td>
                    <td style="font-size:.78rem"><?= htmlspecialchars($r['medicines_given']??'—') ?></td>
                    <td style="font-size:.78rem;white-space:nowrap"><?= $r['blood_pressure']??'—' ?></td>
                    <td style="font-size:.78rem;white-space:nowrap"><?= $r['temperature']?$r['temperature'].'°C':'—' ?></td>
                    <td style="font-size:.78rem"><?= htmlspecialchars($r['nurse_name']) ?></td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if($rc===0): ?><tr class="table-empty"><td colspan="12">No medical records found for selected period/filters.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TOP COMPLAINTS (shown in all_records + medical_records) -->
          <?php if(in_array($report_type,['all_records','medical_records'])): ?>
          <div class="print-section" style="display:grid;grid-template-columns:1fr;gap:24px">
            <div>
              <div class="print-section-title">🤒 TOP CHIEF COMPLAINTS</div>
              <table>
                <thead><tr><th>#</th><th>Complaint</th><th style="text-align:center">Cases</th></tr></thead>
                <tbody>
                  <?php $i=1; while($c=$top_comp->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($c['chief_complaint']) ?></td>
                    <td style="text-align:center"><span class="badge badge-nurse"><?= $c['c'] ?></span></td>
                  </tr>
                  <?php endwhile; ?>
                  <?php if(!isset($i)||$i===1): ?><tr class="table-empty"><td colspan="3">No data</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <!-- MEDICINE INVENTORY -->
          <div class="print-section" id="section-medicines" <?= !in_array($report_type,['medicines','all_records'])?'style="display:none"':'' ?>>
            <div class="print-section-title">💊 MEDICINE INVENTORY</div>
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Medicine Name</th>
                    <th>Category</th>
                    <th style="text-align:center">Quantity</th>
                    <th>Unit</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i=1; while($m=$medicines->fetch_assoc()): ?>
                  <tr style="<?= $m['status']==='out_of_stock'?'background:#FEF2F2':($m['status']==='low'?'background:#FFFBEB':'') ?>">
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($m['medicine_name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['category']??'—') ?></td>
                    <td style="text-align:center;font-weight:700;font-size:1.05rem;color:<?= $m['quantity']<=5?'var(--danger)':($m['quantity']<=20?'var(--warning)':'var(--success)') ?>"><?= $m['quantity'] ?></td>
                    <td><?= htmlspecialchars($m['unit']) ?></td>
                    <td><?= $m['expiry_date'] ? date('M d, Y',strtotime($m['expiry_date'])) : '—' ?></td>
                    <td><span class="badge badge-<?= $m['status'] ?>"><?= str_replace('_',' ',$m['status']) ?></span></td>
                    <td style="font-size:.78rem;color:var(--gray-600)"><?= htmlspecialchars($m['description']??'') ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- SYSTEM USERS -->
          <div class="print-section" id="section-users" <?= !in_array($report_type,['users','all_records'])?'style="display:none"':'' ?>>
            <div class="print-section-title">👥 SYSTEM USERS</div>
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>School ID</th>
                    <th>Status</th>
                    <th>Date Registered</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i=1; while($u=$users_list->fetch_assoc()): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                    <td style="font-family:monospace"><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td style="font-family:monospace"><?= $u['school_id']??'—' ?></td>
                    <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                    <td><?= date('M d, Y',strtotime($u['created_at'])) ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Document Footer -->
          <div class="print-footer-note">
            <strong>ST. JOHN PAUL II COLLEGE OF DAVAO — School Clinic</strong><br>
            This report is confidential and for official use only. Generated on <?= $generated_at ?> by <?= htmlspecialchars($_SESSION['full_name']) ?>.<br>
            Filter Period: <?= $period_label ?>
            <?php if($gender||$course): ?> | Filters Applied: <?= implode(', ', array_filter([$gender, $course])) ?><?php endif; ?>
          </div>
        </div>
      </div><!-- /#print-document -->
    </div>
  </div>
</div>

<script>
function switchReport(type) {
  document.getElementById('report_type_input').value = type;
  document.querySelectorAll('.rtype-btn').forEach(b => b.classList.remove('active'));
  event.target.closest('.rtype-btn').classList.add('active');

  // Show/hide sections
  const allSections = ['patients','records','medicines','users'];
  allSections.forEach(s => {
    const el = document.getElementById('section-' + s);
    if (el) el.style.display = 'none';
  });

  if (type === 'all_records') {
    allSections.forEach(s => { const el = document.getElementById('section-' + s); if(el) el.style.display=''; });
  } else if (type === 'patients') {
    document.getElementById('section-patients').style.display = '';
  } else if (type === 'medical_records') {
    document.getElementById('section-records').style.display = '';
  } else if (type === 'medicines') {
    document.getElementById('section-medicines').style.display = '';
  } else if (type === 'users') {
    document.getElementById('section-users').style.display = '';
  }
}
</script>
</body>
</html>
