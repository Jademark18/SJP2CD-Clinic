<?php
session_start();

$error   = '';
$success = '';

if (isset($_GET['msg']))     $error   = htmlspecialchars($_GET['msg']);
if (isset($_GET['success'])) $success = htmlspecialchars($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SJPIICD Clinic System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
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
      max-height: 260px;
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
    .chat-input-area button:hover { background: #001f5b; }

    body {
      min-height: 100vh;
      margin: 0;
      background: linear-gradient(135deg, #0b1c4a 0%, #1c3a7d 45%, #4d79c1 100%);
      background-image: url('images/sjpiicd_cover.webp');
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
    }
    .auth-wrapper {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      background: transparent;
    }
    .auth-left { display: none; }
    .auth-right {
      width: min(100%, 430px);
      min-height: 0;
      padding: 2.2rem;
      border-radius: 30px;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.18);
      box-sizing: border-box;
    }
    .auth-right h2 { color: #0f1f4d; font-weight: 800; }
    .auth-right .sub { color: #4d5b7f; }
  </style>
</head>
<body>

<div class="auth-wrapper">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="school-logo">🏥</div>
      <h1>ST. JOHN PAUL II<br>COLLEGE OF DAVAO</h1>
      <h2>School Clinic Management System</h2>
      <div class="clinic-badge">🏥 &nbsp; Clinic Portal</div>
      <p class="auth-left-tagline">
        Providing quality healthcare<br>
        to our academic community
      </p>
    </div>
  </div>

  <div class="auth-right">
    <div class="brand-top">
      <div class="brand-icon">🏫</div>
      <div class="brand-name">
        SJPIICD Clinic System
        <span>Student & Admin Access Portal</span>
      </div>
    </div>

    <h2>Choose Your Portal</h2>
    <p class="sub">Admin and student accounts use separate login pages</p>

    <?php if ($error): ?>
      <div class="alert alert-error" style="color: #b91c1c; background: #fee2e2; padding: 12px; border-radius: 8px; margin-bottom: 15px;">⚠️ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success" style="color: #15803d; background: #dcfce7; padding: 12px; border-radius: 8px; margin-bottom: 15px;">✅ <?= $success ?></div>
    <?php endif; ?>

    <a href="admin/login.php" class="btn btn-primary btn-full" style="display:block; width:100%; background:#003087; color:#fff; padding:14px; border:none; border-radius:14px; font-weight:700; text-align:center; text-decoration:none;">
      🔐 &nbsp; Admin Login
    </a>

    <a href="student/login.php" class="btn btn-outline btn-full" style="display:block; width:100%; margin-top:12px; border:1px solid #cbd5e1; padding:12px; border-radius:14px; color:#1f305e; text-align:center; text-decoration:none; font-weight:600;">
      🎓 &nbsp; Student Login
    </a>

    <a href="student/signup.php" class="btn btn-outline btn-full" style="display:block; margin-top:12px; text-align:center; border:1px solid #cbd5e1; padding:12px; border-radius:14px; color:#1f305e; text-decoration:none; font-weight:500;">
      📝 &nbsp; Create Student Account
    </a>

    <button id="chatToggle" class="chat-toggle-btn" type="button">💬 Open Clinic Assistant</button>

    <div id="chatWidget" class="chat-widget hidden">
      <div class="chat-header">
        <span>SJPIICD Clinic Assistant</span>
        <button id="chatClose" class="chat-close-btn" type="button" aria-label="Close chat">✕</button>
      </div>
      <div id="chatMessages" class="chat-messages"></div>
      <div class="chat-input-area">
        <input id="chatInput" type="text" placeholder="Ask about student login, clinic records, or portal help...">
        <button id="chatSend" type="button">Send</button>
      </div>
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

  // Fully restored your custom backup logic here for regular students & freshmen
  const LOCAL_RESPONSES = [
    { keywords: ['hi', 'hello', 'hey', 'greetings'], reply: 'Hello! Ask about login, patients, medicines, reports, or clinic location.' },
    { keywords: ['login', 'log in', 'sign in', 'signin', 'password', 'credentials'], reply: 'Use the correct login page and enter your credentials accurately.' },
    { keywords: ['admin', 'administrator'], reply: 'Admins should sign in at admin/login.php to access the dashboard, patient records, medicines, and reports.' },
    { keywords: ['available', 'open', 'close', 'hours', 'time', 'breaktime', 'break', 'weekdays', 'saturday'], reply: 'Clinic hours are Monday to Friday, 8:00 AM to 5:00 PM with lunch break from 12:00 PM to 1:00 PM. Saturday is also open 8:00 AM to 5:00 PM with break time 11:30 AM to 1:00 PM.' },
    { keywords: ['student', 'freshman', 'freshmen'], reply: 'Students and freshmen should sign in at student/login.php with their student number and password to access clinic records.' },
    { keywords: ['patient', 'patients'], reply: 'Patient records are managed in the admin dashboard, not on this page.' },
    { keywords: ['medicine', 'medicines'], reply: 'Medicine information is managed by admins in the medicines section.' },
    { keywords: ['report', 'reports', 'dashboard'], reply: 'Reports are shown in the admin dashboard after login.' },
    { keywords: ['help', 'support', 'problem', 'issue', 'error'], reply: 'Contact the clinic administrator for login or system support.' },
    { keywords: ['clinic', 'building', 'location', 'located', 'where is located', 'library', 'middle'], reply: 'The clinic is on the 2nd floor of Building C, in the middle section, next to the library.' },
    { keywords: ['system', 'portal', 'site', 'project'], reply: 'This portal handles student and clinic records.' }
  ];

  function getLocalReply(message) {
    const normalized = message.trim().toLowerCase();
    if (!normalized) {
      return 'Please type a question about the SJPIICD clinic system or login help.';
    }

    for (const item of LOCAL_RESPONSES) {
      if (item.keywords.some(keyword => normalized.includes(keyword))) {
        return item.reply;
      }
    }

    if (normalized.includes('forgot') && normalized.includes('password')) {
      return 'If you forgot your password, please contact the clinic administrator to reset your account.';
    }

    return 'I am a clinic chatbot built for the SJPIICD system. Ask me about student login, clinic records, or portal help.';
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

  async function sendChat(message) {
    addMessage(message, 'user');
    chatInput.value = '';
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
      });
      const data = await response.json();
      if (data && data.reply) {
         addMessage(data.reply, 'bot');
      } else {
         addMessage(getLocalReply(message), 'bot');
      }
    } catch (error) {
      addMessage(getLocalReply(message), 'bot');
    }
  }

  function clearChatHistory() {
    chatMessages.innerHTML = '';
    chatInitialized = false;
    chatInput.value = '';
  }

  function initChat() {
    if (!chatInitialized) {
      addMessage('Welcome to the SJPIICD Assistant. Ask me about the clinic system or login help.', 'bot');
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