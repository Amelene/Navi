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
$middle_name   = trim($_POST['middle_name'] ?? '');
$ext_name      = trim($_POST['ext_name'] ?? '');

$errors = [];

if ($crew_no === '') $errors[] = 'Crew No is required.';
if ($first_name === '') $errors[] = 'First Name is required.';
if ($last_name === '') $errors[] = 'Last Name is required.';
if ($position_id <= 0) $errors[] = 'On Board Position is required.';
if ($vessel_id <= 0) $errors[] = 'Vessel Assigned is required.';

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

    // Build INSERT dynamically based on actual crew_master columns (for schema compatibility)
    $columnRows = $db->fetchAll("SHOW COLUMNS FROM crew_master");
    $availableColumns = [];
    foreach ($columnRows as $col) {
        if (!empty($col['Field'])) {
            $availableColumns[$col['Field']] = true;
        }
    }

    $insertData = [
        'crew_no'    => $crew_no,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'position_id'=> $position_id,
        'vessel_id'  => $vessel_id,
    ];

    if (isset($availableColumns['middle_name'])) {
        $insertData['middle_name'] = $middle_name !== '' ? $middle_name : null;
    }
    if (isset($availableColumns['ext_name'])) {
        $insertData['ext_name'] = $ext_name !== '' ? $ext_name : null;
    }
    if (isset($availableColumns['department_id'])) {
        $insertData['department_id'] = $department_id > 0 ? $department_id : null;
    }
    if (isset($availableColumns['crew_status'])) {
        $insertData['crew_status'] = in_array($crew_status, ['on_board', 'on_vacation', 'inactive', 'terminated'], true)
            ? $crew_status
            : 'on_board';
    }
    if (isset($availableColumns['birth_date'])) {
        $insertData['birth_date'] = $birth_date !== '' ? $birth_date : null;
    }
    if (isset($availableColumns['phone'])) {
        $insertData['phone'] = $phone !== '' ? $phone : null;
    }
    if (isset($availableColumns['nationality'])) {
        $insertData['nationality'] = $nationality !== '' ? $nationality : null;
    }
    if (isset($availableColumns['address'])) {
        $insertData['address'] = $address !== '' ? $address : null;
    }

    $columns = array_keys($insertData);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO crew_master (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
    $params = array_values($insertData);

    $db->execute($sql, $params);

    $_SESSION['success_message'] = 'New crew member added successfully.';
    header('Location: crew.php');
    exit();
} catch (Exception $e) {
    error_log('crew_save.php error: ' . $e->getMessage());
    $_SESSION['crew_add_errors'] = ['Failed to save crew: An error occurred. Please try again.'];
    $_SESSION['crew_add_old'] = $_POST;
    header('Location: crew_add.php');
    exit();
}
