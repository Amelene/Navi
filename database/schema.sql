-- ============================================
-- NAVI SHIPPING DATABASE SCHEMA (MySQL)
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS navi_shipping;
USE navi_shipping;

-- ============================================
-- 1. USERS TABLE (for authentication)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'crew') DEFAULT 'staff',
    user_status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. VESSELS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS vessels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_name VARCHAR(255) UNIQUE NOT NULL,
    vessel_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. DEPARTMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. POSITIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CATEGORIES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. STAFF TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Authentication reference
    auth_user_id INT UNIQUE,
    
    -- Basic Information
    staff_no VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    
    -- Role and Status
    role VARCHAR(50) DEFAULT 'staff',
    user_status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    
    -- Personal Information
    nationality VARCHAR(100),
    birth_date DATE,
    sex ENUM('Male', 'Female', 'Other'),
    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    phone VARCHAR(20),
    address TEXT,
    
    -- Work Assignment
    vessel_id INT,
    department_id INT,
    position_id INT,
    
    -- Staff Status
    staff_status ENUM('active', 'inactive', 'on_leave', 'terminated') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (auth_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_staff_no (staff_no),
    INDEX idx_staff_status (staff_status),
    INDEX idx_department (department_id),
    INDEX idx_position (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CREW_MASTER TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS crew_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Authentication reference
    auth_user_id INT,
    role VARCHAR(50) DEFAULT 'crew',
    user_status ENUM('active', 'inactive') DEFAULT 'active',
    
    -- Basic Information
    crew_no VARCHAR(50) UNIQUE,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    nationality VARCHAR(100),
    birth_date DATE,
    sex ENUM('Male', 'Female', 'Other'),
    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    phone VARCHAR(20),
    address TEXT,
    
    -- Work Assignment
    vessel_id INT,
    department_id INT,
    position_id INT,
    crew_status ENUM('on_board', 'on_vacation', 'inactive', 'terminated') DEFAULT 'on_board',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (auth_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_crew_no (crew_no),
    INDEX idx_crew_status (crew_status),
    INDEX idx_vessel (vessel_id),
    INDEX idx_position_crew (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. APPLICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Application Identification
    application_id VARCHAR(50) UNIQUE NOT NULL,
    
    -- Position and SRN
    position_applied VARCHAR(255),
    srn_no VARCHAR(100),
    
    -- Personal Information
    name VARCHAR(255) NOT NULL,
    age INT,
    cellphone_no VARCHAR(20),
    nationality VARCHAR(100),
    birth_date DATE,
    height VARCHAR(50),
    birth_place VARCHAR(255),
    weight VARCHAR(50),
    home_address TEXT,
    email_address VARCHAR(255),
    civil_status VARCHAR(50),
    religion VARCHAR(100),
    sss_no VARCHAR(50),
    pag_ibig_no VARCHAR(50),
    tin_no VARCHAR(50),
    philhealth_no VARCHAR(50),
    umid_no VARCHAR(50),
    
    -- Educational Attainment
    school VARCHAR(255),
    school_address TEXT,
    course VARCHAR(255),
    year_graduate VARCHAR(10),
    
    -- Emergency Contact
    emergency_name VARCHAR(255),
    relationship VARCHAR(100),
    emergency_address TEXT,
    mobile_no VARCHAR(20),
    
    -- Documents (stored as JSON for flexibility)
    documents JSON,
    
    -- Training & Certificates (stored as JSON)
    training_certificates JSON,
    
    -- Additional Certificates (stored as JSON)
    additional_certificates JSON,
    
    -- Sea Service Record (stored as JSON)
    sea_service_record JSON,
    
    -- Certificate Requirements Checklist (stored as JSON)
    certificate_checklist JSON,
    
    -- Additional Information
    embark_date DATE,
    expected_salary VARCHAR(100),
    
    -- Application Status
    status ENUM('pending', 'confirmed', 'on_hold', 'rejected') DEFAULT 'pending',
    
    -- Timestamps
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_application_id (application_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert admin user (password: admin123)
INSERT INTO users (email, password, role, user_status) VALUES
('admin@navishipping.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert crew users (password: crew123 for all)
-- Password hash for 'crew123': $2y$10$rBV2nP0LhCJGXqJzYqKp5eF5o5fZJZH5xGqYqKp5eF5o5fZJZH5xG
INSERT INTO users (email, password, role, user_status) VALUES
('crew001@navishipping.com', '$2y$10$rBV2nP0LhCJGXqJzYqKp5eF5o5fZJZH5xGqYqKp5eF5o5fZJZH5xG', 'crew', 'active'),
('crew002@navishipping.com', '$2y$10$rBV2nP0LhCJGXqJzYqKp5eF5o5fZJZH5xGqYqKp5eF5o5fZJZH5xG', 'crew', 'active'),
('crew003@navishipping.com', '$2y$10$rBV2nP0LhCJGXqJzYqKp5eF5o5fZJZH5xGqYqKp5eF5o5fZJZH5xG', 'crew', 'active'),
('crew004@navishipping.com', '$2y$10$rBV2nP0LhCJGXqJzYqKp5eF5o5fZJZH5xGqYqKp5eF5o5fZJZH5xG', 'crew', 'active');

-- Insert Vessels
INSERT INTO vessels (vessel_name, vessel_type) VALUES
('MV FUTURE 01', 'Cargo Vessel'),
('MV FUTURE 02', 'Cargo Vessel'),
('MV OCEAN 06', 'Cargo Vessel'),
('LCT SEA 9', 'Landing Craft Tank'),
('LCT OCEAN 91', 'Landing Craft Tank'),
('MTKR SAMIE FAITH 1', 'Motor Tanker'),
('MTKR MARISEL', 'Motor Tanker'),
('MTKR SEWEL FAITH', 'Motor Tanker'),
('MTKR YOONA FAITH', 'Motor Tanker'),
('MTUG NAVI 01', 'Motor Tug'),
('MT ANA KATRICE PP2', 'Motor Tanker'),
('MT ANA CECILIA PP1', 'Motor Tanker');

-- Insert Departments
INSERT INTO departments (department_name) VALUES
('Deck Department'),
('Engine Department'),
('Steward Department'),
('Human Resources'),
('Operations'),
('Finance'),
('Administration');

-- Insert Positions
INSERT INTO positions (position_name) VALUES
-- Deck Positions
('MASTER'),
('CHIEF OFFICER'),
('2ND OFFICER'),
('3RD OFFICER'),
('BOSUN'),
('AB'),
('DECK CADET'),
-- Engine Positions
('CHIEF ENGINEER'),
('2ND ENGINEER'),
('3RD ENGINEER'),
('4TH ENGINEER'),
('WELDER'),
('OILER'),
('WIPER'),
('ENGINE CADET'),
-- Staff Positions
('HR MANAGER'),
('HR OFFICER'),
('OPERATIONS MANAGER'),
('FINANCE MANAGER'),
('ACCOUNTANT'),
('ADMIN OFFICER');

-- Insert Categories
INSERT INTO categories (category_name) VALUES
('Certificates'),
('Medical Records'),
('Training Records'),
('Personal Documents'),
('Contract Documents');

-- Insert Sample Staff
INSERT INTO staff (staff_no, first_name, last_name, role, user_status, nationality, birth_date, sex, civil_status, phone, address, department_id, position_id, staff_status) VALUES
('STF-2025-001', 'ANN', 'DIZON', 'staff', 'active', 'Filipino', '1985-03-15', 'Female', 'Married', '+63 912 345 6789', 'Manila, Philippines', 4, 15, 'active'),
('STF-2025-002', 'JOHN', 'SANTOS', 'staff', 'active', 'Filipino', '1990-07-22', 'Male', 'Single', '+63 923 456 7890', 'Quezon City, Philippines', 5, 17, 'active'),
('STF-2025-003', 'MARIA', 'REYES', 'staff', 'on_leave', 'Filipino', '1988-11-10', 'Female', 'Married', '+63 934 567 8901', 'Makati, Philippines', 6, 19, 'on_leave');

-- Insert Sample Crew (linked to user accounts)
INSERT INTO crew_master (auth_user_id, crew_no, first_name, last_name, role, user_status, nationality, birth_date, sex, civil_status, phone, address, vessel_id, department_id, position_id, crew_status) VALUES
(2, 'CRW-2025-001', 'LUCAS', 'CRUZ', 'crew', 'active', 'Filipino', '1980-05-20', 'Male', 'Married', '+63 945 678 9012', 'Cavite, Philippines', 1, 1, 1, 'on_vacation'),
(3, 'CRW-2025-002', 'PEDRO', 'GARCIA', 'crew', 'active', 'Filipino', '1985-08-15', 'Male', 'Single', '+63 956 789 0123', 'Batangas, Philippines', 1, 1, 2, 'on_board'),
(4, 'CRW-2025-003', 'RAMON', 'LOPEZ', 'crew', 'active', 'Filipino', '1992-02-28', 'Male', 'Single', '+63 967 890 1234', 'Manila, Philippines', 2, 2, 8, 'on_board'),
(5, 'CRW-2025-004', 'JOSE', 'MENDOZA', 'crew', 'active', 'Filipino', '1987-12-05', 'Male', 'Married', '+63 978 901 2345', 'Cebu, Philippines', 3, 2, 9, 'on_board');

-- ============================================
-- VIEWS (Optional - for easier queries)
-- ============================================

-- View for Staff with Department and Position names
CREATE OR REPLACE VIEW vw_staff_details AS
SELECT 
    s.id,
    s.staff_no,
    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
    s.first_name,
    s.last_name,
    s.nationality,
    s.birth_date,
    s.sex,
    s.civil_status,
    s.phone,
    s.address,
    d.department_name,
    p.position_name,
    v.vessel_name,
    s.staff_status,
    s.user_status,
    s.created_at
FROM staff s
LEFT JOIN departments d ON s.department_id = d.id
LEFT JOIN positions p ON s.position_id = p.id
LEFT JOIN vessels v ON s.vessel_id = v.id;

-- View for Crew with Vessel, Department and Position names
CREATE OR REPLACE VIEW vw_crew_details AS
SELECT 
    c.id,
    c.crew_no,
    CONCAT(c.first_name, ' ', c.last_name) AS full_name,
    c.first_name,
    c.last_name,
    c.nationality,
    c.birth_date,
    c.sex,
    c.civil_status,
    c.phone,
    c.address,
    v.vessel_name,
    d.department_name,
    p.position_name,
    c.crew_status,
    c.user_status,
    c.created_at
FROM crew_master c
LEFT JOIN vessels v ON c.vessel_id = v.id
LEFT JOIN departments d ON c.department_id = d.id
LEFT JOIN positions p ON c.position_id = p.id;

-- ============================================
-- END OF SCHEMA
-- ============================================
