-- Add function column to questions table
ALTER TABLE questions ADD COLUMN `function` VARCHAR(255) NOT NULL AFTER `question_id`;

-- Create exam_attempt_analysis table
CREATE TABLE IF NOT EXISTS exam_attempt_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    strengths TEXT,
    areas_for_improvement TEXT,
    recommendations TEXT,
    manual_recommendation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attempt_id (attempt_id),
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
