<?php
session_start();
require_once '../includes/auth_admin.php';
require_once '../config/db.php';
require_once '../config/notifications.php';

if (isset($_GET['mark_notifications'])) {
    mark_notifications_read('admin', (int) $_SESSION['user_id']);
    header('Location: dashboard.php');
    exit();
}

$total_patients = $conn->query("SELECT COUNT(*) c FROM patients WHERE status='active'")->fetch_assoc()['c'];
$total_archived = $conn->query("SELECT COUNT(*) c FROM patients WHERE status='archived'")->fetch_assoc()['c'];
$total_users    = $conn->query("SELECT COUNT(*) c FROM users WHERE status='active'")->fetch_assoc()['c'];
$total_records  = $conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'];
$today_visits   = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE visit_date=CURDATE()")->fetch_assoc()['c'];
$total_meds     = $conn->query("SELECT COUNT(*) c FROM medicines")->fetch_assoc()['c'];
$low_meds       = $conn->query("SELECT COUNT(*) c FROM medicines WHERE status IN ('low','out_of_stock','expired')")->fetch_assoc()['c'];
$this_month     = $conn->query("SELECT COUNT(*) c FROM medical_records WHERE MONTH(visit_date)=MONTH(CURDATE()) AND YEAR(visit_date)=YEAR(CURDATE())")->fetch_assoc()['c'];

// Monthly visits
$monthly = $conn->query("SELECT DATE_FORMAT(visit_date,'%b %Y') as month, COUNT(*) as cnt FROM medical_records WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY YEAR(visit_date), MONTH(visit_date) ORDER BY visit_date");
$months=[]; $counts=[];
while($row=$monthly->fetch_assoc()){$months[]=$row['month'];$counts[]=$row['cnt'];}

// Recent users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 6");


// Recent records
$recent = $conn->query("SELECT mr.*, p.full_name, p.student_id FROM medical_records mr JOIN patients p ON p.id=mr.patient_id ORDER BY mr.created_at DESC LIMIT 6");

