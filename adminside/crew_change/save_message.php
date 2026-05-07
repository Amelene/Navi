<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

function backToDetails($id)
{
    header('Location: crew_change_details.php?id=' . urlencode($id));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    backToDetails($_GET['id'] ?? '001');
}

$change_id = trim($_POST['change_id'] ?? '');
$message_group = trim($_POST['message_group'] ?? '');
$message_text = trim($_POST['message_text'] ?? '');

$allowedGroups = ['crew_remarks', 'crew_answer', 'candidate_remarks', 'candidate_questions', 'candidate_answer'];

if ($change_id === '' || !in_array($message_group, $allowedGroups, true) || $message_text === '') {
    $_SESSION['error_message'] = 'Please complete the message field.';
    backToDetails($change_id !== '' ? $change_id : '001');
}

try {
    $db = Database::getInstance();

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $sessionEmail = trim($_SESSION['user_email'] ?? '');
    $sessionRole = strtoupper(trim($_SESSION['user_role'] ?? 'STAFF'));

    $user = null;
    if ($userId > 0) {
        $user = $db->fetchOne("SELECT id, email, role FROM users WHERE id = ? LIMIT 1", [$userId]);
    }

    $email = $user['email'] ?? $sessionEmail;
    $role = strtoupper($user['role'] ?? strtolower($sessionRole));
    $senderName = 'User';

    if (!empty($email) && strpos($email, '@') !== false) {
        $senderName = explode('@', $email)[0];
    } elseif ($role !== '') {
        $senderName = ucfirst(strtolower($role));
    }

    $db->execute(
        "INSERT INTO crew_change_messages (change_id, message_group, message_text, sender_user_id, sender_name)
         VALUES (?, ?, ?, ?, ?)",
        [
            $change_id,
            $message_group,
            $message_text,
            $userId > 0 ? $userId : null,
            $senderName
        ]
    );

    $_SESSION['success_message'] = 'Message posted successfully.';
} catch (Exception $e) {
    $_SESSION['error_message'] = (defined('DB_DEBUG') && DB_DEBUG)
        ? 'Error: ' . $e->getMessage()
        : 'Unable to save message.';
}

backToDetails($change_id);
