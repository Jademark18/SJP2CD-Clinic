<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

$id = (int)($_GET['id']??0);
$patient = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
if (!$patient) { header('Location: list.php?error=Patient+not+found'); exit(); }

// Add medical record
$rec_err = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_record'])) {
    $vd  = $_POST['visit_date']??date('Y-m-d');
    $cc  = trim($_POST['chief_complaint']??'');
    $dx  = trim($_POST['diagnosis']??'');
    $tx  = trim($_POST['treatment']??'');
    $med = trim($_POST['medicines_given']??'');
    $bp  = trim($_POST['blood_pressure']??'');
    $tmp = trim($_POST['temperature']??'');
    $pr  = trim($_POST['pulse_rate']??'');
    $wt  = trim($_POST['weight']??'');
    $rem = trim($_POST['remarks']??'');
    $uid = $_SESSION['user_id'];

    if (empty($cc)) { $rec_err = 'Chief complaint is required.'; }
    else {
        $stmt2 = $conn->prepare("INSERT INTO medical_records (patient_id,visit_date,chief_complaint,diagnosis,treatment,medicines_given,blood_pressure,temperature,pulse_rate,weight,remarks,attended_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt2->bind_param('issssssssssi',$id,$vd,$cc,$dx,$tx,$med,$bp,$tmp,$pr,$wt,$rem,$uid);
        if ($stmt2->execute()) {
            header("Location: view.php?id=$id&success=Record+added+successfully");
            exit();
        } else { $rec_err = 'Failed to add record.'; }
    }
}

$records = $conn->query("SELECT mr.*, u.full_name as nurse_name FROM medical_records mr JOIN users u ON u.id=mr.attended_by WHERE mr.patient_id=$id ORDER BY mr.visit_date DESC");
$record_count = $records->num_rows;

// Build print records link — for admin go to print_records, for nurse go to records
$print_href = ($_SESSION['role']==='admin')
    ? '../admin/print_records.php?report_type=medical_records'
    : 'records.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($patient['full_name']) ?> — Patient Record</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>🧑‍⚕️ <?= htmlspecialchars($patient['full_name']) ?></h3>
        <p>Student ID: <?= htmlspecialchars($patient['student_id']) ?> &nbsp;·&nbsp; <?= $record_count ?> visit record<?= $record_count!==1?'s':'' ?></p>
      </div>
      <div class="topbar-right">
        <a href="edit.php?id=<?= $id ?>" class="btn btn-yellow btn-sm">✏️ Edit</a>
        <?php if($_SESSION['role']==='admin'): ?>
          <a href="../admin/print_records.php?report_type=all_records" class="btn btn-print btn-sm">🖨️ Print All Records</a>
        <?php endif; ?>
        <a href="list.php" class="btn btn-outline btn-sm">← Back to List</a>
      </div>
    </div>

    <div class="page-content">
      <?php if(isset($_GET['success'])): ?><div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
      <?php if($rec_err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($rec_err) ?></div><?php endif; ?>

      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <a href="list.php">Patients</a> / <span><?= htmlspecialchars($patient['full_name']) ?></span></div>

      <!-- Patient Profile -->
      <div class="card" style="margin-bottom:24px">
        <div class="card-header card-header-yellow">
          <h3>📋 Patient Profile</h3>
          <span class="badge badge-<?= $patient['status'] ?>"><?= $patient['status'] ?></span>
        </div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-item"><label>Student ID</label><p style="font-family:monospace;font-weight:700;color:var(--blue-mid)"><?= htmlspecialchars($patient['student_id']) ?></p></div>
            <div class="detail-item"><label>Full Name</label><p><strong><?= htmlspecialchars($patient['full_name']) ?></strong></p></div>
            <div class="detail-item"><label>Age</label><p><?= $patient['age'] ?> years old</p></div>
            <div class="detail-item"><label>Gender</label><p><span class="badge badge-<?= $patient['gender'] ?>"><?= $patient['gender'] ?></span></p></div>
            <div class="detail-item"><label>Course</label><p><?= htmlspecialchars($patient['course']??'N/A') ?></p></div>
            <div class="detail-item"><label>Year Level</label><p><?= htmlspecialchars($patient['year_level']??'N/A') ?></p></div>
            <div class="detail-item"><label>Blood Type</label><p><strong style="color:var(--danger);font-size:1rem"><?= $patient['blood_type']??'Unknown' ?></strong></p></div>
            <div class="detail-item"><label>Contact Number</label><p><?= htmlspecialchars($patient['contact_number']??'N/A') ?></p></div>
            <div class="detail-item"><label>Registered</label><p><?= date('M d, Y', strtotime($patient['created_at'])) ?></p></div>
            <div class="detail-item full"><label>Address</label><p><?= htmlspecialchars($patient['address']??'N/A') ?></p></div>
            <div class="detail-item"><label>Emergency Contact</label><p><?= htmlspecialchars($patient['emergency_contact_name']??'N/A') ?></p></div>
            <div class="detail-item"><label>Emergency Number</label><p><?= htmlspecialchars($patient['emergency_contact_number']??'N/A') ?></p></div>
            <div class="detail-item full"><label>Known Allergies</label>
              <p style="color:var(--danger);font-weight:600">
                <?= $patient['allergies'] && $patient['allergies']!=='None' ? '⚠️ ' : '' ?><?= htmlspecialchars($patient['allergies']??'None') ?>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Medical Visit History -->
      <div class="card">
        <div class="card-header">
          <h3>📋 Medical Visit History <span style="font-size:.78rem;font-weight:400;opacity:.75">(<?= $record_count ?> record<?= $record_count!==1?'s':'' ?>)</span></h3>
          <div style="display:flex;gap:8px;align-items:center">
            <?php if($_SESSION['role']==='admin'): ?>
              <a href="../admin/print_records.php?report_type=all_records" class="btn btn-print btn-sm">🖨️ Print All Records</a>
            <?php endif; ?>
            <button onclick="document.getElementById('addRecordModal').classList.add('open')" class="btn btn-yellow btn-sm">➕ Add Visit Record</button>
          </div>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Visit Date</th>
                <th>Chief Complaint</th>
                <th>Diagnosis</th>
                <th>Treatment</th>
                <th>Medicines Given</th>
                <th>Vitals</th>
                <th>Attended By</th>
              </tr>
            </thead>
            <tbody>
              <?php if($records->num_rows===0): ?>
              <tr class="table-empty">
                <td colspan="7">
                  <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No medical records yet</h3>
                    <p><button onclick="document.getElementById('addRecordModal').classList.add('open')" style="background:none;border:none;color:var(--blue-mid);cursor:pointer;font-weight:700;font-size:.875rem">Add the first visit record →</button></p>
                  </div>
                </td>
              </tr>
              <?php else: while($r=$records->fetch_assoc()): ?>
              <tr>
                <td style="white-space:nowrap"><strong><?= date('M d, Y',strtotime($r['visit_date'])) ?></strong></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['chief_complaint']) ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['diagnosis']??'—') ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['treatment']??'—') ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['medicines_given']??'—') ?></td>
                <td style="font-size:.78rem;line-height:1.8">
                  <?php if($r['blood_pressure']||$r['temperature']||$r['pulse_rate']||$r['weight']): ?>
                    <?php if($r['blood_pressure']): ?><span style="background:#EFF6FF;color:#1D4ED8;padding:1px 6px;border-radius:4px;margin:1px 0;display:inline-block">BP: <?= $r['blood_pressure'] ?></span><br><?php endif; ?>
                    <?php if($r['temperature']): ?><span style="background:#FEF3C7;color:#92400E;padding:1px 6px;border-radius:4px;margin:1px 0;display:inline-block">🌡 <?= $r['temperature'] ?>°C</span><br><?php endif; ?>
                    <?php if($r['pulse_rate']): ?><span style="background:#F0FDF4;color:#166534;padding:1px 6px;border-radius:4px;margin:1px 0;display:inline-block">PR: <?= $r['pulse_rate'] ?>bpm</span><br><?php endif; ?>
                    <?php if($r['weight']): ?><span style="background:#F5F3FF;color:#5B21B6;padding:1px 6px;border-radius:4px;margin:1px 0;display:inline-block">Wt: <?= $r['weight'] ?>kg</span><?php endif; ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:.82rem"><?= htmlspecialchars($r['nurse_name']) ?></td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
        <?php if($_SESSION['role']==='admin' && $record_count > 0): ?>
        <div class="card-footer">
          <a href="../admin/print_records.php?report_type=all_records" class="btn btn-print btn-sm">🖨️ Print All Clinic Records</a>
          <span style="font-size:.78rem;color:var(--gray-500)">Opens the full printable records page</span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Add Medical Record Modal -->
