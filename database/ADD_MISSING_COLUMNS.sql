-- ============================================
-- SAFE SQL - Only adds columns that don't exist
-- Run this in phpMyAdmin SQL tab
-- ============================================

USE navi_shipping;

-- This script will only add columns that don't exist
-- Ignore any "Duplicate column" errors - they mean the column already exists

SET @dbname = 'navi_shipping';
SET @tablename = 'crew_master';

-- Emergency Contact
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'emergency_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN emergency_name VARCHAR(255)', 'SELECT "emergency_name already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'emergency_relationship');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN emergency_relationship VARCHAR(100)', 'SELECT "emergency_relationship already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'emergency_phone');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN emergency_phone VARCHAR(50)', 'SELECT "emergency_phone already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Bank Information
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'bank_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN bank_name VARCHAR(255)', 'SELECT "bank_name already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'bank_account');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN bank_account VARCHAR(100)', 'SELECT "bank_account already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Government Numbers
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'sss_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN sss_no VARCHAR(50)', 'SELECT "sss_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'philhealth_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN philhealth_no VARCHAR(50)', 'SELECT "philhealth_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'pagibig_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN pagibig_no VARCHAR(50)', 'SELECT "pagibig_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'passport_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN passport_no VARCHAR(50)', 'SELECT "passport_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Seafarer's Identification
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'srn_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN srn_no VARCHAR(50)', 'SELECT "srn_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'remarks');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN remarks VARCHAR(255)', 'SELECT "remarks already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'sirb_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN sirb_no VARCHAR(50)', 'SELECT "sirb_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'sirb_expiry');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN sirb_expiry DATE', 'SELECT "sirb_expiry already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'dcoc_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN dcoc_no VARCHAR(50)', 'SELECT "dcoc_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'dcoc_expiry');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN dcoc_expiry DATE', 'SELECT "dcoc_expiry already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'seamans_book_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN seamans_book_no VARCHAR(50)', 'SELECT "seamans_book_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'seamans_book_expiry');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN seamans_book_expiry DATE', 'SELECT "seamans_book_expiry already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Embarkation Details
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'embarkation_date');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN embarkation_date DATE', 'SELECT "embarkation_date already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'embarkation_place');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN embarkation_place VARCHAR(255)', 'SELECT "embarkation_place already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'disembarkation_date');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN disembarkation_date DATE', 'SELECT "disembarkation_date already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'disembarkation_place');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN disembarkation_place VARCHAR(255)', 'SELECT "disembarkation_place already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'disembarkation_reason');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN disembarkation_reason TEXT', 'SELECT "disembarkation_reason already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'contract_start');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN contract_start DATE', 'SELECT "contract_start already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'contract_end');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN contract_end DATE', 'SELECT "contract_end already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'extension_contract');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE crew_master ADD COLUMN extension_contract VARCHAR(100)', 'SELECT "extension_contract already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Show all new columns
SELECT 'All columns added successfully!' as Status;

SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
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
