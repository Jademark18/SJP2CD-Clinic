<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
$id = (int)($_GET['id']??0);
$med = $conn->query("SELECT * FROM medicines WHERE id=$id")->fetch_assoc();
if (!$med) { header('Location: list.php?error=Medicine+not+found'); exit(); }
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = trim($_POST['medicine_name']??'');
    $cat  = trim($_POST['category']??'');
    $qty  = (int)($_POST['quantity']??0);
    $unit = trim($_POST['unit']??'pcs');
    $exp  = $_POST['expiry_date']??'';
    $desc = trim($_POST['description']??'');
    $status = 'available';
    if ($qty === 0) $status = 'out_of_stock';
    elseif ($qty <= 10) $status = 'low';
    if ($exp && strtotime($exp) < time()) $status = 'expired';

    $stmt = $conn->prepare("UPDATE medicines SET medicine_name=?,category=?,quantity=?,unit=?,expiry_date=?,description=?,status=? WHERE id=?");
    $expval = $exp ?: null;
    $stmt->bind_param('ssissssi',$name,$cat,$qty,$unit,$expval,$desc,$status,$id);
    if ($stmt->execute()) {
        header('Location: list.php?success=Medicine+updated');
        exit();
    } else { $err='Update failed.'; }
}
$m = $med;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Medicine - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title"><h3>✏️ Edit Medicine</h3><p><?= htmlspecialchars($m['medicine_name']) ?></p></div>
      <div class="topbar-right"><a href="list.php" class="btn btn-outline btn-sm">← Back</a></div>
    </div>
    <div class="page-content">
      <?php if($err): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
      <div class="card" style="max-width:700px">
        <div class="card-header card-header-yellow"><h3>💊 Edit Medicine Info</h3></div>
        <form method="POST">
        <div class="card-body">
          <div class="form-group"><label>Medicine Name *</label><input type="text" name="medicine_name" value="<?= htmlspecialchars($m['medicine_name']) ?>" required></div>
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select name="category">
                <option value="">Select</option>
                <?php foreach(['Analgesic','Antibiotic','Antihistamine','Antiviral','NSAID','GI Medicine','Electrolyte','Antiseptic','Supplement','Cardiovascular','Respiratory','Other'] as $c): ?>
                <option value="<?= $c ?>" <?= $m['category']===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Unit</label>
              <select name="unit">
                <?php foreach(['tablets','capsules','bottles','sachets','ampules','vials','pcs','ml'] as $u): ?>
                <option value="<?= $u ?>" <?= $m['unit']===$u?'selected':'' ?>><?= $u ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Quantity</label><input type="number" name="quantity" min="0" value="<?= $m['quantity'] ?>"></div>
            <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" value="<?= $m['expiry_date']??'' ?>"></div>
          </div>
          <div class="form-group"><label>Description</label><textarea name="description"><?= htmlspecialchars($m['description']??'') ?></textarea></div>
        </div>
        <div class="card-footer" style="display:flex;gap:12px">
          <button type="submit" class="btn btn-primary">💾 Save Changes</button>
          <a href="list.php" class="btn btn-outline">Cancel</a>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
