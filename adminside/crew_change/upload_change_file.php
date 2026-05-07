<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$changeId = isset($_POST['change_id']) ? (int)$_POST['change_id'] : 0;
$fileGroup = trim($_POST['file_group'] ?? '');

if ($changeId <= 0 || !in_array($fileGroup, ['relieve', 'extension'], true)) {
    $_SESSION['error_message'] = 'Invalid upload request.';
    header('Location: crew_change_details.php?id=' . max(1, $changeId));
    exit();
}

if (!isset($_FILES['upload_file']) || ($_FILES['upload_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'Please choose a file to upload.';
    header('Location: crew_change_details.php?id=' . $changeId);
    exit();
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

    $db->execute(
        "CREATE TABLE IF NOT EXISTS crew_change_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            change_id INT NOT NULL,
            file_group ENUM('relieve','extension') NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NULL,
            file_type VARCHAR(120) NULL,
            uploaded_by INT NULL,
            uploader_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_change_group_created (change_id, file_group, created_at),
            CONSTRAINT fk_ccf_change FOREIGN KEY (change_id) REFERENCES crew_changes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $exists = $db->fetchOne("SELECT id FROM crew_changes WHERE id = ?", [$changeId]);
    if (!$exists) {
        throw new Exception('Crew change record not found.');
    }

    $uploadRoot = '../../uploads/crew_change/' . $changeId . '/' . $fileGroup . '/';
    if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true)) {
        throw new Exception('Failed to create upload folder.');
    }

    $origName = $_FILES['upload_file']['name'] ?? 'file';
    $tmpPath = $_FILES['upload_file']['tmp_name'] ?? '';
    $fileSize = (int)($_FILES['upload_file']['size'] ?? 0);
    $fileType = (string)($_FILES['upload_file']['type'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
    $finalName = $safeName . '_' . time() . '_' . substr(md5(uniqid('', true)), 0, 8) . ($ext ? '.' . $ext : '');
    $finalAbs = $uploadRoot . $finalName;
    $finalRel = 'uploads/crew_change/' . $changeId . '/' . $fileGroup . '/' . $finalName;

    if (!move_uploaded_file($tmpPath, $finalAbs)) {
        throw new Exception('Unable to save uploaded file.');
    }

    $uploadedBy = $_SESSION['user_id'] ?? null;
    $uploaderName = '';
    if (!empty($uploadedBy)) {
        try {
            // Try to get name from staff table first
            $userRow = $db->fetchOne(
                "SELECT CONCAT(s.first_name, ' ', s.last_name) as full_name FROM staff s 
                 WHERE s.auth_user_id = ? LIMIT 1",
                [(int)$uploadedBy]
            );
            if ($userRow && !empty($userRow['full_name'])) {
                $uploaderName = trim((string)$userRow['full_name']);
            } elseif (!$userRow) {
                // Fallback to email if staff record not found
                $userRow = $db->fetchOne("SELECT email FROM users WHERE id = ? LIMIT 1", [(int)$uploadedBy]);
                if ($userRow) {
                    $uploaderName = trim((string)($userRow['email'] ?? ''));
                }
            }
        } catch (Exception $e) {
            // Ignore schema differences; fallback to session values below
        }
    }

    if ($uploaderName === '') {
        $uploaderName = trim((string)($_SESSION['full_name'] ?? ''));
    }
    if ($uploaderName === '') {
        $uploaderName = trim((string)($_SESSION['username'] ?? ''));
    }
    if ($uploaderName === '') {
        $uploaderName = 'Unknown User';
    }

    $db->execute(
        "INSERT INTO crew_change_files (change_id, file_group, file_name, file_path, file_size, file_type, uploaded_by, uploader_name)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$changeId, $fileGroup, $origName, $finalRel, $fileSize, $fileType, $uploadedBy, $uploaderName]
    );

    $_SESSION['success_message'] = 'File uploaded successfully.';
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: crew_change_details.php?id=' . $changeId);
exit();
