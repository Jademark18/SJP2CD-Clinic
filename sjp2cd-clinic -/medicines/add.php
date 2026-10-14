<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['medicine_name']??'');
    $cat  = trim($_POST['category']??'');
    $qty  = (int)($_POST['quantity']??0);
    $unit = trim($_POST['unit']??'pcs');
    $exp  = $_POST['expiry_date']??'';
    $desc = trim($_POST['description']??'');

    // Auto status
    $status = 'available';
    if ($qty === 0) $status = 'out_of_stock';
    elseif ($qty <= 10) $status = 'low';
    if ($exp && strtotime($exp) < time()) $status = 'expired';

    if (empty($name)) { $err = 'Medicine name is required.'; }
    else {
        $stmt = $conn->prepare("INSERT INTO medicines (medicine_name,category,quantity,unit,expiry_date,description,status) VALUES (?,?,?,?,?,?,?)");
        $expval = $exp ?: null;
        $stmt->bind_param('ssissa' . 's', $name,$cat,$qty,$unit,$expval,$desc,$status);
        $stmt->close();
        $s2 = $conn->prepare("INSERT INTO medicines (medicine_name,category,quantity,unit,expiry_date,description,status) VALUES (?,?,?,?,?,?,?)");
        $s2->bind_param('ssissss',$name,$cat,$qty,$unit,$expval,$desc,$status);
        if ($s2->execute()) {
            header('Location: list.php?success=Medicine+added+successfully');
            exit();
        } else { $err = 'Failed to add medicine.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Medicine - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><h3>➕ Add Medicine</h3><p>Add new medicine to inventory</p></div>
      <div class="topbar-right"><a href="list.php" class="btn btn-outline btn-sm">← Back</a></div>
    </div>
    <div class="page-content">
      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <a href="list.php">Medicines</a> / <span>Add</span></div>
      <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
      <div class="card" style="max-width:700px">
        <div class="card-header"><h3>💊 Medicine Information</h3></div>
        <form method="POST">
        <div class="card-body">
          <div class="form-group"><label>Medicine Name *</label><input type="text" name="medicine_name" placeholder="e.g. Paracetamol 500mg" required value="<?= htmlspecialchars($_POST['medicine_name']??'') ?>"></div>
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select name="category">
                <option value="">Select Category</option>
                <?php foreach(['Analgesic','Antibiotic','Antihistamine','Antiviral','NSAID','GI Medicine','Electrolyte','Antiseptic','Supplement','Cardiovascular','Respiratory','Other'] as $c): ?>
                <option value="<?= $c ?>" <?= ($_POST['category']??'')===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Unit</label>
              <select name="unit">
                <?php foreach(['tablets','capsules','bottles','sachets','ampules','vials','pcs','ml'] as $u): ?>
                <option value="<?= $u ?>" <?= ($_POST['unit']??'')===$u?'selected':'' ?>><?= $u ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" min="0" value="<?= htmlspecialchars($_POST['quantity']??'0') ?>" required></div>
            <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" value="<?= htmlspecialchars($_POST['expiry_date']??'') ?>"></div>
          </div>
          <div class="form-group"><label>Description / Notes</label><textarea name="description" placeholder="Optional notes about this medicine"><?= htmlspecialchars($_POST['description']??'') ?></textarea></div>
        </div>
        <div class="card-footer" style="display:flex;gap:12px">
          <button type="submit" class="btn btn-primary">💾 Add Medicine</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
