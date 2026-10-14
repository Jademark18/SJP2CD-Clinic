<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND role='admin' AND status='active'");
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — SJPIICD Clinic</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    :root {
      --blue-dark: #003087;
      --blue-mid: #0050B3;
      --blue-light: #4A90D9;
      --yellow: #E9B700;
      --yellow-light: #F7D06C;
      --gray-200: #E2E8F0;
      --gray-500: #94A3B8;
      --gray-700: #475569;
      --white: #FFFFFF;
      --danger: #DC2626;
      --danger-bg: #FEF2F2;
    }

    body {
      margin: 0;
      font-family: 'Poppins', system-ui, sans-serif;
      background: linear-gradient(135deg, #0b1c4a 0%, #1c3a7d 45%, #4d79c1 100%);
      color: #fff;
      background-image: url('../images/sjpiicd_cover.webp');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: relative;
      overflow-x: hidden;
    }
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(3, 37, 103, 0.75);
      mix-blend-mode: multiply;
      pointer-events: none;
    }

    .admin-login-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      position: relative;
      z-index: 1;
    }
    .admin-login-box {
      position: relative;
      z-index: 1;
      background: rgba(255, 255, 255, 0.95);
      border: none;
      border-radius: 30px;
      padding: 2.2rem;
      width: 100%;
      max-width: 430px;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.18);
      text-align: center;
    }
    .admin-login-box .lock-icon { font-size: 3rem; margin-bottom: 10px; }
    .admin-login-box h2 { font-size: 1.8rem; font-weight: 800; color: #0f1f4d; margin-bottom: 8px; }
    .admin-login-box p { color: #4d5b7f; font-size: .88rem; margin-bottom: 24px; }
    .admin-login-box .form-group { text-align: left; margin-bottom: 16px; }
    .admin-login-box label { font-size: .95rem; font-weight: 600; color: #4d5b7f; display: block; margin-bottom: 6px; }
    .admin-login-box input {
      width: 100%; padding: 14px 16px; border: 1px solid #dbe2ef;
      border-radius: 14px; font-size: .95rem; font-family: 'Poppins', sans-serif;
      box-sizing: border-box; transition: border .2s;
      background: #fff;
      color: #0f172a;
    }
    .admin-login-box input:focus { border-color: var(--blue-mid); outline: none; box-shadow: 0 0 0 3px rgba(0,80,179,0.12); }
    .admin-login-box .btn-admin {
      width: 100%; padding: 14px; background: linear-gradient(135deg, #003087, #0050B3); color: #fff;
      border: none; border-radius: 14px; font-size: 1rem; font-weight: 700;
      cursor: pointer; margin-top: 8px; font-family: 'Poppins', sans-serif;
      transition: background .2s, transform .2s, box-shadow .2s;
      box-shadow: 0 4px 12px rgba(0, 48, 135, 0.2);
    }
    .admin-login-box .btn-admin:hover {
      background: linear-gradient(135deg, #002a6d, #003d9a);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0, 48, 135, 0.25);
    }
    .admin-login-box .alert-error {
      background: var(--danger-bg); border: 1px solid rgba(220,38,38,.2); color: var(--danger);
      border-radius: 8px; padding: 10px 14px; font-size: .85rem;
      margin-bottom: 16px; text-align: left;
    }
    .chat-widget {
      margin-top: 24px;
      border: 1px solid #e3e8ef;
      border-radius: 18px;
      background: #f8fbff;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.09);
      overflow: hidden;
      text-align: left;
      display: block;
    }
    .chat-widget.hidden {
      display: none;
    }
    .chat-toggle-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 13px;
      margin-top: 20px;
      border-radius: 12px;
      border: 1px solid #003087;
      background: #fff;
      color: #003087;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
    }
    .chat-toggle-btn:hover {
      background: #003087;
      color: #fff;
    }
    .chat-header {
      padding: 18px 20px;
      background: #003087;
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .chat-close-btn {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 1rem;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 8px;
    }
    .chat-close-btn:hover {
      background: rgba(255,255,255,0.15);
    }
    .chat-messages {
      max-height: 300px;
      overflow-y: auto;
      padding: 18px 20px;
      gap: 14px;
      display: flex;
      flex-direction: column;
    }
    .chat-message {
      max-width: 100%;
      border-radius: 16px;
      padding: 12px 14px;
      line-height: 1.5;
      font-size: 0.92rem;
    }
    .chat-message.user {
      align-self: flex-end;
      background: #003087;
      color: #fff;
    }
    .chat-message.bot {
      align-self: flex-start;
      background: #ffffff;
      border: 1px solid #dbe6f5;
      color: #1f305e;
      position: relative;
    }
    .message-close-btn {
      position: absolute;
      top: 6px;
      right: 6px;
      background: none;
      border: none;
      color: #999;
      cursor: pointer;
      font-size: 16px;
      padding: 2px 4px;
      line-height: 1;
      border-radius: 4px;
      transition: all 0.2s;
    }
    .message-close-btn:hover {
      color: #d32f2f;
      background: #ffe6e6;
    }
    .chat-input-area {
      border-top: 1px solid #e3e8ef;
      display: flex;
      gap: 10px;
      padding: 14px 18px;
      background: #eff6ff;
    }
    .chat-input-area input {
      flex: 1;
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 0.95rem;
    }
    .chat-input-area button {
      background: #003087;
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 12px 18px;
      cursor: pointer;
      font-weight: 700;
      transition: background .2s;
    }
    .chat-input-area button:hover {
      background: #001f5b;
    }
    .chat-status {
      font-size: 0.78rem;
      color: #64748b;
      padding: 0 20px 16px;
    }
    .back-link {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
      font-size: .82rem;
    }
    .back-link span { display: none; }
    .back-link a {
      flex: 1;
      padding: 11px 10px;
      border: 1px solid #cbd5e1;
      border-radius: 14px;
      color: #003087;
      font-weight: 600;
      text-decoration: none;
      background: rgba(255, 255, 255, 0.7);
      transition: background .2s, color .2s;
    }
    .back-link a:hover {
      background: #003087;
      color: #fff;
    }
  </style>
</head>
<body>
<div class="admin-login-wrap">
  <div class="admin-login-box">
    <div class="lock-icon">🔐</div>
    <h2>Administrator Login</h2>
    <p>Restricted access — authorized personnel only</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
      <div class="form-group">
        <label>Username or Email</label>
        <input type="text" name="username" placeholder="Admin username" required autofocus
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               autocomplete="off" autocapitalize="none" spellcheck="false">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Admin password" required autocomplete="new-password" autocapitalize="none" spellcheck="false">
      </div>
      <button type="submit" class="btn-admin">🔑 &nbsp; Login as Admin</button>
    </form>

    <button id="chatToggle" class="chat-toggle-btn" type="button">💬 Open Clinic Assistant</button>

    <div id="chatWidget" class="chat-widget hidden" aria-label="Clinic assistant chatbot">
      <div class="chat-header">
        <span>SJPIICD Clinic Assistant</span>
        <button id="chatClose" class="chat-close-btn" type="button" aria-label="Close chat">✕</button>
      </div>
      <div id="chatMessages" class="chat-messages"></div>
      <div class="chat-input-area">
        <input id="chatInput" type="text" placeholder="Ask about login, patients, medicines, or reports...">
        <button id="chatSend" type="button">Send</button>
      </div>
    </div>

    <div class="back-link">
      <span> | </span>
      <a href="../index.php">← Back to main portal</a>
    </div>
  </div>
</div>
<script>
  const chatToggle = document.getElementById('chatToggle');
  const chatWidget = document.getElementById('chatWidget');
  const chatMessages = document.getElementById('chatMessages');
  const chatInput = document.getElementById('chatInput');
  const chatSend = document.getElementById('chatSend');
  const chatClose = document.getElementById('chatClose');
  const endpoint = 'http://127.0.0.1:5000/chat';
  let chatInitialized = false;

  const LOCAL_RESPONSES = [
    { keywords: ['hi', 'hello', 'hey', 'greetings'], reply: 'Hello! Ask about admin login, student access, patients, medicines, reports, or clinic location.' },
    { keywords: ['login', 'log in', 'sign in', 'signin', 'password', 'credentials'], reply: 'Use your admin username or email and password on admin/login.php.' },
    { keywords: ['available', 'open', 'close', 'hours', 'time', 'breaktime', 'break', 'weekdays', 'saturday'], reply: 'Clinic hours are Monday to Friday, 8:00 AM to 5:00 PM with lunch break from 12:00 PM to 1:00 PM. Saturday is also open 8:00 AM to 5:00 PM with break time 11:30 AM to 1:00 PM.' },
    { keywords: ['admin', 'administrator'], reply: 'This is the admin login page. Sign in at admin/login.php to manage the dashboard, patients, medicines, and reports.' },
    { keywords: ['student'], reply: 'Students use student/login.php. Admin login does not work there.' },
    { keywords: ['patient', 'patients'], reply: 'Admin dashboard manages patient records and visit details.' },
    { keywords: ['medicine', 'medicines'], reply: 'The medicines section lets admins add, edit, and track inventory.' },
    { keywords: ['report', 'reports', 'dashboard'], reply: 'Reports are available in the admin reports page.' },
    { keywords: ['help', 'support', 'problem', 'issue', 'error'], reply: 'Contact the clinic administrator for access or system support.' },
    { keywords: ['clinic', 'building', 'location', 'located', 'where is located', 'library', 'middle'], reply: 'The clinic is on the 2nd floor of Building C, in the middle section, next to the library.' },
    { keywords: ['system', 'portal', 'site', 'project'], reply: 'This app manages clinic users, patients, medicines, and reports.' }
  ];

  function getLocalReply(message) {
    const normalized = message.trim().toLowerCase();
    if (!normalized) {
      return 'Please type a question about the SJPIICD clinic system or login assistance.';
    }

    for (const item of LOCAL_RESPONSES) {
      if (item.keywords.some(keyword => normalized.includes(keyword))) {
        return item.reply;
      }
    }

    if (normalized.includes('forgot') && normalized.includes('password')) {
      return 'If you forgot your password, please contact the clinic administrator to reset your account. This login page does not provide a password reset flow.';
    }

    return 'I am a clinic chatbot built for the SJPIICD system. Ask me about admin login, student access, patient records, medicines, or reports.';
  }

  function addMessage(text, type) {
    const message = document.createElement('div');
    message.className = `chat-message ${type}`;
    
    if (type === 'bot') {
      // Create a text span for bot messages
      const textSpan = document.createElement('span');
      textSpan.textContent = text;
      message.appendChild(textSpan);
    } else {
      // User messages just get text
      message.textContent = text;
    }
    
    chatMessages.appendChild(message);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function setStatus(text) {
  }

  async function sendChat(message) {
    addMessage(message, 'user');
    chatInput.value = '';
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
      });
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      const data = await response.json();
      const reply = data.reply || getLocalReply(message);
      addMessage(reply, 'bot');
    } catch (error) {
      addMessage(getLocalReply(message), 'bot');
      console.error('Chatbot error:', error);
    }
  }

  function clearChatHistory() {
    chatMessages.innerHTML = '';
    chatInitialized = false;
    chatInput.value = '';
  }

  function initChat() {
    if (!chatInitialized) {
      addMessage('Welcome to the SJPIICD Assistant. Ask me about the clinic system, admin login, or student access.', 'bot');
      chatInitialized = true;
    }
  }

  chatToggle.addEventListener('click', () => {
    const isHidden = chatWidget.classList.toggle('hidden');
    if (isHidden) {
      chatToggle.textContent = '💬 Open Clinic Assistant';
      clearChatHistory();
    } else {
      chatToggle.textContent = '💬 Close Clinic Assistant';
      initChat();
    }
  });

  chatClose.addEventListener('click', () => {
    chatWidget.classList.add('hidden');
    chatToggle.textContent = '💬 Open Clinic Assistant';
    clearChatHistory();
  });

  chatSend.addEventListener('click', () => {
    const text = chatInput.value.trim();
    if (text) sendChat(text);
  });

  chatInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      const text = chatInput.value.trim();
      if (text) sendChat(text);
    }
  });
</script>
</body>
</html>
