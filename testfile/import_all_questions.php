<?php
/**
 * Comprehensive CSV Import Script for All Exam Questions
 * 
 * This script imports ALL questions from CSV files into the database
 * Supports: DECK (Dry Cargo & Oil Tanker), ENGINE, and STEWARD (Cook)
 */

require_once '../config/database.php';

// Set execution time limit for large imports
set_time_limit(600);

// Initialize database
$db = Database::getInstance();

// Define all CSV files to import
$csvFiles = [
    // DECK - DRY CARGO
    [
        'file' => '../crewside/csv/DECK DRY CARGO/deck mng-drycargo.csv',
        'department' => 'DECK',
        'category' => 'MANAGEMENT',
        'vessel_type' => 'DRY CARGO',
        'description' => 'Deck Management examination for Dry Cargo vessels'
    ],
    [
        'file' => '../crewside/csv/DECK DRY CARGO/deck op-dry cargo.csv',
        'department' => 'DECK',
        'category' => 'OPERATIONAL',
        'vessel_type' => 'DRY CARGO',
        'description' => 'Deck Operational examination for Dry Cargo vessels'
    ],
    [
        'file' => '../crewside/csv/DECK DRY CARGO/deck sup-dry cargo.csv',
        'department' => 'DECK',
        'category' => 'SUPPORT',
        'vessel_type' => 'DRY CARGO',
        'description' => 'Deck Support examination for Dry Cargo vessels'
    ],
    
    // DECK - OIL TANKER
    [
        'file' => '../crewside/csv/DECK OIL TANKER/deck mng-oil tanker.csv',
        'department' => 'DECK',
        'category' => 'MANAGEMENT',
        'vessel_type' => 'OIL TANKER',
        'description' => 'Deck Management examination for Oil Tanker vessels'
    ],
    [
        'file' => '../crewside/csv/DECK OIL TANKER/deck op-oil tanker.csv',
        'department' => 'DECK',
        'category' => 'OPERATIONAL',
        'vessel_type' => 'OIL TANKER',
        'description' => 'Deck Operational examination for Oil Tanker vessels'
    ],
    [
        'file' => '../crewside/csv/DECK OIL TANKER/deck sup-oil tanker.csv',
        'department' => 'DECK',
        'category' => 'SUPPORT',
        'vessel_type' => 'OIL TANKER',
        'description' => 'Deck Support examination for Oil Tanker vessels'
    ],
    
    // ENGINE
    [
        'file' => '../crewside/csv/ENGINE/Engine mng.csv',
        'department' => 'ENGINE',
        'category' => 'MANAGEMENT',
        'vessel_type' => 'GENERAL',
        'description' => 'Engine Management examination'
    ],
    [
        'file' => '../crewside/csv/ENGINE/Engine Op.csv',
        'department' => 'ENGINE',
        'category' => 'OPERATIONAL',
        'vessel_type' => 'GENERAL',
        'description' => 'Engine Operational examination'
    ],
    [
        'file' => '../crewside/csv/ENGINE/Engine Sup.csv',
        'department' => 'ENGINE',
        'category' => 'SUPPORT',
        'vessel_type' => 'GENERAL',
        'description' => 'Engine Support examination'
    ],
    
    // STEWARD - COOK
    [
        'file' => '../crewside/csv/COOK/Cater.csv',
        'department' => 'STEWARD',
        'category' => 'COOK',
        'vessel_type' => 'GENERAL',
        'description' => 'Cook/Catering examination for Steward department'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import All Exam Questions</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .category-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .category-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .category-badge {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .success .category-badge {
            background: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .error .category-badge {
            background: #dc3545;
        }
        .category-details {
            font-size: 14px;
            color: #666;
            line-height: 1.8;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-card .label {
            font-size: 14px;
            opacity: 0.9;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.2s;
            font-size: 16px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .summary-box {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary-box h3 {
            color: #2196F3;
            margin-bottom: 15px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .summary-table th,
        .summary-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .summary-table th {
            background: #2196F3;
            color: white;
            font-weight: bold;
        }
        .summary-table tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚢 Import All Exam Questions</h1>
            <p>Comprehensive import for DECK, ENGINE, and STEWARD departments</p>
        </div>
        <div class="content">
<?php

$totalCategories = count($csvFiles);
$successCategories = 0;
$errorCategories = 0;
$totalQuestions = 0;
$categoryResults = [];

try {
    // Start transaction
    $db->beginTransaction();
    
    echo '<h2 style="color: #333; margin-bottom: 20px;">📊 Import Progress</h2>';
    
    // Process each CSV file
    foreach ($csvFiles as $index => $csvConfig) {
        $categoryResult = [
            'department' => $csvConfig['department'],
            'category' => $csvConfig['category'],
            'vessel_type' => $csvConfig['vessel_type'],
            'file' => basename($csvConfig['file']),
            'questions' => 0,
            'status' => 'pending',
            'message' => ''
        ];
        
        try {
            // Check if file exists
            if (!file_exists($csvConfig['file'])) {
                throw new Exception("File not found: " . $csvConfig['file']);
            }
            
            // Get or create exam category
            $categoryQuery = "SELECT id FROM exam_categories 
                            WHERE department = ? AND category = ? AND vessel_type = ?";
            $category = $db->fetchOne($categoryQuery, [
                $csvConfig['department'],
                $csvConfig['category'],
                $csvConfig['vessel_type']
            ]);
            
            if (!$category) {
                $insertCategory = "INSERT INTO exam_categories (department, category, vessel_type, description, time_limit, passing_score) 
                                  VALUES (?, ?, ?, ?, 30, 70)";
                $db->execute($insertCategory, [
                    $csvConfig['department'],
                    $csvConfig['category'],
                    $csvConfig['vessel_type'],
                    $csvConfig['description']
                ]);
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $category['id'];
            }
            
            // Clear existing questions for this category
            $deleteOptions = "DELETE qo FROM question_options qo 
                            INNER JOIN questions q ON qo.question_id = q.id 
                            WHERE q.exam_category_id = ?";
            $db->execute($deleteOptions, [$categoryId]);
            
            $deleteQuestions = "DELETE FROM questions WHERE exam_category_id = ?";
            $db->execute($deleteQuestions, [$categoryId]);
            
            // Open CSV file
            $file = fopen($csvConfig['file'], 'r');
            if (!$file) {
                throw new Exception("Unable to open file");
            }
            
            // Skip header row
            fgetcsv($file);
            
            $questionCount = 0;
            $questionOrder = 1;
            
            // Process each row
            while (($row = fgetcsv($file)) !== false) {
                // Extract data
                $questionText = trim($row[0] ?? '');
                $optionA = trim($row[1] ?? '');
                $optionB = trim($row[2] ?? '');
                $optionC = trim($row[3] ?? '');
                $optionD = trim($row[4] ?? '');
                $optionE = trim($row[5] ?? '');
                $correctAnswer = strtoupper(trim($row[6] ?? ''));
                
                // Skip empty rows
                if (empty($questionText)) {
                    continue;
                }
                
                // Extract Question ID if present
                $questionId = null;
                if (preg_match('/Question ID:\s*([A-Z]+)/i', $questionText, $matches)) {
                    $questionId = $matches[1];
                }
                
                // Clean question text
                $questionText = preg_replace('/^Question ID:\s*[A-Z]+\s*/i', '', $questionText);
                $questionText = preg_replace('/^Question:\s*\*?/i', '', $questionText);
                $questionText = trim($questionText);
                
                // Clean options
                $options = [
                    'A' => preg_replace('/^[A-E][\:\.\s]+/i', '', $optionA),
                    'B' => preg_replace('/^[A-E][\:\.\s]+/i', '', $optionB),
                    'C' => preg_replace('/^[A-E][\:\.\s]+/i', '', $optionC),
                    'D' => preg_replace('/^[A-E][\:\.\s]+/i', '', $optionD),
                    'E' => preg_replace('/^[A-E][\:\.\s]+/i', '', $optionE)
                ];
                
                // Insert question
                $insertQuestion = "INSERT INTO questions (exam_category_id, question_id, question_text, question_order, status) 
                                  VALUES (?, ?, ?, ?, 'active')";
                $db->execute($insertQuestion, [$categoryId, $questionId, $questionText, $questionOrder]);
                $newQuestionId = $db->lastInsertId();
                
                // Insert options
                foreach ($options as $letter => $text) {
                    if (!empty($text)) {
                        $isCorrect = ($letter === $correctAnswer) ? 1 : 0;
                        $insertOption = "INSERT INTO question_options (question_id, option_letter, option_text, is_correct) 
                                        VALUES (?, ?, ?, ?)";
                        $db->execute($insertOption, [$newQuestionId, $letter, trim($text), $isCorrect]);
                    }
                }
                
                $questionCount++;
                $questionOrder++;
            }
            
            fclose($file);
            
            // Update total questions count
            $updateCategory = "UPDATE exam_categories SET total_questions = ? WHERE id = ?";
            $db->execute($updateCategory, [$questionCount, $categoryId]);
            
            $categoryResult['questions'] = $questionCount;
            $categoryResult['status'] = 'success';
            $categoryResult['message'] = "Successfully imported $questionCount questions";
            
            $successCategories++;
            $totalQuestions += $questionCount;
            
        } catch (Exception $e) {
            $categoryResult['status'] = 'error';
            $categoryResult['message'] = $e->getMessage();
            $errorCategories++;
        }
        
        $categoryResults[] = $categoryResult;
        
        // Display progress
        $statusClass = $categoryResult['status'] === 'success' ? 'success' : 'error';
        echo '<div class="category-section ' . $statusClass . '">';
        echo '<div class="category-header">';
        echo '<div class="category-title">' . $categoryResult['department'] . ' - ' . $categoryResult['category'] . ' (' . $categoryResult['vessel_type'] . ')</div>';
        echo '<div class="category-badge">' . ($categoryResult['status'] === 'success' ? '✓ ' . $categoryResult['questions'] . ' Questions' : '✗ Failed') . '</div>';
        echo '</div>';
        echo '<div class="category-details">';
        echo '<strong>File:</strong> ' . $categoryResult['file'] . '<br>';
        echo '<strong>Status:</strong> ' . $categoryResult['message'];
        echo '</div>';
        echo '</div>';
    }
    
    // Commit transaction
    $db->commit();
    
    // Display summary statistics
    echo '<div class="stats-grid">';
    echo '<div class="stat-card">';
    echo '<div class="number">' . $totalCategories . '</div>';
    echo '<div class="label">Total Categories</div>';
    echo '</div>';
    echo '<div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">';
    echo '<div class="number">' . $successCategories . '</div>';
    echo '<div class="label">Successfully Imported</div>';
    echo '</div>';
    echo '<div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">';
    echo '<div class="number">' . $errorCategories . '</div>';
    echo '<div class="label">Failed</div>';
    echo '</div>';
    echo '<div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">';
    echo '<div class="number">' . $totalQuestions . '</div>';
    echo '<div class="label">Total Questions</div>';
    echo '</div>';
    echo '</div>';
    
    // Display summary table
    echo '<div class="summary-box">';
    echo '<h3>📋 Import Summary</h3>';
    echo '<table class="summary-table">';
    echo '<thead><tr><th>Department</th><th>Category</th><th>Vessel Type</th><th>Questions</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($categoryResults as $result) {
        $statusIcon = $result['status'] === 'success' ? '✓' : '✗';
        $statusColor = $result['status'] === 'success' ? '#28a745' : '#dc3545';
        echo '<tr>';
        echo '<td>' . $result['department'] . '</td>';
        echo '<td>' . $result['category'] . '</td>';
        echo '<td>' . $result['vessel_type'] . '</td>';
        echo '<td>' . $result['questions'] . '</td>';
        echo '<td style="color: ' . $statusColor . '; font-weight: bold;">' . $statusIcon . ' ' . ucfirst($result['status']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    echo '<div style="text-align: center; margin-top: 30px;">';
    echo '<a href="../crewside/index.php" class="btn">Go to Examination System</a>';
    echo '</div>';
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    echo '<div class="category-section error">';
    echo '<div class="category-header">';
    echo '<div class="category-title">✗ Import Failed</div>';
    echo '</div>';
    echo '<div class="category-details">';
    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div>';
    
    echo '<div style="text-align: center;">';
    echo '<a href="javascript:location.reload()" class="btn">Try Again</a>';
    echo '</div>';
}

?>
        </div>
    </div>
</body>
</html>
