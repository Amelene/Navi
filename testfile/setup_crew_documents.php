<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Crew Documents Table</title>
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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #126E82;
            margin-bottom: 20px;
        }
        .message {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            background: #126E82;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0d5266;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Crew Documents Table Setup</h1>
        
        <?php
        if (isset($_GET['run'])) {
            require_once '../config/database.php';
            
            try {
                $db = Database::getInstance();
                
                echo '<div class="message info">Starting setup...</div>';
                
                // Create crew_documents table
                $sql = "CREATE TABLE IF NOT EXISTS crew_documents (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    
                    crew_id INT NOT NULL,
                    crew_no VARCHAR(50) NOT NULL,
                    
                    document_category ENUM('medical_certificate', 'contract_file', 'embarkation_file', 'disembarkation_file') NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_size INT,
                    file_type VARCHAR(100),
                    
                    expiration_date DATE,
                    
                    uploaded_by INT,
                    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    status ENUM('active', 'expired', 'archived') DEFAULT 'active',
                    
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (crew_id) REFERENCES crew_master(id) ON DELETE CASCADE,
                    
                    INDEX idx_crew_id (crew_id),
                    INDEX idx_crew_no (crew_no),
                    INDEX idx_category (document_category),
                    INDEX idx_status (status),
                    INDEX idx_expiration (expiration_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $db->execute($sql);
                
                echo '<div class="message success">✅ crew_documents table created successfully!</div>';
                
                // Create uploads directory structure
                $uploadDirs = [
                    'uploads',
                    'uploads/crew_documents',
                    'uploads/crew_documents/medical_certificates',
                    'uploads/crew_documents/contract_files',
                    'uploads/crew_documents/embarkation_files',
                    'uploads/crew_documents/disembarkation_files'
                ];
                
                echo '<div class="message info">Creating upload directories...</div>';
                
                foreach ($uploadDirs as $dir) {
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                        echo '<div class="message success">✅ Created directory: ' . htmlspecialchars($dir) . '</div>';
                    } else {
                        echo '<div class="message info">ℹ️  Directory already exists: ' . htmlspecialchars($dir) . '</div>';
                    }
                }
                
                echo '<div class="message success"><strong>✅ Setup completed successfully!</strong></div>';
                echo '<p>You can now use the crew document upload system.</p>';
                echo '<a href="crew.php" class="btn">Go to Crew Management</a>';
                echo ' <a href="crew_upload.php" class="btn">Go to Upload Files</a>';
                
            } catch (Exception $e) {
                echo '<div class="message error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<p>Please check your database connection and try again.</p>';
            }
        } else {
            ?>
            <p>This will create the <strong>crew_documents</strong> table and upload directories needed for the crew document upload system.</p>
            
            <div class="message info">
                <strong>What will be created:</strong>
                <ul>
                    <li>crew_documents table in the database</li>
                    <li>Upload directories for document storage</li>
                    <li>Proper indexes for fast queries</li>
                </ul>
            </div>
            
            <div class="message info">
                <strong>Table Structure:</strong>
                <pre>crew_documents (
    id, crew_id, crew_no,
    document_category, file_name, file_path,
    file_size, file_type, expiration_date,
    uploaded_by, upload_date, status,
    created_at, updated_at
)</pre>
            </div>
            
            <a href="?run=1" class="btn">Run Setup Now</a>
            <?php
        }
        ?>
    </div>
</body>
</html>
