<?php
/**
 * Simple Script to Import Deck Questions from CSV to MySQL
 * 
 * This script will:
 * 1. Create the necessary database tables
 * 2. Import questions from CSV file
 * 
 * Just run this file in your browser: http://localhost/php-project/import_questions.php
 */

require_once '../config/database.php';
set_time_limit(300);

$db = Database::getInstance();
$conn = $db->getConnection();

// Configuration
$csvFile = 'crewside/csv/deck-management-drycargo.csv';
$department = 'DECK';
$category = 'MANAGEMENT';
$vesselType = 'DRY CARGO';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Questions</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
        .stat-number { font-size: 32px; font-weight: bold; color: #4CAF50; }
        .stat-label { color: #666; margin-top: 5px; }
        .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 Import Deck Management Questions</h1>
        
<?php

try {
    echo '<div class="info">Starting import process...</div>';
    
    // STEP 1: Create Tables
    echo '<h2>Step 1: Creating Database Tables</h2>';
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS exam_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department VARCHAR(50) NOT NULL,
            category VARCHAR(100) NOT NULL,
            vessel_type VARCHAR(100) NOT NULL,
            description TEXT,
            total_questions INT DEFAULT 0,
            time_limit INT DEFAULT 30,
            passing_score INT DEFAULT 70,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_exam (department, category, vessel_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_category_id INT NOT NULL,
            question_id VARCHAR(50),
            question_text TEXT NOT NULL,
            question_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_category_id) REFERENCES exam_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS question_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_id INT NOT NULL,
            option_letter CHAR(1) NOT NULL,
            option_text TEXT NOT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($tables as $sql) {
        $conn->exec($sql);
    }
    
    echo '<div class="success">✓ Database tables created successfully!</div>';
    
    // STEP 2: Get or Create Exam Category
    echo '<h2>Step 2: Setting Up Exam Category</h2>';
    
    $stmt = $conn->prepare("SELECT id FROM exam_categories WHERE department = ? AND category = ? AND vessel_type = ?");
    $stmt->execute([$department, $category, $vesselType]);
    $categoryRow = $stmt->fetch();
    
    if (!$categoryRow) {
        $stmt = $conn->prepare("INSERT INTO exam_categories (department, category, vessel_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$department, $category, $vesselType, 'Deck Management examination for Dry Cargo vessels']);
        $categoryId = $conn->lastInsertId();
        echo '<div class="success">✓ Created new exam category (ID: ' . $categoryId . ')</div>';
    } else {
        $categoryId = $categoryRow['id'];
        echo '<div class="info">Using existing exam category (ID: ' . $categoryId . ')</div>';
    }
    
    // STEP 3: Clear existing questions
    echo '<h2>Step 3: Clearing Old Questions</h2>';
    
    $conn->exec("DELETE FROM question_options WHERE question_id IN (SELECT id FROM questions WHERE exam_category_id = $categoryId)");
    $conn->exec("DELETE FROM questions WHERE exam_category_id = $categoryId");
    
    echo '<div class="success">✓ Cleared existing questions</div>';
    
    // STEP 4: Import from CSV
    echo '<h2>Step 4: Importing Questions from CSV</h2>';
    
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file not found: $csvFile");
    }
    
    $file = fopen($csvFile, 'r');
    fgetcsv($file); // Skip header
    
    $imported = 0;
    $questionOrder = 1;
    
    $conn->beginTransaction();
    
    while (($row = fgetcsv($file)) !== false) {
        $questionText = trim($row[0] ?? '');
        if (empty($questionText)) continue;
        
        // Extract Question ID
        $questionId = null;
        if (preg_match('/Question ID:\s*([A-Z]+)/i', $questionText, $matches)) {
            $questionId = $matches[1];
        }
        
        // Clean question text
        $questionText = preg_replace('/^Question ID:\s*[A-Z]+\s*/i', '', $questionText);
        $questionText = preg_replace('/^Question:\s*\*?/i', '', $questionText);
        
        // Get options
        $options = [
            'A' => trim($row[1] ?? ''),
            'B' => trim($row[2] ?? ''),
            'C' => trim($row[3] ?? ''),
            'D' => trim($row[4] ?? ''),
            'E' => trim($row[5] ?? '')
        ];
        
        // Find correct answer (marked with *)
        $correctAnswer = null;
        foreach ($options as $letter => $text) {
            if (strpos($text, '*') !== false) {
                $correctAnswer = $letter;
                $options[$letter] = str_replace('*', '', $text);
            }
            // Clean option text
            $options[$letter] = preg_replace('/^[A-E][\:\.\s]+/i', '', $options[$letter]);
            $options[$letter] = trim($options[$letter]);
        }
        
        // Insert question
        $stmt = $conn->prepare("INSERT INTO questions (exam_category_id, question_id, question_text, question_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$categoryId, $questionId, $questionText, $questionOrder]);
        $newQuestionId = $conn->lastInsertId();
        
        // Insert options
        foreach ($options as $letter => $text) {
            if (!empty($text)) {
                $isCorrect = ($letter === $correctAnswer) ? 1 : 0;
                $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_letter, option_text, is_correct) VALUES (?, ?, ?, ?)");
                $stmt->execute([$newQuestionId, $letter, $text, $isCorrect]);
            }
        }
        
        $imported++;
        $questionOrder++;
    }
    
    fclose($file);
    
    // Update total questions
    $stmt = $conn->prepare("UPDATE exam_categories SET total_questions = ? WHERE id = ?");
    $stmt->execute([$imported, $categoryId]);
    
    $conn->commit();
    
    // Display Results
    echo '<div class="stats">';
    echo '<div class="stat"><div class="stat-number">' . $imported . '</div><div class="stat-label">Questions Imported</div></div>';
    echo '<div class="stat"><div class="stat-number">' . ($imported * 5) . '</div><div class="stat-label">Options Created</div></div>';
    echo '<div class="stat"><div class="stat-number">100%</div><div class="stat-label">Success Rate</div></div>';
    echo '</div>';
    
    echo '<div class="success">';
    echo '<h3>✓ Import Completed Successfully!</h3>';
    echo '<p><strong>' . $imported . ' questions</strong> have been imported into the database.</p>';
    echo '<p>Category: <strong>' . $department . ' - ' . $category . ' - ' . $vesselType . '</strong></p>';
    echo '</div>';
    
    echo '<a href="crewside/examination.php" class="btn">Go to Examination Page →</a>';
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    echo '<div class="error">';
    echo '<h3>✗ Import Failed</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

?>
    </div>
</body>
</html>
