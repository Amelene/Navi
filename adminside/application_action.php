<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: application.php');
    exit();
}

$application_id = trim($_POST['application_id'] ?? '');
$action         = trim($_POST['action'] ?? '');

if ($application_id === '' || !in_array($action, ['accept', 'decline'], true)) {
    $_SESSION['error_message'] = 'Invalid application action request.';
    header('Location: application.php');
    exit();
}

try {
    $db = Database::getInstance();
    $db->beginTransaction();

    $application = $db->fetchOne(
        "SELECT * FROM applications WHERE application_id = ? LIMIT 1",
        [$application_id]
    );

    if (!$application) {
        throw new Exception('Application not found.');
    }

    $currentStatus = (string)($application['status'] ?? '');

    if ($action === 'decline') {
        if (!in_array($currentStatus, ['pending', 'on_hold'], true)) {
            throw new Exception('Application already processed.');
        }
        $db->execute(
            "UPDATE applications SET status = 'on_hold' WHERE application_id = ?",
            [$application_id]
        );

        $db->commit();
        $_SESSION['success_message'] = 'Application declined and moved to ON HOLD.';
        header('Location: application.php');
        exit();
    }

    // ACCEPT FLOW: allow from pending/on_hold; set confirmed + create crew record only once
    if (!in_array($currentStatus, ['pending', 'on_hold'], true)) {
        throw new Exception('Application already processed.');
    }

    $year = date('Y');
    $latestAnyYear = $db->fetchOne(
        "SELECT crew_no FROM crew_master WHERE crew_no REGEXP '^CRW-[0-9]{4}-[0-9]+$' ORDER BY id DESC LIMIT 1"
    );

    $nextNumber = 1;
    if (!empty($latestAnyYear['crew_no']) && preg_match('/CRW-\d{4}-(\d+)/', $latestAnyYear['crew_no'], $m)) {
        $nextNumber = ((int)$m[1]) + 1;
    }
    $crew_no = sprintf('CRW-%s-%03d', $year, $nextNumber);

    // Basic mapping from application -> crew_master
    $fullName = trim((string)($application['name'] ?? ''));
    $nameParts = preg_split('/\s+/', $fullName);
    $first_name = $nameParts[0] ?? 'N/A';
    $last_name = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

    $positionName = trim((string)($application['position_applied'] ?? ''));
    $normalizedPositionName = preg_replace('/\s+/', ' ', strtolower($positionName));
    $position = null;
    if ($normalizedPositionName !== '') {
        $allPositions = $db->fetchAll("SELECT id, position_name FROM positions");
        foreach ($allPositions as $p) {
            $dbPos = preg_replace('/\s+/', ' ', strtolower(trim((string)$p['position_name'])));
            if ($dbPos === $normalizedPositionName) {
                $position = $p;
                break;
            }

            $appParts = array_map('trim', explode('/', $normalizedPositionName));
            $dbParts  = array_map('trim', explode('/', $dbPos));
            $matched = false;
            foreach ($appParts as $ap) {
                foreach ($dbParts as $dp) {
                    if ($ap !== '' && $dp !== '' && ($ap === $dp || str_contains($ap, $dp) || str_contains($dp, $ap))) {
                        $matched = true;
                        break 2;
                    }
                }
            }
            if ($matched) {
                $position = $p;
                break;
            }
        }
    }

    $position_id = (int)($position['id'] ?? 0);

    // Required in current schema/form: position_id and vessel_id
    // Auto-assign first vessel so Crew Management has a selectable/visible vessel value (not N/A).
    $vessel = $db->fetchOne("SELECT id FROM vessels ORDER BY id ASC LIMIT 1");
    $vessel_id = (int)($vessel['id'] ?? 0);

    if ($position_id <= 0) {
        throw new Exception('Cannot confirm: position_applied has no matching position record.');
    }
    if ($vessel_id <= 0) {
        throw new Exception('Cannot confirm: no vessel record available for assignment.');
    }

    // prevent duplicate crew creation for same person+birthdate
    $existingCrew = $db->fetchOne(
        "SELECT id FROM crew_master WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND (birth_date <=> ?) LIMIT 1",
        [$first_name, $last_name, !empty($application['birth_date']) ? $application['birth_date'] : null]
    );

    if (!$existingCrew) {
        $db->execute(
            "INSERT INTO crew_master (
                crew_no, first_name, last_name, position_id, vessel_id, department_id,
                crew_status, birth_date, phone, nationality, address
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $crew_no,
                $first_name,
                $last_name,
                $position_id,
                $vessel_id,
                null,
                'on_board',
                !empty($application['birth_date']) ? $application['birth_date'] : null,
                !empty($application['cellphone_no']) ? $application['cellphone_no'] : null,
                !empty($application['nationality']) ? $application['nationality'] : null,
                !empty($application['home_address']) ? $application['home_address'] : null
            ]
        );
    }

    $db->execute(
        "UPDATE applications SET status = 'confirmed' WHERE application_id = ?",
        [$application_id]
    );

    $db->commit();

    $_SESSION['success_message'] = 'Application accepted. Applicant moved to Crew Management as CONFIRMED.';
    header('Location: application.php');
    exit();
} catch (Exception $e) {
    if (isset($db)) {
        try {
            $db->rollback();
        } catch (Exception $rollbackError) {
            // no-op
        }
    }

    $_SESSION['error_message'] = 'Failed to process application: ' . $e->getMessage();
    header('Location: application.php');
    exit();
}
