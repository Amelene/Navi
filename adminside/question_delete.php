<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rawId = trim($_GET['id'] ?? '');
$returnTo = trim($_GET['return_to'] ?? '');
if ($returnTo === '') {
    $returnTo = 'question_bank.php';
}

try {
    $db = Database::getInstance();

    // Normal path: valid positive primary key
    if ($id > 0) {
        $db->execute("UPDATE questions SET status = 'inactive' WHERE id = ?", [$id]);
    } else {
        // Legacy/bad-data fallback: some rows show id=0 in UI.
        // In this case, delete by provided numeric id value safely.
        if ($rawId !== '' && preg_match('/^\d+$/', $rawId)) {
            $db->execute("UPDATE questions SET status = 'inactive' WHERE id = ?", [(int)$rawId]);
        }
    }
} catch (Exception $e) {
    error_log('question_delete error: ' . $e->getMessage());
}

header('Location: ' . $returnTo);
exit();
