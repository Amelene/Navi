<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>Database Check</h1>";
    
    echo "<h2>Exam Categories</h2>";
    $categories = $db->fetchAll("SELECT * FROM exam_categories");
    echo "<pre>" . print_r($categories, true) . "</pre>";
    
    echo "<h2>Question Counts per Category</h2>";
    $counts = $db->fetchAll("
        SELECT ec.department, ec.category, ec.vessel_type, COUNT(q.id) as question_count
        FROM exam_categories ec
        LEFT JOIN questions q ON ec.id = q.exam_category_id
        GROUP BY ec.id
    ");
    echo "<pre>" . print_r($counts, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