<div class="modal-overlay" id="addRecordModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>➕ Add Medical Visit Record — <?= htmlspecialchars($patient['full_name']) ?></h3>
      <button class="modal-close" onclick="document.getElementById('addRecordModal').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label>Visit Date *</label>
          <input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Chief Complaint *</label>
        <textarea name="chief_complaint" placeholder="Patient's main complaint or reason for visit..." required></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Diagnosis</label>
          <input type="text" name="diagnosis" placeholder="Medical diagnosis">
        </div>
        <div class="form-group">
          <label>Treatment Given</label>
          <input type="text" name="treatment" placeholder="Treatment or intervention provided">
        </div>
      </div>
      <div class="form-group">
        <label>Medicines Given</label>
        <input type="text" name="medicines_given" placeholder="e.g. Paracetamol 500mg x2 tabs, Ibuprofen 200mg x1 tab">
      </div>

      <div class="section-title">📊 Vital Signs</div>
      <div class="form-row four">
        <div class="form-group">
          <label>Blood Pressure</label>
          <input type="text" name="blood_pressure" placeholder="e.g. 120/80">
        </div>
        <div class="form-group">
          <label>Temperature (°C)</label>
          <input type="text" name="temperature" placeholder="e.g. 37.0">
        </div>
        <div class="form-group">
          <label>Pulse Rate (bpm)</label>
          <input type="text" name="pulse_rate" placeholder="e.g. 72">
        </div>
        <div class="form-group">
          <label>Weight (kg)</label>
          <input type="text" name="weight" placeholder="e.g. 55">
        </div>
      </div>
      <div class="form-group">
        <label>Remarks / Notes</label>
        <textarea name="remarks" placeholder="Additional notes, follow-up instructions, referrals..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="document.getElementById('addRecordModal').classList.remove('open')">Cancel</button>
      <button type="submit" name="add_record" class="btn btn-primary">💾 Save Visit Record</button>
    </div>
    </form>
  </div>
</div>
</body>
</html>