// Low meds
$low_med_list = $conn->query("SELECT * FROM medicines WHERE status IN ('low','out_of_stock','expired') ORDER BY FIELD(status,'out_of_stock','expired','low') LIMIT 6");
$adminNotifications = get_notifications('admin', (int) $_SESSION['user_id'], 8);
$adminUnread = count_unread_notifications('admin', (int) $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard — SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
.notification-wrap { position: relative; }
.notification-bell {
  position: relative; display: inline-flex; align-items: center; justify-content: center;
  width: 42px; height: 42px; border-radius: 50%; background: rgba(0,48,135,0.08);
  border: 1px solid rgba(0,48,135,0.15); color: #003087; font-size: 1.1rem;
  text-decoration: none; cursor: pointer;
}
.notification-bell .badge-count {
  position: absolute; top: -4px; right: -4px; background: #f43f5e; color: white;
  border-radius: 999px; font-size: 10px; padding: 3px 6px; font-weight: 700;
}
.notification-panel {
  position: absolute; right: 0; top: calc(100% + 12px); width: 320px;
  background: white; color: #1f2937; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.18);
  border: 1px solid #e5e7eb; overflow: hidden; z-index: 30;
}
.notification-panel.hidden { display: none; }
.notification-header {
  display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 700;
}
.notification-list { max-height: 320px; overflow-y: auto; }
.notification-item {
  padding: 12px 16px; border-bottom: 1px solid #edf2f7; display: block; text-decoration: none; color: inherit;
}
.notification-item.unread { background: #eff6ff; }
.notification-item .title { font-weight: 700; font-size: 0.83rem; display: block; }
.notification-item .message { font-size: 0.75rem; color: #475569; display: block; margin-top: 3px; }
.notification-item .time { font-size: 0.68rem; color: #64748b; display: block; margin-top: 6px; }
.notification-empty { padding: 18px 16px; color: #64748b; font-size: 0.8rem; }
</style>
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h3>📊 <?= $_SESSION['role'] === 'admin' ? 'Admin Dashboard' : 'Nurse Dashboard' ?> <span class="admin-badge"><?= strtoupper($_SESSION['role']) ?></span></h3>
        <p>ST. JOHN PAUL II COLLEGE OF DAVAO — <?= $_SESSION['role'] === 'admin' ? 'Full System Overview' : 'Patient Care Overview' ?></p>
      </div>
      <div class="topbar-right">
        <div class="date-badge">📅 <?= date('F d, Y') ?></div>
        <div class="notification-wrap">
          <button class="notification-bell" type="button" aria-label="Notifications" onclick="document.getElementById('adminNotifications').classList.toggle('hidden')">
            🔔
            <?php if ($adminUnread > 0): ?><span class="badge-count"><?= $adminUnread ?></span><?php endif; ?>
          </button>
          <div id="adminNotifications" class="notification-panel hidden">
            <div class="notification-header">
              <span>Notifications</span>
              <a href="dashboard.php?mark_notifications=1" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:0.7rem">Mark all read</a>
            </div>
            <div class="notification-list">
              <?php if (empty($adminNotifications)): ?>
                <div class="notification-empty">No notifications yet.</div>
              <?php else: foreach ($adminNotifications as $note): ?>
                <a href="<?= htmlspecialchars($note['link'] ?: '#') ?>" class="notification-item <?= $note['status'] === 'unread' ? 'unread' : '' ?>">
                  <span class="title"><?= htmlspecialchars($note['title']) ?></span>
                  <span class="message"><?= htmlspecialchars($note['message']) ?></span>
                  <span class="time"><?= date('M d, Y h:i A', strtotime($note['created_at'])) ?></span>
                </a>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>
        <?php if($_SESSION['role'] === 'admin'): ?>
        <a href="print_records.php" class="btn btn-print">🖨️ Print Records</a>
        <?php endif; ?>
        <a href="../logout.php" class="btn btn-danger btn-sm">🚪 Sign Out</a>
      </div>
    </div>

    <div class="page-content">
      <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
      <?php endif; ?>
NURSING STAFF STATUS SECTION:
<div class="card shadow-sm" id="nursing-status">

    <div class="card-header bg-success text-white">
        Nursing Staff Status
    </div>

    <div class="card-body">

        <table class="table table-bordered">

            <thead>
                <tr>
                    <th>Nursing Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" class="form-control" placeholder="Enter nursing name" style="min-height:38px;border:1px solid #ddd;border-radius:4px;padding:6px 8px;"></td>
                    <td><input type="text" class="form-control" placeholder="e.g., Available" style="min-height:38px;border:1px solid #ddd;border-radius:4px;padding:6px 8px;"></td>
                    
            </tbody>

        </table>

    </div>

</div>

      <!-- Stats Row 1 -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon">🧑‍⚕️</div>
          <div class="stat-info"><p>Active Patients</p><h3><?= $total_patients ?></h3><small><a href="../patients/list.php">Manage →</a></small></div>
        </div>
        <div class="stat-card yellow">
          <div class="stat-icon">📋</div>
          <div class="stat-info"><p>Total Records</p><h3><?= $total_records ?></h3><small>This month: <strong><?= $this_month ?></strong></small></div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon">👥</div>
          <div class="stat-info"><p>System Users</p><h3><?= $total_users ?></h3><small><a href="users.php">Manage →</a></small></div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon">💊</div>
          <div class="stat-info"><p>Medicine Issues</p><h3><?= $low_meds ?></h3><small><a href="../medicines/list.php">Check →</a></small></div>
        </div>
      </div>

      <!-- Stats Row 2 -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px">
        <div class="stat-card blue" style="border-left-color:var(--blue-light)">
          <div class="stat-icon" style="background:rgba(74,144,217,.1)">📦</div>
          <div class="stat-info"><p>Medicine Types</p><h3><?= $total_meds ?></h3></div>
        </div>
        <div class="stat-card gray">
          <div class="stat-icon">🗃️</div>
          <div class="stat-info"><p>Archived</p><h3><?= $total_archived ?></h3><small><a href="../archive/index.php">View →</a></small></div>
        </div>
        <div class="stat-card yellow" style="border-left-color:var(--success)">
          <div class="stat-icon" style="background:var(--success-bg)">📅</div>
          <div class="stat-info"><p>Today's Visits</p><h3><?= $today_visits ?></h3><small><?= date('M d, Y') ?></small></div>
        </div>
        <div class="stat-card blue" style="border-left-color:#6366F1">
          <div class="stat-icon" style="background:#EEF2FF">📈</div>
          <div class="stat-info"><p>This Month</p><h3><?= $this_month ?></h3><small><?= date('F Y') ?></small></div>
        </div>
      </div>

      <!-- Charts -->
      <div class="dashboard-grid" style="margin-bottom:24px; grid-template-columns: 1fr">
        <div class="card">
          <div class="card-header">
            <h3>📈 Monthly Visit Trend (Last 6 Months)</h3>
            <a href="reports.php" class="btn btn-yellow btn-sm">Full Report</a>
          </div>
          <div class="card-body">
            <canvas id="visitChart" height="110"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Records + Low Meds -->
      <div class="dashboard-grid" style="margin-bottom:24px">
        <div class="card">
          <div class="card-header">
            <h3>📋 Recent Medical Visits</h3>
            <a href="../patients/records.php" class="btn btn-yellow btn-sm">View All</a>
          </div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Patient</th><th>Visit Date</th><th>Complaint</th></tr></thead>
              <tbody>
                <?php while($r=$recent->fetch_assoc()): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                    <small style="color:var(--gray-500);font-family:monospace"><?= htmlspecialchars($r['student_id']) ?></small>
                  </td>
                  <td><?= date('M d, Y',strtotime($r['visit_date'])) ?></td>
                  <td style="font-size:.82rem"><?= htmlspecialchars(substr($r['chief_complaint'],0,45)) ?>…</td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header card-header-yellow">
            <h3>⚠️ Medicine Alerts</h3>
            <a href="../medicines/list.php" class="btn btn-primary btn-sm">Manage</a>
          </div>
          <div style="padding:0">
            <?php if ($low_med_list->num_rows===0): ?>
              <div style="padding:40px;text-align:center;color:var(--gray-500)">✅ All medicines well stocked</div>
            <?php else: while($m=$low_med_list->fetch_assoc()): ?>
            <div style="padding:12px 18px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
              <div>
                <strong style="font-size:.85rem"><?= htmlspecialchars($m['medicine_name']) ?></strong><br>
                <small style="color:var(--gray-500)">Qty: <?= $m['quantity'] ?> <?= $m['unit'] ?></small>
              </div>
              <span class="badge badge-<?= $m['status'] ?>"><?= str_replace('_',' ',$m['status']) ?></span>
            </div>
            <?php endwhile; endif; ?>
          </div>
          <div class="card-footer">
            <a href="../medicines/add.php" class="btn btn-yellow btn-sm">➕ Add Medicine</a>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="card" style="margin-bottom:24px">
        <div class="card-header">
          <h3>👥 Recent System Users</h3>
          <a href="users.php" class="btn btn-yellow btn-sm">Manage All</a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th></tr></thead>
            <tbody>
              <?php while($u=$users->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                <td style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($u['username']) ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                <td style="font-size:.82rem"><?= date('M d, Y',strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-header">
          <h3>⚡ Quick Actions</h3>
        </div>
        <div class="card-body">
          <div class="quick-actions">
            <a href="../patients/add.php" class="btn btn-yellow">➕ New Patient</a>
            <a href="../patients/records.php" class="btn btn-primary">📋 Medical Records</a>
            <a href="../medicines/add.php" class="btn btn-success">💊 Add Medicine</a>
            <a href="reports.php" class="btn btn-outline">📈 Monthly Report</a>
            <a href="print_records.php" class="btn btn-print">🖨️ Print All Records</a>
            <a href="../archive/index.php" class="btn btn-outline">🗃️ Archive</a>
            <a href="users.php" class="btn btn-outline">👥 Manage Users</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
const ctx = document.getElementById('visitChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Visits',
      data: <?= json_encode($counts) ?>,
      backgroundColor: 'rgba(0,48,135,0.85)',
      borderColor: '#FFC300',
      borderWidth: 2,
      borderRadius: 8,
      hoverBackgroundColor: 'rgba(0,80,179,0.9)'
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ctx.raw + ' visit' + (ctx.raw!==1?'s':'') } }
    },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
      x: { grid: { display: false } }
    }
  }
});
</script>
</body>
</html>
