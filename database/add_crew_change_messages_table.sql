USE navi_shipping;

CREATE TABLE IF NOT EXISTS crew_change_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_id VARCHAR(50) NOT NULL,
    message_group ENUM('crew_remarks', 'crew_answer', 'candidate_remarks', 'candidate_questions', 'candidate_answer') NOT NULL,
    message_text TEXT NOT NULL,
    sender_user_id INT NULL,
    sender_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_change_group (change_id, message_group),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_ccm_user
        FOREIGN KEY (sender_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
