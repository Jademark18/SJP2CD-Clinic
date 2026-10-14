<?php
$current  = basename($_SERVER['PHP_SELF']);
$in_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$in_sub   = strpos($_SERVER['PHP_SELF'], '/patients/') !== false
          || strpos($_SERVER['PHP_SELF'], '/medicines/') !== false
          || strpos($_SERVER['PHP_SELF'], '/archive/') !== false;
$prefix = $in_admin || $in_sub ? '../' : '';
$role = $_SESSION['role'] ?? 'nurse';

// Helper to detect active nav
function navActive($path) {
  return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}
?>
<aside class="sidebar <?= $role==='admin'?'admin-sidebar':'' ?>">
  <div class="sidebar-logo">
    <div class="logo-icon">🏥</div>
    <div class="logo-text">
      <strong>SJPIICD Clinic</strong>
      <span>Management System</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <?php if ($role==='admin'): ?>
    <a href="<?= $prefix ?>admin/dashboard.php" class="nav-item <?= $current==='dashboard.php'&&$in_admin?'active':'' ?>">
      <span class="nav-icon">📊</span><span>Admin Dashboard</span>
    </a>
    <a href="<?= $prefix ?>admin/users.php" class="nav-item <?= $current==='users.php'&&$in_admin?'active':'' ?>">
      <span class="nav-icon">👥</span><span>Manage Users</span>
    </a>
    <?php else: ?>
    <a href="<?= $prefix ?>dashboard.php" class="nav-item <?= $current==='dashboard.php'&&!$in_admin?'active':'' ?>">
      <span class="nav-icon">🏠</span><span>Dashboard</span>
    </a>
    <?php endif; ?>

    <div class="nav-section-label">Clinic</div>
    <a href="<?= $prefix ?>patients/list.php" class="nav-item <?= $current==='list.php'&&navActive('/patients/')?'active':'' ?>">
      <span class="nav-icon">🧑‍⚕️</span><span>Patients</span>
    </a>
    <a href="<?= $prefix ?>patients/add.php" class="nav-item <?= $current==='add.php'&&navActive('/patients/')?'active':'' ?>">
      <span class="nav-icon">➕</span><span>Add Patient</span>
    </a>
    <a href="<?= $prefix ?>medicines/list.php" class="nav-item <?= navActive('/medicines/')?'active':'' ?>">
      <span class="nav-icon">💊</span><span>Medicines</span>
    </a>

    <div class="nav-section-label">Records</div>
    <a href="<?= $prefix ?>patients/records.php" class="nav-item <?= $current==='records.php'&&navActive('/patients/')?'active':'' ?>">
      <span class="nav-icon">📋</span><span>Medical Records</span>
    </a>
    <a href="<?= $prefix ?>archive/index.php" class="nav-item <?= navActive('/archive/')?'active':'' ?>">
      <span class="nav-icon">🗃️</span><span>Archive</span>
    </a>

    <?php if ($role==='admin'): ?>
    <div class="nav-section-label">Admin Tools</div>
    <a href="<?= $prefix ?>admin/reports.php" class="nav-item <?= $current==='reports.php'&&$in_admin?'active':'' ?>">
      <span class="nav-icon">📈</span><span>Monthly Reports</span>
    </a>
    <a href="<?= $prefix ?>admin/print_records.php" class="nav-item <?= $current==='print_records.php'&&$in_admin?'active':'' ?>" style="<?= $current==='print_records.php'&&$in_admin?'':''; ?>">
      <span class="nav-icon">🖨️</span><span>Print Records</span>
    </a>
    <a href="<?= $prefix ?>admin/settings.php" class="nav-item <?= $current==='settings.php'&&$in_admin?'active':'' ?>">
      <span class="nav-icon">⚙️</span><span>Settings</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
      <div class="user-info">
        <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
        <span><?= $role === 'admin' ? '⚙️ Administrator' : '🩺 Nurse' ?></span>
      </div>
      <a href="<?= $prefix ?>logout.php" class="logout-btn" title="Sign Out">🚪</a>
    </div>
  </div>
</aside>
