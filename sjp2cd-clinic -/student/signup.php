<?php
session_start();
if (isset($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit();
}
require_once '../config/db.php';
require_once '../config/smtp.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = trim($_POST['student_number'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($student_number) || empty($full_name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^\d{8}$/', $student_number)) {
        $error = 'Student number must be exactly 8 digits.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if student number or email already exists
        $chk = $conn->prepare("SELECT id FROM student_accounts WHERE student_number=? OR email=?");
        $chk->bind_param('ss', $student_number, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'Student number or email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO student_accounts (student_number, full_name, email, password, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param('ssss', $student_number, $full_name, $email, $hash);
            if ($stmt->execute()) {
                send_welcome_email_to_user($full_name, $email);
                send_new_user_admin_alert($full_name, $email);
                $success = 'Account created successfully! Please log in with your credentials.';
                echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Admission - SJPIICD Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body.student-signup-body {
            min-height: 100vh;
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

        body.student-signup-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .signup-container {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px 0;
        }

        .signup-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            color: #333;
        }

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0b1c4a;
            margin-bottom: 10px;
        }

        .signup-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #0b1c4a;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4d79c1;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(77, 121, 193, 0.1);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 12px 15px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0b1c4a 0%, #1c3a7d 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(11, 28, 74, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #4d79c1;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .signup-navigation {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .signup-navigation a {
            flex: 1;
            padding: 11px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.7);
            color: #003087;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: background .2s, color .2s;
        }

        .signup-navigation a:hover {
            background: #003087;
            color: #fff;
        }

        .small-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body class="student-signup-body">
    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <h1>🏥 Student Admission</h1>
                <p>Create your clinic account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="student_number">Student Number *</label>
                    <input 
                        type="text" 
                        id="student_number"
                        name="student_number" 
                        placeholder="e.g., 20231001" 
                        maxlength="8" 
                        pattern="\d{8}" 
                        inputmode="numeric"
                        value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>" 
                        required>
                    <div class="small-text">Must be 8 digits</div>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input 
                        type="text" 
                        id="full_name"
                        name="full_name" 
                        placeholder="Enter your full name" 
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" 
                        required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        placeholder="your@email.com" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        placeholder="Enter password" 
                        required>
                    <div class="small-text">Minimum 6 characters</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input 
                        type="password" 
                        id="confirm_password"
                        name="confirm_password" 
                        placeholder="Confirm password" 
                        required>
                </div>

                <button type="submit" class="btn-submit">Create Account</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>

            <div class="signup-navigation">
                <a href="../index.php">Main Portal</a>
            </div>
        </div>
    </div>
</body>
</html>
