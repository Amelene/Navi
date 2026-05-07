<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$returnTo = trim($_GET['return_to'] ?? '');
if ($returnTo === '') {
    $returnTo = 'question_bank.php';
}

if ($id <= 0) {
    header('Location: ' . $returnTo);
    exit();
}

try {
    $db = Database::getInstance();
    // Soft delete only. Avoid depending on optional columns like updated_at.
    $db->execute("UPDATE questions SET status = 'inactive' WHERE id = ?", [$id]);
} catch (Exception $e) {
    error_log('question_delete error: ' . $e->getMessage());
}

header('Location: ' . $returnTo);
exit();
