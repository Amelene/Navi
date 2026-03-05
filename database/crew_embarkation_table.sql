-- ============================================
-- CREW EMBARKATION TABLE
-- ============================================

USE navi_shipping;

-- Create crew_embarkation table
CREATE TABLE IF NOT EXISTS crew_embarkation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Crew Reference
    crew_id INT NOT NULL,
    crew_no VARCHAR(50) NOT NULL,
    
    -- Embarkation Details
    embarkation_date DATE,
    embarkation_place VARCHAR(255),
    
    -- Disembarkation Details
    disembarkation_date DATE,
    disembarkation_place VARCHAR(255),
    disembarkation_reason TEXT,
    
    -- Contract Details
    contract_start DATE,
    contract_end DATE,
    extension_contract VARCHAR(100),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (crew_id) REFERENCES crew_master(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_crew_id (crew_id),
    INDEX idx_crew_no (crew_no),
    INDEX idx_embarkation_date (embarkation_date),
    INDEX idx_disembarkation_date (disembarkation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
