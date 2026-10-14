<?php
session_start();
include('../config/db.php');
include('../config/notifications.php');

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['mark_notifications'])) {
    mark_notifications_read('student', (int) $_SESSION['student_id']);
    header('Location: dashboard.php');
    exit();
}

$studentAccountId = (int) $_SESSION['student_id'];
$studentAccountQuery = mysqli_query($conn, "SELECT full_name, student_number, patient_id FROM student_accounts WHERE id=$studentAccountId AND status='active' LIMIT 1");
$studentAccount = mysqli_fetch_assoc($studentAccountQuery);
if (!$studentAccount) {
  session_destroy();
  header('Location: login.php');
  exit();
}

$student_id = (int) ($studentAccount['patient_id'] ?? 0);
$studentQuery = $student_id > 0
  ? mysqli_query($conn, "SELECT * FROM patients WHERE id=$student_id LIMIT 1")
  : false;
$student = $studentQuery ? mysqli_fetch_assoc($studentQuery) : null;
$recordsQuery = $student_id > 0
  ? mysqli_query($conn, "SELECT visit_date, diagnosis, created_at FROM medical_records WHERE patient_id=$student_id ORDER BY visit_date DESC, id DESC")
  : false;
$medicineQuery = $student_id > 0
  ? mysqli_query($conn, "SELECT m.medicine_name, mu.used_at FROM medicine_usage mu JOIN medicines m ON m.id=mu.medicine_id WHERE mu.patient_id=$student_id ORDER BY mu.used_at DESC")
  : false;
$nurseQuery = mysqli_query($conn, "SELECT * FROM nurse_availability");
$studentNotifications = get_notifications('student', (int) $_SESSION['student_id'], 8);
$studentUnread = count_unread_notifications('student', (int) $_SESSION['student_id']);

