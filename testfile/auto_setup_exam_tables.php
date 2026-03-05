<?php
/**
 * Auto Setup Exam Tables
 * This script automatically creates all exam-related tables
 */

require_once '../config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "🚀 Starting Exam Tables Setup...\n\n";

    // SQL statements to create exam tables
    $sql = "
    -- ============================================
    -- EXAMINATION SYSTEM TABLES
    -- ============================================

    USE navi_shipping;

    -- ============================================
    -- 1. EXAM CATEGORIES TABLE
    -- ============================================
    CREATE TABLE IF NOT EXISTS exam_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department VARCHAR(50) NOT NULL COMMENT 'DECK, ENGINE, STEWARD',
        category VARCHAR(100) NOT NULL COMMENT 'MANAGEMENT, OPERATIONAL, etc.',
        vessel_type VARCHAR(100) NOT NULL COMMENT 'DRY CARGO, TANKER, etc.',
        description TEXT,
        total_questions INT DEFAULT 0,
        time_limit INT DEFAULT 30 COMMENT 'Time limit in minutes',
        passing_score INT DEFAULT 70 COMMENT 'Passing score percentage',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        -- Unique constraint for category combination
        UNIQUE KEY unique_exam_category (department, category, vessel_type),

        -- Indexes
        INDEX idx_department (department),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ============================================
    -- 2. QUESTIONS TABLE
    -- ============================================
    CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_category_id INT NOT NULL,
        question_id VARCHAR(50) COMMENT 'Original question ID from CSV (e.g., CFGH, EWRW)',
        question_text TEXT NOT NULL,
        question_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        -- Foreign Keys
        FOREIGN KEY (exam_category_id) REFERENCES exam_categories(id) ON DELETE CASCADE,

        -- Indexes
        INDEX idx_exam_category (exam_category_id),
        INDEX idx_question_id (question_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ============================================
    -- 3. QUESTION OPTIONS TABLE
    -- ============================================
    CREATE TABLE IF NOT EXISTS question_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        option_letter CHAR(1) NOT NULL COMMENT 'A, B, C, D, E',
        option_text TEXT NOT NULL,
        is_correct BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        -- Foreign Keys
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,

        -- Indexes
        INDEX idx_question (question_id),
        INDEX idx_is_correct (is_correct)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ============================================
    -- 4. EXAM ATTEMPTS TABLE (for tracking user attempts)
    -- ============================================
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

    -- ============================================
    -- 5. EXAM ANSWERS TABLE (for storing user answers)
    -- ============================================
    CREATE TABLE IF NOT EXISTS exam_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        selected_option_id INT,
        is_correct BOOLEAN DEFAULT FALSE,
        answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        -- Foreign Keys
        FOREIGN KEY (exam_attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY (selected_option_id) REFERENCES question_options(id) ON DELETE SET NULL,

        -- Indexes
        INDEX idx_exam_attempt (exam_attempt_id),
        INDEX idx_question (question_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- ============================================
    -- INSERT DEFAULT EXAM CATEGORY
    -- ============================================
    INSERT INTO exam_categories (department, category, vessel_type, description, total_questions, time_limit, passing_score)
    VALUES ('DECK', 'MANAGEMENT', 'DRY CARGO', 'Deck Management examination for Dry Cargo vessels', 0, 30, 70)
    ON DUPLICATE KEY UPDATE description = 'Deck Management examination for Dry Cargo vessels';

    -- ============================================
    -- VIEWS FOR EASIER QUERIES
    -- ============================================

    -- View for questions with their options
    CREATE OR REPLACE VIEW vw_questions_with_options AS
    SELECT
        q.id AS question_id,
        q.exam_category_id,
        ec.department,
        ec.category,
        ec.vessel_type,
        q.question_id AS original_question_id,
        q.question_text,
        q.question_order,
        qo.id AS option_id,
        qo.option_letter,
        qo.option_text,
        qo.is_correct
    FROM questions q
    INNER JOIN exam_categories ec ON q.exam_category_id = ec.id
    LEFT JOIN question_options qo ON q.id = qo.question_id
    WHERE q.status = 'active'
    ORDER BY q.question_order, qo.option_letter;

    -- View for exam results
    CREATE OR REPLACE VIEW vw_exam_results AS
    SELECT
        ea.id AS attempt_id,
        c.crew_no,
        CONCAT(c.first_name, ' ', c.last_name) AS crew_name,
        ec.department,
        ec.category,
        ec.vessel_type,
        ea.attempt_number,
        ea.start_time,
        ea.end_time,
        ea.time_taken,
        ea.score,
        ea.total_questions,
        ea.correct_answers,
        ea.status,
        CASE
            WHEN ea.score >= ec.passing_score THEN 'PASSED'
            WHEN ea.score < ec.passing_score THEN 'FAILED'
            ELSE 'PENDING'
        END AS result
    FROM exam_attempts ea
    INNER JOIN crew_master c ON ea.crew_id = c.id
    INNER JOIN exam_categories ec ON ea.exam_category_id = ec.id;
    ";

    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $successCount = 0;
    $errors = [];

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;

        try {
            $conn->exec($statement);
            $successCount++;
            echo "✓ Executed statement " . $successCount . "\n";
        } catch (PDOException $e) {
            // Only log errors that aren't "already exists"
            if (strpos($e->getMessage(), 'already exists') === false &&
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                $errors[] = $e->getMessage();
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            } else {
                echo "✓ Table/view already exists (skipped)\n";
            }
        }
    }

    echo "\n🎉 Exam Tables Setup Complete!\n";
    echo "Created $successCount database objects successfully.\n";

    if (!empty($errors)) {
        echo "\n⚠ Some warnings occurred:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }

    echo "\n📋 Created Tables:\n";
    echo "- exam_categories (exam types)\n";
    echo "- questions (exam questions)\n";
    echo "- question_options (answer choices)\n";
    echo "- exam_attempts (exam sessions)\n";
    echo "- exam_answers (user answers)\n";

    echo "\n📊 Created Views:\n";
    echo "- vw_questions_with_options\n";
    echo "- vw_exam_results\n";

    echo "\n✅ NSC Exam Results should now be accessible in the admin panel!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>