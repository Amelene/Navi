<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

function redirectSettings()
{
    header('Location: settings.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request.';
    redirectSettings();
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$skipCurrentPassword = isset($_POST['skip_current_password']) && $_POST['skip_current_password'] === '1';

if ($userId <= 0) {
    $_SESSION['error_message'] = 'Session expired. Please login again.';
    redirectSettings();
}

if ($newPassword === '' || $confirmPassword === '') {
    $_SESSION['error_message'] = 'New password and confirmation are required.';
    redirectSettings();
}

if (!$skipCurrentPassword && $currentPassword === '') {
    $_SESSION['error_message'] = 'Current password is required.';
    redirectSettings();
}

if (strlen($newPassword) < 8) {
    $_SESSION['error_message'] = 'New password must be at least 8 characters.';
    redirectSettings();
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error_message'] = 'New password and confirmation do not match.';
    redirectSettings();
}

try {
    $db = Database::getInstance();

    $user = $db->fetchOne("SELECT id, password FROM users WHERE id = ? LIMIT 1", [$userId]);

    if (!$user) {
        $_SESSION['error_message'] = 'User account not found.';
        redirectSettings();
    }

    $storedHash = $user['password'] ?? '';

    if (!$skipCurrentPassword) {
        if (!password_verify($currentPassword, $storedHash)) {
            $_SESSION['error_message'] = 'Current password is incorrect.';
            redirectSettings();
        }
    }

    if (password_verify($newPassword, $storedHash)) {
        $_SESSION['error_message'] = 'New password must be different from current password.';
        redirectSettings();
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $db->execute(
        "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$newHash, $userId]
    );

    $_SESSION['success_message'] = 'Password updated successfully.';
    redirectSettings();
} catch (Exception $e) {
    $_SESSION['error_message'] = (defined('DB_DEBUG') && DB_DEBUG)
        ? 'Update failed: ' . $e->getMessage()
        : 'Unable to update password right now.';
    redirectSettings();
}