if ($studentUnread > 0 && !isset($_GET['mark_notifications'])) {
    $studentNotifications = array_map(function ($n) {
        $n['status'] = 'unread';
        return $n;
    }, $studentNotifications);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard — SJPIICD Clinic</title>
<link rel="stylesheet" href="../css/style.css">
<style>
    body {
        min-height: 100vh;
        background: linear-gradient(135deg, #07172f 0%, #1c3a7d 45%, #4d7cc6 100%);
        color: #fff;
    }
    .student-dashboard {
        min-height: 100vh;
    }
    .student-topbar {
        background: rgba(255,255,255,0.10);
        box-shadow: 0 18px 40px rgba(0,0,0,0.12);
        border-bottom: 1px solid rgba(255,255,255,0.10);
        color: #ffffff;
    }
    .student-topbar .topbar-title h3,
    .student-topbar .topbar-title p {
        color: #ffffff;
    }
    .student-card {
        border: none;
        border-radius: 24px;
        box-shadow: 0 18px 50px rgba(0,0,0,0.14);
    }
    .student-card .card-header {
        background: #003087;
        color: #ffffff;
        border-bottom: none;
    }
    .student-card .card-body {
        background: #ffffff;
        color: #1f2937;
    }
    .student-card .card-body p {
        color: #4b5563;
    }
    .student-dashboard .page-content {
        padding-top: 24px;
    }
    .table thead {
        background: #f8fafc;
    }
    .student-dashboard .date-badge {
        background: rgba(255,255,255,0.16);
        border-color: rgba(255,255,255,0.20);
        color: #ffffff;
    }
    .student-dashboard .topbar-right {
      gap: 22px;
    }
    .student-dashboard .topbar-right > *,
    .student-dashboard .card-header .btn {
      margin-left: 6px;
      margin-right: 6px;
    }
    .notification-wrap { position: relative; }
    .notification-bell {
      position: relative; display: inline-flex; align-items: center; justify-content: center;
      width: 42px; height: 42px; border-radius: 50%; background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.18); color: white; font-size: 1.1rem;
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
    .student-dashboard .dashboard-grid {
      gap: 28px;
    }
    .student-dashboard .page-content > .student-card {
      margin-bottom: 24px;
    }
    .student-dashboard .page-content > .student-card:last-child {
      margin-bottom: 0;
    }
</style>
</head>
<body>
<div class="student-dashboard">
  <div class="topbar student-topbar">
    <div class="topbar-title">
      <h3>🎓 Student Dashboard</h3>
      <p>Your personal clinic overview</p>
    </div>
    <div class="topbar-right">
      <div class="date-badge">📅 <?= date('F d, Y') ?></div>
      <div class="notification-wrap">
        <button class="notification-bell" type="button" aria-label="Notifications" onclick="document.getElementById('studentNotifications').classList.toggle('hidden')">
          🔔
          <?php if ($studentUnread > 0): ?><span class="badge-count"><?= $studentUnread ?></span><?php endif; ?>
        </button>
        <div id="studentNotifications" class="notification-panel hidden">
          <div class="notification-header">
            <span>Notifications</span>
            <a href="dashboard.php?mark_notifications=1" class="btn btn-sm btn-outline" style="padding:5px 10px;font-size:0.7rem">Mark all read</a>
          </div>
          <div class="notification-list">
            <?php if (empty($studentNotifications)): ?>
              <div class="notification-empty">No notifications yet.</div>
            <?php else: foreach ($studentNotifications as $note): ?>
              <a href="<?= htmlspecialchars($note['link'] ?: '#') ?>" class="notification-item <?= $note['status'] === 'unread' ? 'unread' : '' ?>">
                <span class="title"><?= htmlspecialchars($note['title']) ?></span>
                <span class="message"><?= htmlspecialchars($note['message']) ?></span>
                <span class="time"><?= date('M d, Y h:i A', strtotime($note['created_at'])) ?></span>
              </a>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <a href="../logout.php" class="btn btn-danger btn-sm">🚪 Sign Out</a>
    </div>
  </div>
  <div class="page-content">
    <div class="card student-card mb-4">
      <div class="card-body">
        <h4>Welcome, <?= htmlspecialchars($studentAccount['student_number'] ?? 'Student') ?></h4>
        <p>Here is your clinic record summary and nurse availability.</p>
      </div>

DENTAL SCHEDULE SECTION:

<div class="card shadow-sm mb-4" id="dental-schedule">

    <div class="card-header bg-primary text-white">
        Student Dental Scheduling
    </div>

    <div class="card-body">

        <form method="POST" action="save_dental_schedule.php">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <label>Student Name</label>

                    <input 
                        type="text"
                        name="student_name"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label>Schedule Date</label>

                    <input 
                        type="date"
                        name="schedule_date"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label>Time</label>

                    <input 
                        type="time"
                        name="schedule_time"
                        class="form-control"
                        required
                    >
                </div>

            </div>

            <button type="submit" class="btn btn-primary">
                Save Schedule
            </button>

        </form>

    </div>

</div>

    </div>
    <div class="dashboard-grid mb-4">
      <div class="card student-card">
        <div class="card-header">Medical History</div>
        <div class="card-body">
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr><th>Date</th></tr>
              </thead>
              <tbody>
                <?php if ($recordsQuery && mysqli_num_rows($recordsQuery) > 0): ?>
                  <?php while ($record = mysqli_fetch_assoc($recordsQuery)): ?>
                    <tr>
                      <td><?= htmlspecialchars($record['visit_date']) ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr class="table-empty"><td colspan="1">No records found yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="card student-card">
        <div class="card-header">🕒 Clinic Hours</div>
        <div class="card-body">
          <p>Monday to Friday: 8:00 AM - 5:00 PM</p>
          <p class="mb-0">Saturday: 8:00 AM - 5:00 PM</p>
        </div>
      </div>
    </div>
    <div class="card student-card mb-4">
      <div class="card-header">Medicine History</div>
      <div class="card-body">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>Medicine</th><th>Date</th></tr>
            </thead>
            <tbody>
              <?php if ($medicineQuery && mysqli_num_rows($medicineQuery) > 0): ?>
                <?php while ($medicine = mysqli_fetch_assoc($medicineQuery)): ?>
                  <tr>
                    <td><?= htmlspecialchars($medicine['medicine_name']) ?></td>
                    <td><?= htmlspecialchars($medicine['used_at']) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr class="table-empty"><td colspan="2">No medicine history available.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="card student-card">
      <div class="card-header bg-info text-white">Nurse Availability</div>
      <div class="card-body">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>Nurse</th><th>Date</th><th>Time</th></tr>
            </thead>
            <tbody>
              <?php if(mysqli_num_rows($nurseQuery) > 0): ?>
                <?php while($nurse = mysqli_fetch_assoc($nurseQuery)): ?>
                  <tr>
                    <td><?= htmlspecialchars($nurse['nurse_name']) ?></td>
                    <td><?= htmlspecialchars($nurse['available_date']) ?></td>
                    <td><?= htmlspecialchars($nurse['available_time']) ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr class="table-empty"><td colspan="3">No nurse availability scheduled.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
