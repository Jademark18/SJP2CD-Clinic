<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

// Delete medicine (admin only)
if (isset($_GET['delete']) && $_SESSION['role']==='admin') {
    $mid = (int)$_GET['delete'];
    $conn->query("DELETE FROM medicines WHERE id=$mid");
    header('Location: list.php?success=Medicine+deleted');
    exit();
}

$search = $_GET['search']??'';
$status = $_GET['status']??'';
$q = "SELECT * FROM medicines WHERE 1=1";
if ($search) $q .= " AND medicine_name LIKE '%".mysqli_real_escape_string($conn,$search)."%'";
if ($status) $q .= " AND status='".mysqli_real_escape_string($conn,$status)."'";
$q .= " ORDER BY medicine_name";
$meds = $conn->query($q);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medicines - SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>💊 Medicine Inventory</h3>
        <p>Track and manage clinic medicine stock</p>
      </div>
      <div class="topbar-right">
        <a href="add.php" class="btn btn-yellow">➕ Add Medicine</a>
      </div>
    </div>
    <div class="page-content">
      <?php if(isset($_GET['success'])): ?><div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
      <div class="breadcrumb">🏠 <a href="../dashboard.php">Dashboard</a> / <span>Medicines</span></div>

      <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search medicine name..." value="<?= htmlspecialchars($search) ?>">
        <select name="status">
          <option value="">All Status</option>
          <option value="available" <?= $status==='available'?'selected':'' ?>>Available</option>
          <option value="low" <?= $status==='low'?'selected':'' ?>>Low Stock</option>
          <option value="out_of_stock" <?= $status==='out_of_stock'?'selected':'' ?>>Out of Stock</option>
          <option value="expired" <?= $status==='expired'?'selected':'' ?>>Expired</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="list.php" class="btn btn-outline">Reset</a>
      </form>

      <div class="card">
        <div class="card-header">
          <h3>💊 Medicine Stock List</h3>
          <span style="color:rgba(255,255,255,.7);font-size:.85rem"><?= $meds->num_rows ?> item(s)</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>#</th><th>Medicine Name</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Expiry Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if($meds->num_rows===0): ?>
              <tr class="table-empty"><td colspan="8">No medicines found. <a href="add.php">Add one →</a></td></tr>
              <?php else: $i=1; while($m=$meds->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($m['medicine_name']) ?></strong><?php if($m['description']): ?><br><small style="color:#8A94A6"><?= htmlspecialchars(substr($m['description'],0,40)) ?></small><?php endif; ?></td>
                <td><?= htmlspecialchars($m['category']??'-') ?></td>
                <td>
                  <span style="font-size:1.1rem;font-weight:800;color:<?= $m['quantity']<=5?'var(--danger)':($m['quantity']<=20?'var(--warning)':'var(--success)') ?>"><?= $m['quantity'] ?></span>
                </td>
                <td><?= htmlspecialchars($m['unit']) ?></td>
                <td><?= $m['expiry_date']?date('M d, Y',strtotime($m['expiry_date'])):'-' ?><?php if($m['expiry_date'] && strtotime($m['expiry_date'])<time()): ?> <span style="color:var(--danger);font-size:.75rem">(EXPIRED)</span><?php endif; ?></td>
                <td><span class="badge badge-<?= $m['status'] ?>"><?= str_replace('_',' ',$m['status']) ?></span></td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="edit.php?id=<?= $m['id'] ?>" class="btn btn-yellow btn-sm">✏️ Edit</a>
                    <?php if($_SESSION['role']==='admin'): ?>
                    <a href="?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this medicine?')">🗑️</a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
