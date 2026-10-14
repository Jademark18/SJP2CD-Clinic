<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
include('../config/db.php');

$error = '';
if(isset($_POST['login'])){
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM student_accounts WHERE student_number=? AND status='active'");
    $stmt->bind_param('s', $student_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        
        if(password_verify($password, $row['password']) || $password === $row['password']){
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['patient_id'] = $row['patient_id'];
            
            // FIX: If the dashboard file is inside your student folder, stay in this folder!
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid student number or password';
        }
    } else {
        $error = 'Invalid student number or password';
    }
}
?>
  <!DOCTYPE html>
  <html>
  <head>
  <title>Student Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body.student-login-body {
        min-height: 100vh;
        margin: 0;
        font-family: 'Poppins', system-ui, sans-serif;
        background: linear-gradient(135deg, #0b1c4a 0%, #1c3a7d 45%, #4d79c1 100%);
        background-image: url('../images/sjpiicd_cover.webp');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        overflow-x: hidden;
        color: #fff;
      }
      body.student-login-body::before {
        content: '';
        position: fixed;
        inset: 0;
        background: rgba(3, 37, 103, 0.75);
        mix-blend-mode: multiply;
        pointer-events: none;
      }
      .student-login-wrapper {
        position: relative;
        z-index: 1;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
      }
      .student-login-card {
        background: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 30px;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.18);
        overflow: hidden;
      }
      .student-login-card .card-body {
        padding: 2.2rem;
      }
      .student-login-card h3 {
        color: #0f1f4d;
        font-weight: 800;
        margin-bottom: 1.5rem;
      }
      .student-login-card label {
        color: #4d5b7f;
        font-weight: 600;
      }
      .student-login-card .form-control {
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        padding: 14px 16px;
        box-shadow: none;
        background: rgba(255, 255, 255, 0.62);
        color: #0f172a;
      }
      .student-login-card .form-control:focus {
        background: rgba(255, 255, 255, 0.72);
        border-color: #0050B3;
        box-shadow: 0 0 0 3px rgba(0,80,179,0.12);
      }
      .student-login-card .btn-primary {
        border-radius: 14px;
        padding: 12px 1rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0b3b8a, #113f94);
        border: none;
      }
      .student-login-card .btn-primary:hover {
        background: linear-gradient(135deg, #0a2f6d, #0f357f);
      }
      .student-login-card .alert {
        border-radius: 16px;
      }
      .student-login-links {
        display: flex;
        gap: 10px;
        margin-top: 16px;
      }
      .student-login-links a {
        flex: 1;
        padding: 11px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.7);
        color: #003087;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: background .2s, color .2s;
      }
      .student-login-links a:hover {
        background: #003087;
        color: #fff;
      }
      .chat-widget {
        margin-top: 20px;
        border: 1px solid #e3e6f0;
        border-radius: 18px;
        background: #f8fbff;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
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
        padding: 16px 18px;
        background: #003087;
        color: #fff;
        font-weight: 700;
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
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
        padding: 18px;
        max-height: 250px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .chat-message {
        max-width: 100%;
        border-radius: 16px;
        padding: 12px 14px;
        font-size: 0.95rem;
        line-height: 1.5;
      }
      .chat-message.user {
        align-self: flex-end;
        background: #003087;
        color: #fff;
      }
      .chat-message.bot {
        align-self: flex-start;
        background: #fff;
        border: 1px solid #dfe7f2;
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
        display: flex;
        gap: 10px;
        padding: 14px 18px 18px;
        background: #eff6ff;
        border-bottom-left-radius: 18px;
        border-bottom-right-radius: 18px;
      }
      .chat-input-area input {
        flex: 1;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
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
      }
    </style>
  </head>
  <body class="student-login-body">
  <div class="student-login-wrapper">
  <div class="container py-5">
  <div class="row justify-content-center">
  <div class="col-md-4">
  <div class="card shadow student-login-card">
  <div class="card-body">
  <h3 class="mb-4 text-center">Student Login</h3>
  <?php if($error){ ?>
  <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php } ?>
  <form method="POST" autocomplete="off">
  <div class="mb-3">
  <label>Student Number</label>
  <input type="text" name="student_number" class="form-control" autocomplete="off" autocapitalize="none" spellcheck="false" required>
  </div>
  <div class="mb-3">
  <label>Password</label>
  <input type="password" name="password" class="form-control" autocomplete="new-password" required>
  </div>
  <button type="submit" name="login" class="btn btn-primary w-100" style="margin-bottom:12px;">Login</button>
  </form>
  <div class="student-login-links"> 
    <a href="../index.php">Main Portal</a>
  </div>
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

    const LOCAL_RESPONSES = [
      { keywords: ['hi', 'hello', 'hey', 'greetings'], reply: 'Hello! Ask about login, students, patients, medicines, reports, or clinic location.' },
      { keywords: ['login', 'log in', 'sign in', 'signin', 'password', 'credentials'], reply: 'Use your student number and password on the student login page.' },
      { keywords: ['available', 'open', 'close', 'hours', 'time', 'breaktime', 'break', 'weekdays', 'saturday'], reply: 'Clinic hours are Monday to Friday, 8:00 AM to 5:00 PM with lunch break from 12:00 PM to 1:00 PM. Saturday is also open 8:00 AM to 5:00 PM with break time 11:30 AM to 1:00 PM.' },
      { keywords: ['student', 'freshman', 'freshmen'], reply: 'This is the student login page. Use your student number and password to sign in and view your clinic records.' },
      { keywords: ['patient', 'patients'], reply: 'Patient records are stored in the clinic system and accessed by admins.' },
      { keywords: ['medicine', 'medicines'], reply: 'Medicine information is managed by admins in the medicines section.' },
      { keywords: ['report', 'reports', 'dashboard'], reply: 'Reports are available in the admin dashboard after login.' },
      { keywords: ['help', 'support', 'problem', 'issue', 'error'], reply: 'Contact the clinic administrator for support.' },
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
        addMessage(data.reply || 'Sorry, no response from the chatbot.', 'bot');
      } catch (error) {
        addMessage('Unable to reach chatbot server. Start chatbot_server.py and reload the page.', 'bot');
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
  <?php