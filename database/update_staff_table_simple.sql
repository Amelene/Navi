-- ============================================
-- SIMPLE SQL - Add columns to staff table
-- Run this in phpMyAdmin SQL tab
-- Ignore "Duplicate column" errors - they mean column already exists
-- ============================================

USE navi_shipping;

-- Emergency Contact
ALTER TABLE staff ADD COLUMN emergency_name VARCHAR(255);
ALTER TABLE staff ADD COLUMN emergency_relationship VARCHAR(100);
ALTER TABLE staff ADD COLUMN emergency_phone VARCHAR(50);

-- Government Numbers
ALTER TABLE staff ADD COLUMN sss_no VARCHAR(50);
ALTER TABLE staff ADD COLUMN philhealth_no VARCHAR(50);
ALTER TABLE staff ADD COLUMN pagibig_no VARCHAR(50);
ALTER TABLE staff ADD COLUMN passport_no VARCHAR(50);

-- Employment Details
ALTER TABLE staff ADD COLUMN date_hired DATE;

-- Status History (for inactive staff)
ALTER TABLE staff ADD COLUMN last_position VARCHAR(255);
ALTER TABLE staff ADD COLUMN status_start_date DATE;
ALTER TABLE staff ADD COLUMN status_change_date DATE;
ALTER TABLE staff ADD COLUMN status_reason TEXT;
