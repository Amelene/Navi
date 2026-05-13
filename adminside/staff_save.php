<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
require_once '../config/smtp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: staff_add.php');
    exit();
}

function redirectWithError($message) {
    $_SESSION['staff_add_error'] = $message;
    header('Location: staff_add.php');
    exit();
}

try {
    $db = Database::getInstance();

    $system_role   = trim($_POST['system_role'] ?? 'staff');
    $staff_no      = trim($_POST['staff_no'] ?? '');
    $first_name    = trim($_POST['first_name'] ?? '');
    $middle_name   = trim($_POST['middle_name'] ?? ''); // for future use
    $last_name     = trim($_POST['last_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $position_id   = (int)($_POST['position_id'] ?? 0);
    $staff_status  = trim($_POST['staff_status'] ?? 'active');
    $salary        = trim($_POST['salary'] ?? '');
    $date_hired    = trim($_POST['date_hired'] ?? '');

    // Basic validation
    if ($staff_no === '' || $first_name === '' || $last_name === '' || $email === '' || $phone === '' || $position_id <= 0 || $date_hired === '') {
        redirectWithError('Please complete all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithError('Invalid email format.');
    }

    if (!in_array($staff_status, ['active', 'on_leave', 'inactive', 'terminated'], true)) {
        redirectWithError('Invalid staff status value.');
    }

    if ($system_role !== 'staff') {
        $system_role = 'staff';
    }

    // Check duplicates
    $existingStaffNo = $db->fetchOne("SELECT id FROM staff WHERE staff_no = ?", [$staff_no]);
    if ($existingStaffNo) {
        redirectWithError('Employee ID already exists.');
    }

    $existingEmail = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingEmail) {
        redirectWithError('Email already exists in users table.');
    }

    // Generate temporary password
    $tempPassword = 'Temp@' . random_int(100000, 999999);
    $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

    $db->beginTransaction();

    // Create user account
    $db->execute(
        "INSERT INTO users (email, password, role, user_status) VALUES (?, ?, 'staff', 'active')",
        [$email, $passwordHash]
    );
    $authUserId = (int)$db->lastInsertId();

    // Insert staff record
    $db->execute(
        "INSERT INTO staff (
            auth_user_id, staff_no, first_name, last_name, role, user_status, phone,
            department_id, position_id, staff_status, date_hired
        ) VALUES (?, ?, ?, ?, ?, 'active', ?, NULL, ?, ?, ?)",
        [
            $authUserId,
            $staff_no,
            $first_name,
            $last_name,
            $system_role,
            $phone,
            $position_id,
            $staff_status,
            $date_hired
        ]
    );

    // Optional salary handling if column exists in your schema:
    // Not inserted here to avoid schema mismatch crashes.

    $db->commit();

    $loginUrl = (isset($_SERVER['HTTP_HOST']) ? ('http://' . $_SERVER['HTTP_HOST']) : '') . '/php-project/adminside/login.php';
    $fullName = trim($first_name . ' ' . $middle_name . ' ' . $last_name);

    // Send credentials via SMTP (uses native mail() fallback if PHPMailer is unavailable)
    $emailSent = false;
    $emailError = '';

    try {
        $smtp = getSmtpConfig();

        $subject = 'Your Staff Account Credentials';
        $messageHtml = '
            <p>Hello ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your staff account has been created successfully.</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>
            <strong>Temporary Password:</strong> ' . htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') . '<br>
            <strong>Login URL:</strong> <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '</a></p>
            <p>Please log in and change your password immediately.</p>
            <p>Regards,<br>' . htmlspecialchars($smtp['from_name'], ENT_QUOTES, 'UTF-8') . '</p>
        ';
        $messageText = "Hello {$fullName},\n\n"
            . "Your staff account has been created successfully.\n\n"
            . "Email: {$email}\n"
            . "Temporary Password: {$tempPassword}\n"
            . "Login URL: {$loginUrl}\n\n"
            . "Please log in and change your password immediately.\n\n"
            . "Regards,\n{$smtp['from_name']}";

        $vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }

        if (
            class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') &&
            !empty($smtp['host']) &&
            !empty($smtp['username']) &&
            !empty($smtp['password'])
        ) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->Port = (int)$smtp['port'];

            if ($smtp['encryption'] === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($email, $fullName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $messageHtml;
            $mail->AltBody = $messageText;

            $mail->send();
            $emailSent = true;
        } else {
            // Fallback to native mail()
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            if (!empty($smtp['from_email'])) {
                $headers .= 'From: ' . $smtp['from_name'] . ' <' . $smtp['from_email'] . ">\r\n";
            }

            $emailSent = @mail($email, $subject, $messageHtml, $headers);
            if (!$emailSent) {
                $emailError = 'SMTP mailer is unavailable and native mail() failed.';
            }
        }
    } catch (Throwable $mailEx) {
        $emailSent = false;
        $emailError = $mailEx->getMessage();
    }

    $_SESSION['staff_add_success'] = [
        'email' => $email,
        'temp_password' => $tempPassword,
        'login_url' => $loginUrl,
        'full_name' => $fullName,
        'email_sent' => $emailSent,
        'email_error' => $emailError
    ];

    header('Location: staff_add.php');
    exit();

} catch (Exception $e) {
    if (isset($db)) {
        try {
            $db->rollback();
        } catch (Exception $ignored) {}
    }

    if (defined('DB_DEBUG') && DB_DEBUG) {
        redirectWithError('Error: ' . $e->getMessage());
    }
    redirectWithError('Unable to save new staff at the moment.');
}
