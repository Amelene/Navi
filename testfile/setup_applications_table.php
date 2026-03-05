<?php
/**
 * Setup Script for Applications Table
 * Run this file once to create the applications table in your database
 */

// Include database configuration
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Applications Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #126E82;
            border-bottom: 3px solid #126E82;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #bee5eb;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #126E82;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0e5a6b;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Applications Table Setup</h1>";

try {
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div class='info'>
            <strong>📋 Step 1:</strong> Checking database connection...<br>
            ✅ Connected to database: <strong>" . DB_NAME . "</strong>
          </div>";
    
    // Check if table already exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'applications'");
    $tableExists = $checkTable->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div class='info'>
                <strong>ℹ️ Notice:</strong> Applications table already exists!<br>
                Do you want to recreate it? (This will delete all existing data)
              </div>";
        
        // Check if force parameter is set
        if (isset($_GET['force']) && $_GET['force'] === 'yes') {
            echo "<div class='info'>
                    <strong>🔄 Step 2:</strong> Dropping existing table...
                  </div>";
            $conn->exec("DROP TABLE IF EXISTS applications");
            echo "<div class='success'>✅ Old table dropped successfully!</div>";
        } else {
            echo "<div class='info'>
                    <strong>⚠️ Warning:</strong> Table already exists. 
                    <a href='?force=yes' style='color: #721c24; font-weight: bold;'>Click here to recreate it</a> 
                    (this will delete all data)
                  </div>";
            echo "<a href='application.php' class='btn'>Go to Applications Page</a>";
            echo "</div></body></html>";
            exit;
        }
    }
    
    echo "<div class='info'>
            <strong>📋 Step 2:</strong> Creating applications table...
          </div>";
    
    // Create applications table
    $sql = "CREATE TABLE IF NOT EXISTS applications (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    
    echo "<div class='success'>
            <strong>✅ Success!</strong> Applications table created successfully!
          </div>";
    
    // Verify table structure
    echo "<div class='info'>
            <strong>📋 Step 3:</strong> Verifying table structure...
          </div>";
    
    $columns = $conn->query("DESCRIBE applications")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>
            <strong>✅ Table Structure Verified!</strong><br>
            Total columns: <strong>" . count($columns) . "</strong>
          </div>";
    
    echo "<div class='info'>
            <strong>📊 Table Columns:</strong>
            <pre>";
    foreach ($columns as $column) {
        echo sprintf("%-30s %-20s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'] === 'NO' ? 'NOT NULL' : 'NULL'
        );
    }
    echo "</pre>
          </div>";
    
    echo "<div class='success'>
            <h3>🎉 Setup Complete!</h3>
            <p>The applications table has been successfully created and is ready to use.</p>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Go to <a href='crewside/apply.php'>Application Form</a> to submit a test application</li>
                <li>Login to <a href='login.php'>Admin Panel</a> to view applications</li>
                <li>Check the <a href='application.php'>Applications Page</a> to see submitted applications</li>
            </ol>
          </div>";
    
    echo "<a href='crewside/apply.php' class='btn'>Submit Test Application</a> ";
    echo "<a href='application.php' class='btn'>View Applications</a>";
    
} catch (PDOException $e) {
    echo "<div class='error'>
            <strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
    
    echo "<div class='info'>
            <strong>💡 Troubleshooting:</strong>
            <ul>
                <li>Make sure your database credentials in <code>config/database.php</code> are correct</li>
                <li>Ensure MySQL server is running</li>
                <li>Check that the database '<strong>" . DB_NAME . "</strong>' exists</li>
                <li>Verify your MySQL user has CREATE TABLE permissions</li>
            </ul>
          </div>";
} catch (Exception $e) {
    echo "<div class='error'>
            <strong>❌ Unexpected Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "    </div>
</body>
</html>";
?>
