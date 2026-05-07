<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../../config/database.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid crew change id.';
    header('Location: ../rep.php');
    exit();
}

try {
    $db = Database::getInstance();

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

    $files = $db->fetchAll("SELECT file_path FROM crew_change_files WHERE change_id = ?", [$id]);
    foreach ($files as $f) {
        $path = '../../' . ltrim((string)$f['file_path'], '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $db->execute("DELETE FROM crew_change_messages WHERE change_id = ?", [(string)$id]);
    $db->execute("DELETE FROM crew_change_files WHERE change_id = ?", [$id]);
    $db->execute("DELETE FROM crew_changes WHERE id = ?", [$id]);

    $_SESSION['success_message'] = 'Crew change deleted successfully.';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Delete failed: ' . $e->getMessage();
}

header('Location: ../rep.php');
exit();
