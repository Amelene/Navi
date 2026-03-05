<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check current database
    $result = $conn->query("SELECT DATABASE() as current_db");
    $currentDb = $result->fetch()['current_db'];
    echo "Current database: " . $currentDb . "<br>";

    // Check if exam_attempts table exists
    $result = $conn->query("SHOW TABLES LIKE 'exam_attempts'");
    $tableExists = $result->fetch();

    if ($tableExists) {
        echo "✓ exam_attempts table exists<br>";

        // Check table structure
        $result = $conn->query("DESCRIBE exam_attempts");
        echo "Table structure:<br>";
        while ($row = $result->fetch()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
        }
    } else {
        echo "✗ exam_attempts table does not exist<br>";

        // Try to create it
        echo "Attempting to create table...<br>";
        $sql = "
        CREATE TABLE IF NOT EXISTS exam_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            crew_id INT NOT NULL,
            exam_category_id INT NOT NULL,
            attempt_number INT DEFAULT 1,
            start_time DATETIME NOT NULL,
            end_time DATETIME,
            time_taken INT COMMENT 'Time taken in seconds',
            score DECIMAL(5,2) COMMENT 'Score percentage',
            total_questions INT,
            correct_answers INT,
            status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (crew_id) REFERENCES crew_master(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_category_id) REFERENCES exam_categories(id) ON DELETE CASCADE,
            INDEX idx_crew (crew_id),
            INDEX idx_exam_category (exam_category_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $conn->exec($sql);
        echo "✓ Table created successfully<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
