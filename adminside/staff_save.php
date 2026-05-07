<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

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

    // Store invitation-ready details (Option A: manual send)
    $_SESSION['staff_add_success'] = [
        'email' => $email,
        'temp_password' => $tempPassword,
        'login_url' => (isset($_SERVER['HTTP_HOST']) ? ('http://' . $_SERVER['HTTP_HOST']) : '') . '/php-project/adminside/login.php',
        'full_name' => trim($first_name . ' ' . $middle_name . ' ' . $last_name)
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
