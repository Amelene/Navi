-- ============================================
-- MANUAL SQL - Run this in phpMyAdmin
-- Copy and paste this entire script into phpMyAdmin SQL tab
-- ============================================

USE navi_shipping;

-- Add columns one by one (ignore errors if column already exists)

-- Emergency Contact
ALTER TABLE crew_master ADD COLUMN emergency_name VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN emergency_relationship VARCHAR(100);
ALTER TABLE crew_master ADD COLUMN emergency_phone VARCHAR(50);

-- Bank Information
ALTER TABLE crew_master ADD COLUMN bank_name VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN bank_account VARCHAR(100);

-- Government Numbers
ALTER TABLE crew_master ADD COLUMN sss_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN philhealth_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN pagibig_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN passport_no VARCHAR(50);

-- Seafarer's Identification
ALTER TABLE crew_master ADD COLUMN srn_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN remarks VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN sirb_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN sirb_expiry DATE;
ALTER TABLE crew_master ADD COLUMN dcoc_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN dcoc_expiry DATE;
ALTER TABLE crew_master ADD COLUMN seamans_book_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN seamans_book_expiry DATE;

-- Embarkation Details
ALTER TABLE crew_master ADD COLUMN embarkation_date DATE;
ALTER TABLE crew_master ADD COLUMN embarkation_place VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN disembarkation_date DATE;
ALTER TABLE crew_master ADD COLUMN disembarkation_place VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN disembarkation_reason TEXT;
ALTER TABLE crew_master ADD COLUMN contract_start DATE;
ALTER TABLE crew_master ADD COLUMN contract_end DATE;
ALTER TABLE crew_master ADD COLUMN extension_contract VARCHAR(100);

-- Verify columns were added
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'navi_shipping' 
  AND TABLE_NAME = 'crew_master'
  AND COLUMN_NAME IN (
    'emergency_name', 'emergency_relationship', 'emergency_phone',
    'bank_name', 'bank_account',
    'sss_no', 'philhealth_no', 'pagibig_no', 'passport_no',
    'srn_no', 'remarks', 'sirb_no', 'sirb_expiry',
    'dcoc_no', 'dcoc_expiry', 'seamans_book_no', 'seamans_book_expiry',
    'embarkation_date', 'embarkation_place',
    'disembarkation_date', 'disembarkation_place', 'disembarkation_reason',
    'contract_start', 'contract_end', 'extension_contract'
  )
ORDER BY COLUMN_NAME;
