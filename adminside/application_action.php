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

if ($application_id === '' || !in_array($action, ['accept', 'on_hold', 'delete'], true)) {
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

    if ($action === 'on_hold') {
        if (!in_array($currentStatus, ['pending', 'confirmed'], true)) {
            throw new Exception('Application cannot be moved to ON HOLD from current status.');
        }

        $db->execute(
            "UPDATE applications SET status = 'on_hold' WHERE application_id = ?",
            [$application_id]
        );

        $db->commit();
        $_SESSION['success_message'] = 'Application moved to ON HOLD.';
        header('Location: application.php');
        exit();
    }

    if ($action === 'delete') {
        $fullName = trim((string)($application['name'] ?? ''));
        $nameParts = preg_split('/\s+/', $fullName);
        $first_name = strtolower(trim((string)($nameParts[0] ?? '')));
        $last_name = strtolower(trim((string)(count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '')));

        $crew = null;
        if ($first_name !== '') {
            $crew = $db->fetchOne(
                "SELECT id, crew_no FROM crew_master WHERE LOWER(first_name)=? AND LOWER(last_name)=? LIMIT 1",
                [$first_name, $last_name]
            );
        }

        if ($crew && !empty($crew['crew_no'])) {
            $crewNo = (string)$crew['crew_no'];

            $docs = $db->fetchAll("SELECT file_path FROM crew_documents WHERE crew_no = ?", [$crewNo]);
            foreach ($docs as $doc) {
                $relativePath = trim((string)($doc['file_path'] ?? ''));
                if ($relativePath === '') continue;

                $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
                if (file_exists($absolutePath) && is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
            }

            $db->execute("DELETE FROM crew_documents WHERE crew_no = ?", [$crewNo]);
        }

        $db->execute("DELETE FROM applications WHERE application_id = ?", [$application_id]);

        $db->commit();
        $_SESSION['success_message'] = 'Application and related files deleted successfully.';
        header('Location: application.php');
        exit();
    }

    // ACCEPT FLOW: allow from pending/on_hold; set confirmed + create crew record only once
    if (!in_array($currentStatus, ['pending', 'on_hold'], true)) {
        throw new Exception('Application already processed.');
    }

    $selectedVesselId = (int)($_POST['vessel_id'] ?? 0);
    if ($selectedVesselId <= 0) {
        throw new Exception('Please select a vessel before confirming.');
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

    $selectedPositionId = (int)($_POST['position_id'] ?? 0);
    $position_id = 0;

    if ($selectedPositionId > 0) {
        $position = $db->fetchOne("SELECT id FROM positions WHERE id = ? AND department = 'Crew' LIMIT 1", [$selectedPositionId]);
        $position_id = (int)($position['id'] ?? 0);
    }

    // fallback to strict resolver from position_applied only if no explicit selection was posted
    if ($position_id <= 0) {
        $positionName = trim((string)($application['position_applied'] ?? ''));
        $normalizedPositionName = strtoupper(preg_replace('/\s+/', ' ', $positionName));
        $allPositions = $db->fetchAll("SELECT id, position_name FROM positions WHERE department = 'Crew'");

        foreach ($allPositions as $p) {
            $dbPos = strtoupper(preg_replace('/\s+/', ' ', trim((string)$p['position_name'])));
            if ($dbPos === $normalizedPositionName) {
                $position_id = (int)$p['id'];
                break;
            }
        }
    }

    // Required in current schema/form: position_id and vessel_id
    // Use admin-selected vessel from confirm modal.
    $vessel = $db->fetchOne("SELECT id FROM vessels WHERE id = ? LIMIT 1", [$selectedVesselId]);
    $vessel_id = (int)($vessel['id'] ?? 0);

    if ($position_id <= 0) {
        throw new Exception('Cannot confirm: please select a valid position.');
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
