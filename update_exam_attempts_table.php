<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $tableName = 'exam_attempts';
    $columnName = 'recommendations';

    // Check if the column already exists
    $checkColumnQuery = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = ? 
                         AND COLUMN_NAME = ?";
    
    $stmt = $conn->prepare($checkColumnQuery);
    $stmt->execute([$tableName, $columnName]);
    $columnExists = $stmt->fetch();

    if ($columnExists) {
        echo "Column '$columnName' already exists in '$tableName' table. No changes made.";
    } else {
        // Add the recommendations column
        $alterTableQuery = "ALTER TABLE `$tableName` ADD `$columnName` TEXT NULL DEFAULT NULL AFTER `status`";
        $conn->exec($alterTableQuery);
        echo "Successfully added '$columnName' column to '$tableName' table.";
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
