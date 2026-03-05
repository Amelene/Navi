<?php
require_once '../config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();

    $sql = file_get_contents('../database/update_schema.sql');
    
    $connection->exec($sql);

    echo "Database updated successfully.";
} catch (Exception $e) {
    die("Error updating database: " . $e->getMessage());
}
