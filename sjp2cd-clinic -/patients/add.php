<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $sid  = trim($_POST['student_id']??'');
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

    if (empty($sid)||empty($name)||empty($gen)||$age<=0) {
        $err = 'Student ID, Name, Age and Gender are required.';
    } else {
        $chk = $conn->prepare("SELECT id FROM patients WHERE student_id=?");
        $chk->bind_param('s',$sid); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $err = 'Student ID already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO patients (student_id,full_name,age,gender,course,year_level,address,contact_number,emergency_contact_name,emergency_contact_number,blood_type,allergies) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssisssssssss',$sid,$name,$age,$gen,$crs,$yr,$addr,$cont,$ecn,$ecp,$bt,$allg);
            if ($stmt->execute()) {
                header('Location: list.php?success=Patient+registered+successfully!');
                exit();
            } else { $err = 'Failed to save. Try again.'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Add Patient - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>➕ Register New Patient</h3>
        <p>Add a new student/patient to the clinic system</p>
      </div>
      <div class="topbar-right">
        <a href="list.php" class="btn btn-outline btn-sm">← Back to List</a>
      </div>
    </div>
    <div class="page-content">
      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <a href="list.php">Patients</a> / <span>Add New</span></div>

      <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h3>🧑‍⚕️ Patient Information Form</h3>
        </div>
        <form method="POST">
        <div class="card-body">
          <h4 style="color:var(--blue-dark);margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid var(--yellow)">📋 Basic Information</h4>
          <div class="form-row">
            <div class="form-group">
              <label>Student ID *</label>
              <input type="text" name="student_id" placeholder="e.g. 2023-0001" value="<?= htmlspecialchars($_POST['student_id']??'') ?>" required>
            </div>
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="full_name" placeholder="Last, First Middle" value="<?= htmlspecialchars($_POST['full_name']??'') ?>" required>
            </div>
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label>Age *</label>
              <input type="number" name="age" min="1" max="100" value="<?= htmlspecialchars($_POST['age']??'') ?>" required>
            </div>
            <div class="form-group">
              <label>Gender *</label>
              <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="Male" <?= ($_POST['gender']??'')==='Male'?'selected':'' ?>>Male</option>
                <option value="Female" <?= ($_POST['gender']??'')==='Female'?'selected':'' ?>>Female</option>
              </select>
            </div>
            <div class="form-group">
              <label>Blood Type</label>
              <select name="blood_type">
                <option value="">Unknown</option>
                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
                <option value="<?= $bt ?>" <?= ($_POST['blood_type']??'')===$bt?'selected':'' ?>><?= $bt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Course / Program</label>
              <input type="text" name="course" placeholder="e.g. BS Nursing" value="<?= htmlspecialchars($_POST['course']??'') ?>">
            </div>
            <div class="form-group">
              <label>Year Level</label>
              <select name="year_level">
                <option value="">Select Year</option>
                <?php foreach(['1st Year','2nd Year','3rd Year','4th Year',] as $y): ?>
                <option value="<?= $y ?>" <?= ($_POST['year_level']??'')===$y?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <h4 style="color:var(--blue-dark);margin:20px 0 16px;padding-bottom:8px;border-bottom:2px solid var(--yellow)">📞 Contact Information</h4>
          <div class="form-row">
            <div class="form-group">
              <label>Contact Number</label>
              <input type="text" name="contact_number" placeholder="e.g. 09XXXXXXXXX" value="<?= htmlspecialchars($_POST['contact_number']??'') ?>">
            </div>
            <div class="form-group">
              <label>Home Address</label>
              <input type="text" name="address" placeholder="Complete address" value="<?= htmlspecialchars($_POST['address']??'') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Emergency Contact Name</label>
              <input type="text" name="emergency_contact_name" placeholder="Guardian name" value="<?= htmlspecialchars($_POST['emergency_contact_name']??'') ?>">
            </div>
            <div class="form-group">
              <label>Emergency Contact Number</label>
              <input type="text" name="emergency_contact_number" placeholder="09XXXXXXXXX" value="<?= htmlspecialchars($_POST['emergency_contact_number']??'') ?>">
            </div>
          </div>

          <h4 style="color:var(--blue-dark);margin:20px 0 16px;padding-bottom:8px;border-bottom:2px solid var(--yellow)">🏥 Medical Information</h4>
          <div class="form-group">
            <label>Known Allergies</label>
            <textarea name="allergies" placeholder="List any known allergies (medications, food, etc.) or write 'None'"><?= htmlspecialchars($_POST['allergies']??'') ?></textarea>
          </div>
        </div>
        <div class="card-footer" style="display:flex;gap:12px">
          <button type="submit" class="btn btn-primary">💾 Register Patient</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
