-- ============================================
-- CREW ADDITIONAL DETAILS TABLE
-- ============================================

USE navi_shipping;

-- Create crew_additional_details table
CREATE TABLE IF NOT EXISTS crew_additional_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Crew Reference
    crew_id INT NOT NULL,
    crew_no VARCHAR(50) NOT NULL,
    
    -- Emergency Contact
    emergency_name VARCHAR(255),
    emergency_relationship VARCHAR(100),
    emergency_phone VARCHAR(50),
    
    -- Bank Information
    bank_name VARCHAR(255),
    bank_account VARCHAR(100),
    
    -- Government Numbers
    sss_no VARCHAR(50),
    philhealth_no VARCHAR(50),
    pagibig_no VARCHAR(50),
    passport_no VARCHAR(50),
    
    -- Seafarer's Identification
    srn_no VARCHAR(50),
    remarks VARCHAR(255),
    sirb_no VARCHAR(50),
    sirb_expiry DATE,
    dcoc_no VARCHAR(50),
    dcoc_expiry DATE,
    seamans_book_no VARCHAR(50),
    seamans_book_expiry DATE,
    
    -- Position and Vessel
    position VARCHAR(100),
    vessel VARCHAR(100),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (crew_id) REFERENCES crew_master(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_crew_id (crew_id),
    INDEX idx_crew_no (crew_no),
    UNIQUE KEY unique_crew (crew_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
