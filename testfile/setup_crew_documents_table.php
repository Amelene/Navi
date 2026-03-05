<?php
require_once '../config/database.php';

try {
    $db = Database::getInstance();
    
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
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
        
        INDEX idx_crew_id (crew_id),
        INDEX idx_crew_no (crew_no),
        INDEX idx_category (document_category),
        INDEX idx_status (status),
        INDEX idx_expiration (expiration_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->execute($sql);
    
    echo "✅ crew_documents table created successfully!\n";
    
    // Create uploads directory structure
    $uploadDirs = [
        'uploads',
        'uploads/crew_documents',
        'uploads/crew_documents/medical_certificates',
        'uploads/crew_documents/contract_files',
        'uploads/crew_documents/embarkation_files',
        'uploads/crew_documents/disembarkation_files'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Created directory: $dir\n";
        } else {
            echo "ℹ️  Directory already exists: $dir\n";
        }
    }
    
    echo "\n✅ Setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
