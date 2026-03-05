-- ============================================
-- UPDATE STAFF TABLE
-- Add additional fields for staff details
-- ============================================

USE navi_shipping;

-- This script will only add columns that don't exist
-- Run this in phpMyAdmin SQL tab

SET @dbname = 'navi_shipping';
SET @tablename = 'staff';

-- Emergency Contact
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'emergency_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN emergency_name VARCHAR(255)', 'SELECT "emergency_name already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'emergency_relationship');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN emergency_relationship VARCHAR(100)', 'SELECT "emergency_relationship already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'emergency_phone');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN emergency_phone VARCHAR(50)', 'SELECT "emergency_phone already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Government Numbers
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'sss_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN sss_no VARCHAR(50)', 'SELECT "sss_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'philhealth_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN philhealth_no VARCHAR(50)', 'SELECT "philhealth_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'pagibig_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN pagibig_no VARCHAR(50)', 'SELECT "pagibig_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'passport_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN passport_no VARCHAR(50)', 'SELECT "passport_no already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Employment Details
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'date_hired');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff ADD COLUMN date_hired DATE', 'SELECT "date_hired already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Show all new columns
SELECT 'All columns added successfully!' as Status;

SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'navi_shipping' 
  AND TABLE_NAME = 'staff'
  AND COLUMN_NAME IN (
    'emergency_name', 'emergency_relationship', 'emergency_phone',
    'sss_no', 'philhealth_no', 'pagibig_no', 'passport_no',
    'date_hired'
  )
ORDER BY COLUMN_NAME;
