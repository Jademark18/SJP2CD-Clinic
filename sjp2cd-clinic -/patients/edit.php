<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

$id = (int)($_GET['id']??0);
$patient = $conn->query("SELECT * FROM patients WHERE id=$id")->fetch_assoc();
if (!$patient) { header('Location: list.php?error=Patient+not+found'); exit(); }
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['full_name']??'');
    $age  = (int)($_POST['age']??0);
    $gen  = $_POST['gender']??'';
    $crs  = trim($_POST['course']??'');
    $yr   = $_POST['year_level']??'';
    $addr = trim($_POST['address']??'');
    $cont = trim($_POST['contact_number']??'');
    $ecn  = trim($_POST['emergency_contact_name']??'');
    $ecp  = trim($_POST['emergency_contact_number']??'');
    $bt   = $_POST['blood_type']??'';
    $allg = trim($_POST['allergies']??'');

    if (empty($name)||empty($gen)||$age<=0) { $err='Name, Age and Gender required.'; }
    else {
        $stmt = $conn->prepare("UPDATE patients SET full_name=?,age=?,gender=?,course=?,year_level=?,address=?,contact_number=?,emergency_contact_name=?,emergency_contact_number=?,blood_type=?,allergies=? WHERE id=?");
        $stmt->bind_param('sisssssssssi',$name,$age,$gen,$crs,$yr,$addr,$cont,$ecn,$ecp,$bt,$allg,$id);
        if ($stmt->execute()) {
            header("Location: view.php?id=$id&success=Patient+updated+successfully");
            exit();
        } else { $err='Update failed.'; }
    }
}
$p = $patient;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Patient - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>✏️ Edit Patient Record</h3>
        <p><?= htmlspecialchars($p['full_name']) ?> — <?= htmlspecialchars($p['student_id']) ?></p>
      </div>
      <div class="topbar-right">
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline btn-sm">← Back to Profile</a>
      </div>
    </div>
    <div class="page-content">
      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <a href="list.php">Patients</a> / <a href="view.php?id=<?= $id ?>"><?= htmlspecialchars($p['full_name']) ?></a> / <span>Edit</span></div>
      <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
      <div class="card">
        <div class="card-header card-header-yellow">
          <h3>✏️ Edit Patient Information</h3>
        </div>
        <form method="POST">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label>Student ID (cannot change)</label>
              <input type="text" value="<?= htmlspecialchars($p['student_id']) ?>" disabled style="background:#f5f7fa;color:#8A94A6">
            </div>
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="full_name" value="<?= htmlspecialchars($p['full_name']) ?>" required>
            </div>
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>Age *</label>
              <input type="number" name="age" value="<?= $p['age'] ?>" min="1" max="100" required>
            </div>
            <div class="form-group">
              <label>Gender *</label>
              <select name="gender" required>
                <?php foreach(['Male','Female','Other'] as $g): ?>
                <option value="<?= $g ?>" <?= $p['gender']===$g?'selected':'' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Blood Type</label>
              <select name="blood_type">
                <option value="">Unknown</option>
                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                <option value="<?= $bt ?>" <?= $p['blood_type']===$bt?'selected':'' ?>><?= $bt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Course</label>
              <input type="text" name="course" value="<?= htmlspecialchars($p['course']??'') ?>">
            </div>
            <div class="form-group">
              <label>Year Level</label>
              <select name="year_level">
                <option value="">Select</option>
                <?php foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year','Graduate'] as $y): ?>
                <option value="<?= $y ?>" <?= $p['year_level']===$y?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Contact Number</label>
              <input type="text" name="contact_number" value="<?= htmlspecialchars($p['contact_number']??'') ?>">
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" name="address" value="<?= htmlspecialchars($p['address']??'') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Emergency Contact Name</label>
              <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($p['emergency_contact_name']??'') ?>">
            </div>
            <div class="form-group">
              <label>Emergency Contact Number</label>
              <input type="text" name="emergency_contact_number" value="<?= htmlspecialchars($p['emergency_contact_number']??'') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Known Allergies</label>
            <textarea name="allergies"><?= htmlspecialchars($p['allergies']??'') ?></textarea>
          </div>
        </div>
        <div class="card-footer" style="display:flex;gap:12px">
          <button type="submit" class="btn btn-primary">💾 Save Changes</button>
          <a href="view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
