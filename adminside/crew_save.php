<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crew.php');
    exit();
}

$crew_no       = trim($_POST['crew_no'] ?? '');
$first_name    = trim($_POST['first_name'] ?? '');
$last_name     = trim($_POST['last_name'] ?? '');
$position_id   = (int)($_POST['position_id'] ?? 0);
$vessel_id     = (int)($_POST['vessel_id'] ?? 0);
$department_id = (int)($_POST['department_id'] ?? 0);
$crew_status   = trim($_POST['crew_status'] ?? 'on_board');
$birth_date    = trim($_POST['birth_date'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$nationality   = trim($_POST['nationality'] ?? '');
$address       = trim($_POST['address'] ?? '');

$errors = [];

if ($crew_no === '') $errors[] = 'Crew No is required.';
if ($first_name === '') $errors[] = 'First Name is required.';
if ($last_name === '') $errors[] = 'Last Name is required.';
if ($position_id <= 0) $errors[] = 'On Board Position is required.';
if ($vessel_id <= 0) $errors[] = 'Vessel Assigned is required.';

$validStatuses = ['on_board', 'on_vacation', 'inactive', 'terminated'];
if (!in_array($crew_status, $validStatuses, true)) {
    $errors[] = 'Invalid crew status selected.';
}

if ($birth_date !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!$d || $d->format('Y-m-d') !== $birth_date) {
        $errors[] = 'Birth date is invalid.';
    }
}

if (!empty($errors)) {
    $_SESSION['crew_add_errors'] = $errors;
    $_SESSION['crew_add_old'] = $_POST;
    header('Location: crew_add.php');
    exit();
}

try {
    $db = Database::getInstance();

    $existing = $db->fetchOne("SELECT id FROM crew_master WHERE crew_no = ? LIMIT 1", [$crew_no]);
    if ($existing) {
        $_SESSION['crew_add_errors'] = ['Crew No already exists.'];
        $_SESSION['crew_add_old'] = $_POST;
        header('Location: crew_add.php');
        exit();
    }

    $sql = "INSERT INTO crew_master (
                crew_no, first_name, last_name, position_id, vessel_id, department_id,
                crew_status, birth_date, phone, nationality, address
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $db->execute($sql, [
        $crew_no,
        $first_name,
        $last_name,
        $position_id,
        $vessel_id,
        $department_id > 0 ? $department_id : null,
        $crew_status,
        $birth_date !== '' ? $birth_date : null,
        $phone !== '' ? $phone : null,
        $nationality !== '' ? $nationality : null,
        $address !== '' ? $address : null
    ]);

    $_SESSION['success_message'] = 'New crew member added successfully.';
    header('Location: crew.php');
    exit();
} catch (Exception $e) {
    $_SESSION['crew_add_errors'] = ['Failed to save crew: ' . $e->getMessage()];
    $_SESSION['crew_add_old'] = $_POST;
    header('Location: crew_add.php');
    exit();
}
