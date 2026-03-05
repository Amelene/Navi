<?php
/**
 * Automatic Database Setup Script
 * 
 * I-run ito sa browser para automatic na ma-setup ang database
 * URL: http://localhost/php-project/setup_database.php
 */

// Database Configuration
$host = 'localhost';
$port = '3306';
$dbname = 'navi_shipping';
$username = 'root';
$password = ''; // I-update kung may password ang MySQL mo

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Navi Shipping</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .step-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .success {
            color: #28a745;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning {
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .info {
            color: #004085;
            background: #cce5ff;
            border: 1px solid #b8daff;
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .icon {
            font-size: 20px;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .credentials {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .credentials h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .credentials p {
            margin: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Automatic Database Setup</h1>
        
        <?php
        $allSuccess = true;
        
        try {
            // Step 1: Connect to MySQL (without database)
            echo '<div class="step">';
            echo '<div class="step-title">Step 1: Connecting to MySQL Server</div>';
            
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            
            echo '<div class="success">';
            echo '<span class="icon">✓</span>';
            echo '<span>Connected to MySQL Server successfully!</span>';
            echo '</div>';
            echo '</div>';
            
            // Step 2: Create Database
            echo '<div class="step">';
            echo '<div class="step-title">Step 2: Creating Database</div>';
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE $dbname");
            
            echo '<div class="success">';
            echo '<span class="icon">✓</span>';
            echo '<span>Database <code>' . $dbname . '</code> created successfully!</span>';
            echo '</div>';
            echo '</div>';
            
            // Step 3: Create Tables
            echo '<div class="step">';
            echo '<div class="step-title">Step 3: Creating Tables</div>';
            
            // Users table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'staff', 'crew') DEFAULT 'staff',
                    user_status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Vessels table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS vessels (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    vessel_name VARCHAR(255) UNIQUE NOT NULL,
                    vessel_type VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Departments table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS departments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    department_name VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Positions table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    position_name VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Categories table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    category_name VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Staff table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS staff (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    auth_user_id INT UNIQUE,
                    staff_no VARCHAR(50) UNIQUE NOT NULL,
                    first_name VARCHAR(255) NOT NULL,
                    last_name VARCHAR(255) NOT NULL,
                    role VARCHAR(50) DEFAULT 'staff',
                    user_status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
                    nationality VARCHAR(100),
                    birth_date DATE,
                    sex ENUM('Male', 'Female', 'Other'),
                    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
                    phone VARCHAR(20),
                    address TEXT,
                    vessel_id INT,
                    department_id INT,
                    position_id INT,
                    staff_status ENUM('active', 'inactive', 'on_leave', 'terminated') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (auth_user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
                    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
                    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
                    INDEX idx_staff_no (staff_no),
                    INDEX idx_staff_status (staff_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Crew table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS crew_master (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    auth_user_id INT,
                    role VARCHAR(50) DEFAULT 'crew',
                    user_status ENUM('active', 'inactive') DEFAULT 'active',
                    crew_no VARCHAR(50) UNIQUE,
                    first_name VARCHAR(255) NOT NULL,
                    last_name VARCHAR(255) NOT NULL,
                    nationality VARCHAR(100),
                    birth_date DATE,
                    sex ENUM('Male', 'Female', 'Other'),
                    civil_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
                    phone VARCHAR(20),
                    address TEXT,
                    vessel_id INT,
                    department_id INT,
                    position_id INT,
                    crew_status ENUM('on_board', 'on_vacation', 'inactive', 'terminated') DEFAULT 'on_board',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (auth_user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE SET NULL,
                    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
                    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
                    INDEX idx_crew_no (crew_no),
                    INDEX idx_crew_status (crew_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            echo '<div class="success">';
            echo '<span class="icon">✓</span>';
            echo '<span>All tables created successfully!</span>';
            echo '</div>';
            echo '</div>';
            
            // Step 4: Insert Admin User
            echo '<div class="step">';
            echo '<div class="step-title">Step 4: Creating Admin User</div>';
            
            // Check if admin exists
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE email = 'admin@navishipping.com'");
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                // Create password hash for 'admin123'
                $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, role, user_status) 
                    VALUES (?, ?, 'admin', 'active')
                ");
                $stmt->execute(['admin@navishipping.com', $passwordHash]);
                
                echo '<div class="success">';
                echo '<span class="icon">✓</span>';
                echo '<span>Admin user created successfully!</span>';
                echo '</div>';
            } else {
                // Update existing admin password
                $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password = ?, user_status = 'active' 
                    WHERE email = 'admin@navishipping.com'
                ");
                $stmt->execute([$passwordHash]);
                
                echo '<div class="success">';
                echo '<span class="icon">✓</span>';
                echo '<span>Admin user already exists - password updated!</span>';
                echo '</div>';
            }
            echo '</div>';
            
            // Step 5: Insert Sample Data
            echo '<div class="step">';
            echo '<div class="step-title">Step 5: Inserting Sample Data</div>';
            
            // Insert Vessels
            $vessels = [
                ['MV FUTURE 01', 'Cargo Vessel'],
                ['MV FUTURE 02', 'Cargo Vessel'],
                ['MV OCEAN 06', 'Cargo Vessel'],
                ['LCT SEA 9', 'Landing Craft Tank'],
                ['LCT OCEAN 91', 'Landing Craft Tank'],
                ['MTKR SAMIE FAITH 1', 'Motor Tanker'],
                ['MTKR MARISEL', 'Motor Tanker'],
                ['MTKR SEWEL FAITH', 'Motor Tanker'],
                ['MTKR YOONA FAITH', 'Motor Tanker'],
                ['MTUG NAVI 01', 'Motor Tug'],
                ['MT ANA KATRICE PP2', 'Motor Tanker'],
                ['MT ANA CECILIA PP1', 'Motor Tanker']
            ];
            
            foreach ($vessels as $vessel) {
                $pdo->prepare("INSERT IGNORE INTO vessels (vessel_name, vessel_type) VALUES (?, ?)")
                    ->execute($vessel);
            }
            
            // Insert Departments
            $departments = [
                'Deck Department',
                'Engine Department',
                'Steward Department',
                'Human Resources',
                'Operations',
                'Finance',
                'Administration'
            ];
            
            foreach ($departments as $dept) {
                $pdo->prepare("INSERT IGNORE INTO departments (department_name) VALUES (?)")
                    ->execute([$dept]);
            }
            
            // Insert Positions
            $positions = [
                'MASTER', 'CHIEF OFFICER', '2ND OFFICER', '3RD OFFICER', 'BOSUN', 'AB', 'DECK CADET',
                'CHIEF ENGINEER', '2ND ENGINEER', '3RD ENGINEER', '4TH ENGINEER', 'WELDER', 'OILER', 'WIPER', 'ENGINE CADET',
                'HR MANAGER', 'HR OFFICER', 'OPERATIONS MANAGER', 'FINANCE MANAGER', 'ACCOUNTANT', 'ADMIN OFFICER'
            ];
            
            foreach ($positions as $pos) {
                $pdo->prepare("INSERT IGNORE INTO positions (position_name) VALUES (?)")
                    ->execute([$pos]);
            }
            
            // Insert Categories
            $categories = [
                'Certificates',
                'Medical Records',
                'Training Records',
                'Personal Documents',
                'Contract Documents'
            ];
            
            foreach ($categories as $cat) {
                $pdo->prepare("INSERT IGNORE INTO categories (category_name) VALUES (?)")
                    ->execute([$cat]);
            }
            
            echo '<div class="success">';
            echo '<span class="icon">✓</span>';
            echo '<span>Sample data inserted successfully!</span>';
            echo '</div>';
            echo '</div>';
            
            // Success Summary
            echo '<div class="credentials">';
            echo '<h3>🎉 Setup Complete!</h3>';
            echo '<p><strong>Database:</strong> ' . $dbname . '</p>';
            echo '<p><strong>Tables Created:</strong> 7 tables</p>';
            echo '<p><strong>Sample Data:</strong> Vessels, Departments, Positions loaded</p>';
            echo '<br>';
            echo '<h3>🔐 Login Credentials:</h3>';
            echo '<p><strong>Email:</strong> <code>admin@navishipping.com</code></p>';
            echo '<p><strong>Password:</strong> <code>admin123</code></p>';
            echo '<br>';
            echo '<a href="login.php" class="btn">Go to Login Page</a>';
            echo '</div>';
            
        } catch (PDOException $e) {
            $allSuccess = false;
            echo '<div class="step">';
            echo '<div class="error">';
            echo '<span class="icon">✗</span>';
            echo '<span><strong>Setup Failed!</strong><br>' . htmlspecialchars($e->getMessage()) . '</span>';
            echo '</div>';
            
            echo '<div class="warning">';
            echo '<strong>⚠️ Troubleshooting:</strong><br><br>';
            echo '1. Check if MySQL service is running<br>';
            echo '2. Verify MySQL credentials in this file (lines 10-13)<br>';
            echo '3. Make sure you have permission to create databases<br>';
            echo '4. Check if PDO MySQL extension is enabled in php.ini';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
