-- ============================================
-- CREW DOCUMENTS TABLE
-- ============================================

USE navi_shipping;

-- Create crew_documents table
CREATE TABLE IF NOT EXISTS crew_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Crew Reference
    crew_id INT NOT NULL,
    crew_no VARCHAR(50) NOT NULL,
    
    -- Document Information
    document_category ENUM('medical_certificate', 'contract_file', 'embarkation_file', 'disembarkation_file') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(100),
    
    -- Expiration Information
    expiration_date DATE,
    
    -- Upload Information
    uploaded_by INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Status
    status ENUM('active', 'expired', 'archived') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (crew_id) REFERENCES crew_master(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_crew_id (crew_id),
    INDEX idx_crew_no (crew_no),
    INDEX idx_category (document_category),
    INDEX idx_status (status),
    INDEX idx_expiration (expiration_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create uploads directory structure (to be created via PHP)
-- uploads/
--   crew_documents/
--     medical_certificates/
--     contract_files/
--     embarkation_files/
--     disembarkation_files/
