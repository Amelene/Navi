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

/**
 * Normalize birth date from either:
 * - HTML date input: Y-m-d
 * - Text input fallback: m/d/Y
 */
if ($birth_date !== '') {
    $parsedDate = null;

    $d1 = DateTime::createFromFormat('Y-m-d', $birth_date);
    if ($d1 && $d1->format('Y-m-d') === $birth_date) {
        $parsedDate = $d1->format('Y-m-d');
    } else {
        $d2 = DateTime::createFromFormat('m/d/Y', $birth_date);
        if ($d2 && $d2->format('m/d/Y') === $birth_date) {
            $parsedDate = $d2->format('Y-m-d');
        }
    }

    if ($parsedDate === null) {
        $birth_date = '';
    } else {
        $birth_date = $parsedDate;
    }
}

$errors = [];

if ($crew_no === '') $errors[] = 'Crew No is required.';
if ($first_name === '') $errors[] = 'First Name is required.';
if ($last_name === '') $errors[] = 'Last Name is required.';
if ($position_id <= 0) $errors[] = 'On Board Position is required.';
if ($vessel_id <= 0) $errors[] = 'Vessel Assigned is required.';


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

    // Try multiple insert shapes for compatibility with different live schemas.
    $validCrewStatus = in_array($crew_status, ['on_board', 'on_vacation', 'inactive', 'terminated'], true)
        ? $crew_status
        : 'on_board';

    $insertAttempts = [
        // Full modern schema
        [
            'sql' => "INSERT INTO crew_master (
                        crew_no, first_name, last_name, position_id, vessel_id, department_id,
                        crew_status, birth_date, phone, nationality, address
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'params' => [
                $crew_no,
                $first_name,
                $last_name,
                $position_id,
                $vessel_id,
                $department_id > 0 ? $department_id : null,
                $validCrewStatus,
                $birth_date !== '' ? $birth_date : null,
                $phone !== '' ? $phone : null,
                $nationality !== '' ? $nationality : null,
                $address !== '' ? $address : null
            ],
        ],
        // Without department/status
        [
            'sql' => "INSERT INTO crew_master (
                        crew_no, first_name, last_name, position_id, vessel_id,
                        birth_date, phone, nationality, address
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'params' => [
                $crew_no,
                $first_name,
                $last_name,
                $position_id,
                $vessel_id,
                $birth_date !== '' ? $birth_date : null,
                $phone !== '' ? $phone : null,
                $nationality !== '' ? $nationality : null,
                $address !== '' ? $address : null
            ],
        ],
        // Minimal legacy schema
        [
            'sql' => "INSERT INTO crew_master (crew_no, first_name, last_name, position_id, vessel_id)
                      VALUES (?, ?, ?, ?, ?)",
            'params' => [
                $crew_no,
                $first_name,
                $last_name,
                $position_id,
                $vessel_id
            ],
        ],
    ];

    $saved = false;
    $lastError = null;

    foreach ($insertAttempts as $attempt) {
        try {
            $db->execute($attempt['sql'], $attempt['params']);
            $saved = true;
            break;
        } catch (Exception $inner) {
            $lastError = $inner;
            error_log('crew_save.php insert attempt failed: ' . $inner->getMessage() . ' | SQL: ' . $attempt['sql']);
        }
    }

    if (!$saved) {
        throw new Exception($lastError ? $lastError->getMessage() : 'Insert failed for all schema variants.');
    }

    $_SESSION['success_message'] = 'New crew member added successfully.';
    header('Location: crew.php');
    exit();
} catch (Exception $e) {
    error_log('crew_save.php error: ' . $e->getMessage());
    error_log('crew_save.php post: ' . json_encode($_POST));
    $_SESSION['crew_add_errors'] = ['Failed to save crew: An error occurred. Please try again.'];
    $_SESSION['crew_add_old'] = $_POST;
    header('Location: crew_add.php');
    exit();
}


