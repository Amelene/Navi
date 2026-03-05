<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // SQL to create exam_attempts table
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

        -- Foreign Keys
        FOREIGN KEY (crew_id) REFERENCES crew_master(id) ON DELETE CASCADE,
        FOREIGN KEY (exam_category_id) REFERENCES exam_categories(id) ON DELETE CASCADE,

        -- Indexes
        INDEX idx_crew (crew_id),
        INDEX idx_exam_category (exam_category_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $conn->exec($sql);
    echo "Table 'exam_attempts' created successfully!";

} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
