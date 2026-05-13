<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

function toNullableDate($v) {
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}

try {
    $db = Database::getInstance();

    $db->execute(
        "CREATE TABLE IF NOT EXISTS crew_changes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vessel_name VARCHAR(255) NOT NULL,
            position_name VARCHAR(255) NOT NULL,
            crew_to_be_replaced VARCHAR(255) NOT NULL,
            license_required VARCHAR(255) NOT NULL,
            replacement_name VARCHAR(255) NOT NULL,
            replacement_license VARCHAR(255) NOT NULL,
            status_type ENUM('will_disembark','will_extend','for_deployment') NOT NULL DEFAULT 'will_disembark',
            date_joined DATE NULL,
            end_of_coe DATE NULL,
            end_of_extension DATE NULL,
            contact_number VARCHAR(100) NULL,
            target_joining_date DATE NULL,
            place_of_joining VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $data = [
        trim($_POST['vessel_name'] ?? ''),
        trim($_POST['position_name'] ?? ''),
        trim($_POST['crew_to_be_replaced'] ?? ''),
        trim($_POST['license_required'] ?? ''),
        trim($_POST['replacement_name'] ?? ''),
        trim($_POST['replacement_license'] ?? ''),
        trim($_POST['status_type'] ?? 'will_disembark'),
        toNullableDate($_POST['date_joined'] ?? ''),
        toNullableDate($_POST['end_of_coe'] ?? ''),
        toNullableDate($_POST['end_of_extension'] ?? ''),
        trim($_POST['contact_number'] ?? ''),
        toNullableDate($_POST['target_joining_date'] ?? ''),
        trim($_POST['place_of_joining'] ?? '')
    ];

    if ($data[0] === '' || $data[1] === '' || $data[2] === '' || $data[3] === '' || $data[4] === '' || $data[5] === '') {
        throw new Exception('Please fill all required fields.');
    }

    if ($id > 0) {
        $db->execute(
            "UPDATE crew_changes
             SET vessel_name=?, position_name=?, crew_to_be_replaced=?, license_required=?, replacement_name=?, replacement_license=?, status_type=?, date_joined=?, end_of_coe=?, end_of_extension=?, contact_number=?, target_joining_date=?, place_of_joining=?
             WHERE id=?",
            array_merge($data, [$id])
        );
        $_SESSION['success_message'] = 'Crew change updated successfully.';
    } else {
        $db->execute(
            "INSERT INTO crew_changes (vessel_name, position_name, crew_to_be_replaced, license_required, replacement_name, replacement_license, status_type, date_joined, end_of_coe, end_of_extension, contact_number, target_joining_date, place_of_joining)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            $data
        );
        $inserted = $db->fetchOne("SELECT id FROM crew_changes ORDER BY id DESC LIMIT 1");
        $id = (int)($inserted['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Unable to retrieve newly added crew change ID.');
        }
        $_SESSION['success_message'] = 'Crew change added successfully.';
    }

    header('Location: crew_change_details.php?id=' . (int)$id);
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    $backId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    header('Location: crew_change_form.php' . ($backId > 0 ? '?id=' . $backId : ''));
    exit();
}
