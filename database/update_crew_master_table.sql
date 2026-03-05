-- ============================================
-- UPDATE CREW_MASTER TABLE
-- Add additional fields for crew details
-- ============================================

USE navi_shipping;

-- Add Emergency Contact fields
ALTER TABLE crew_master ADD COLUMN emergency_name VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN emergency_relationship VARCHAR(100);
ALTER TABLE crew_master ADD COLUMN emergency_phone VARCHAR(50);

-- Add Bank Information fields
ALTER TABLE crew_master ADD COLUMN bank_name VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN bank_account VARCHAR(100);

-- Add Government Numbers fields
ALTER TABLE crew_master ADD COLUMN sss_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN philhealth_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN pagibig_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN passport_no VARCHAR(50);

-- Add Seafarer's Identification fields
ALTER TABLE crew_master ADD COLUMN srn_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN remarks VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN sirb_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN sirb_expiry DATE;
ALTER TABLE crew_master ADD COLUMN dcoc_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN dcoc_expiry DATE;
ALTER TABLE crew_master ADD COLUMN seamans_book_no VARCHAR(50);
ALTER TABLE crew_master ADD COLUMN seamans_book_expiry DATE;

-- Add Embarkation fields
ALTER TABLE crew_master ADD COLUMN embarkation_date DATE;
ALTER TABLE crew_master ADD COLUMN embarkation_place VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN disembarkation_date DATE;
ALTER TABLE crew_master ADD COLUMN disembarkation_place VARCHAR(255);
ALTER TABLE crew_master ADD COLUMN disembarkation_reason TEXT;
ALTER TABLE crew_master ADD COLUMN contract_start DATE;
ALTER TABLE crew_master ADD COLUMN contract_end DATE;
ALTER TABLE crew_master ADD COLUMN extension_contract VARCHAR(100);
