<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls');
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'your_email@gmail.com');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'your_app_password_here');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'SJPIICD Clinic System');
if (!defined('SMTP_REPLY_TO')) define('SMTP_REPLY_TO', 'your_email@gmail.com');
if (!defined('SMTP_ADMIN_EMAIL')) define('SMTP_ADMIN_EMAIL', 'admin@yourdomain.com');

function create_smtp_mail() {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

    return $mail;
}

function send_generic_email($toEmail, $toName, $subject, $messageBody, $replyTo = null, $cc = []) {
    $toEmail = trim((string) $toEmail);
    $subject = trim((string) $subject);
    $messageBody = (string) $messageBody;

    if ($toEmail === '' || $subject === '') {
        return false;
    }

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = create_smtp_mail();
            if (!$mail) {
                return false;
            }

            $mail->addAddress($toEmail, $toName);
            if ($replyTo) {
                $mail->addReplyTo($replyTo, SMTP_FROM_NAME);
            }

            foreach ($cc as $email) {
                if (!empty($email)) {
                    $mail->addCC($email);
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $messageBody;
            $mail->AltBody = strip_tags($messageBody);

            return $mail->send();
        } catch (Exception $e) {
            error_log('SMTP email failed: ' . $e->getMessage());
            return false;
        }
    }

    $headers = [];
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . ($replyTo ?: SMTP_REPLY_TO);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';

    return @mail($toEmail, $subject, $messageBody, implode("\r\n", $headers));
}

function send_user_notification($userEmail, $userName, $subject, $messageBody) {
    return send_generic_email($userEmail, $userName, $subject, $messageBody, SMTP_REPLY_TO);
}

function send_admin_notification($subject, $messageBody, $adminEmail = SMTP_ADMIN_EMAIL) {
    return send_generic_email($adminEmail, 'Administrator', $subject, $messageBody, SMTP_REPLY_TO);
}

function send_welcome_email_to_user($userName, $userEmail) {
    $subject = 'Welcome to SJPIICD Clinic System';
    $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2>Welcome, {$userName}!</h2>
            <p>Your account has been created successfully.</p>
            <p>You can now log in and use the clinic system.</p>
            <p>Thank you for registering.</p>
        </body>
        </html>
    ";

    return send_user_notification($userEmail, $userName, $subject, $body);
}

function send_new_user_admin_alert($userName, $userEmail) {
    $subject = 'New user registration alert';
    $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2>New user registered</h2>
            <p><strong>Name:</strong> {$userName}</p>
            <p><strong>Email:</strong> {$userEmail}</p>
            <p>Please review the account and activate as needed.</p>
        </body>
        </html>
    ";

    return send_admin_notification($subject, $body);
}
?>
