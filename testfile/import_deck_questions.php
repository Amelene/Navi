<?php
/**
 * CSV Import Script for Deck Management Questions
 * 
 * This script imports questions from deck-management-drycargo.csv into the database
 * Run this file once to populate the questions table
 */

require_once '../config/database.php';

// Set execution time limit for large imports
set_time_limit(300);

// Initialize database
$db = Database::getInstance();

// Configuration
$csvFile = 'crewside/csv/deck-management-drycargo.csv';
$examCategory = [
    'department' => 'DECK',
    'category' => 'MANAGEMENT',
    'vessel_type' => 'DRY CARGO'
];

// HTML Output styling
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Deck Questions</title>
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
            max-width: 900px;
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
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .success h3 {
            color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .error h3 {
            color: #dc3545;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .warning h3 {
            color: #ffc107;
        }
        .progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            transition: width 0.3s ease;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e9ecef;
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        .log {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 20px;
        }
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .log-success {
            color: #28a745;
        }
        .log-error {
            color: #dc3545;
        }
        .log-info {
            color: #17a2b8;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Import Deck Management Questions</h1>
            <p>Importing questions from CSV to MySQL Database</p>
        </div>
        <div class="content">
<?php

try {
    // Check if CSV file exists
    if (!file_exists($csvFile)) {
        throw new Exception("CSV file not found: $csvFile");
    }

    echo '<div class="info-box">';
    echo '<h3>📁 File Information</h3>';
    echo '<p><strong>File:</strong> ' . $csvFile . '</p>';
    echo '<p><strong>Size:</strong> ' . number_format(filesize($csvFile)) . ' bytes</p>';
    echo '</div>';

    // Get or create exam category
    echo '<div class="info-box">';
    echo '<h3>📋 Exam Category</h3>';
    
    $categoryQuery = "SELECT id FROM exam_categories 
                      WHERE department = ? AND category = ? AND vessel_type = ?";
    $category = $db->fetchOne($categoryQuery, [
        $examCategory['department'],
        $examCategory['category'],
        $examCategory['vessel_type']
    ]);

    if (!$category) {
        $insertCategory = "INSERT INTO exam_categories (department, category, vessel_type, description) 
                          VALUES (?, ?, ?, ?)";
        $db->execute($insertCategory, [
            $examCategory['department'],
            $examCategory['category'],
            $examCategory['vessel_type'],
            'Deck Management examination for Dry Cargo vessels'
        ]);
        $categoryId = $db->lastInsertId();
        echo '<p class="log-success">✓ Created new exam category (ID: ' . $categoryId . ')</p>';
    } else {
        $categoryId = $category['id'];
        echo '<p class="log-info">ℹ Using existing exam category (ID: ' . $categoryId . ')</p>';
    }
    
    echo '<p><strong>Department:</strong> ' . $examCategory['department'] . '</p>';
    echo '<p><strong>Category:</strong> ' . $examCategory['category'] . '</p>';
    echo '<p><strong>Vessel Type:</strong> ' . $examCategory['vessel_type'] . '</p>';
    echo '</div>';

    // Open and read CSV file
    $file = fopen($csvFile, 'r');
    if (!$file) {
        throw new Exception("Unable to open CSV file");
    }

    // Skip header row
    $header = fgetcsv($file);
    
    // Initialize counters
    $totalRows = 0;
    $successCount = 0;
    $errorCount = 0;
    $logs = [];

    // Start transaction
    $db->beginTransaction();

    // Clear existing questions for this category (optional - comment out if you want to keep existing)
    $deleteOptions = "DELETE qo FROM question_options qo 
                      INNER JOIN questions q ON qo.question_id = q.id 
                      WHERE q.exam_category_id = ?";
    $db->execute($deleteOptions, [$categoryId]);
    
    $deleteQuestions = "DELETE FROM questions WHERE exam_category_id = ?";
    $db->execute($deleteQuestions, [$categoryId]);
    
    $logs[] = ['type' => 'info', 'message' => 'Cleared existing questions for this category'];

    // Process each row
    $questionOrder = 1;
    
    while (($row = fgetcsv($file)) !== false) {
        $totalRows++;
        
        try {
            // Extract data from CSV
            $questionText = trim($row[0] ?? '');
            $optionA = trim($row[1] ?? '');
            $optionB = trim($row[2] ?? '');
            $optionC = trim($row[3] ?? '');
            $optionD = trim($row[4] ?? '');
            $optionE = trim($row[5] ?? '');

            // Skip empty rows
            if (empty($questionText)) {
                continue;
            }

            // Extract Question ID if present
            $questionId = null;
            if (preg_match('/Question ID:\s*([A-Z]+)/i', $questionText, $matches)) {
                $questionId = $matches[1];
            } elseif (preg_match('/^Question:\s*\*?(.+)/i', $questionText, $matches)) {
                $questionText = trim($matches[1]);
            }

            // Clean question text (remove Question ID prefix if present)
            $questionText = preg_replace('/^Question ID:\s*[A-Z]+\s*/i', '', $questionText);
            $questionText = preg_replace('/^Question:\s*\*?/i', '', $questionText);

            // Determine correct answer (look for asterisk)
            $correctAnswer = null;
            $options = [
                'A' => $optionA,
                'B' => $optionB,
                'C' => $optionC,
                'D' => $optionD,
                'E' => $optionE
            ];

            // Check each option for asterisk
            foreach ($options as $letter => $text) {
                if (strpos($text, '*') !== false) {
                    $correctAnswer = $letter;
                    // Remove asterisk from option text
                    $options[$letter] = str_replace('*', '', $text);
                }
                // Clean option text (remove letter prefix like "A:", "A.", "A ")
                $options[$letter] = preg_replace('/^[A-E][\:\.\s]+/i', '', $options[$letter]);
                $options[$letter] = trim($options[$letter]);
            }

            // If no asterisk found, try to detect from option text format
            if (!$correctAnswer) {
                foreach ($options as $letter => $text) {
                    // Some CSVs mark correct answer differently
                    if (preg_match('/^\*/', $text)) {
                        $correctAnswer = $letter;
                        $options[$letter] = ltrim($text, '*');
                    }
                }
            }

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
                    $db->execute($insertOption, [$newQuestionId, $letter, $text, $isCorrect]);
                }
            }

            $successCount++;
            $questionOrder++;
            
            $logs[] = [
                'type' => 'success',
                'message' => "Question #$questionOrder: " . substr($questionText, 0, 60) . "..." . 
                            ($correctAnswer ? " [Correct: $correctAnswer]" : " [No correct answer marked]")
            ];

        } catch (Exception $e) {
            $errorCount++;
            $logs[] = [
                'type' => 'error',
                'message' => "Row $totalRows: " . $e->getMessage()
            ];
        }
    }

    fclose($file);

    // Update total questions count in exam_categories
    $updateCategory = "UPDATE exam_categories SET total_questions = ? WHERE id = ?";
    $db->execute($updateCategory, [$successCount, $categoryId]);

    // Commit transaction
    $db->commit();

    // Display statistics
    echo '<div class="stats">';
    echo '<div class="stat-card">';
    echo '<div class="number">' . $totalRows . '</div>';
    echo '<div class="label">Total Rows</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="number" style="color: #28a745;">' . $successCount . '</div>';
    echo '<div class="label">Successfully Imported</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="number" style="color: #dc3545;">' . $errorCount . '</div>';
    echo '<div class="label">Errors</div>';
    echo '</div>';
    echo '</div>';

    // Progress bar
    $percentage = $totalRows > 0 ? round(($successCount / $totalRows) * 100) : 0;
    echo '<div class="progress">';
    echo '<div class="progress-bar" style="width: ' . $percentage . '%;">' . $percentage . '%</div>';
    echo '</div>';

    // Success message
    echo '<div class="info-box success">';
    echo '<h3>✓ Import Completed Successfully!</h3>';
    echo '<p>' . $successCount . ' questions have been imported into the database.</p>';
    echo '<p>You can now use these questions in the examination system.</p>';
    echo '</div>';

    // Display logs
    if (!empty($logs)) {
        echo '<h3 style="margin-top: 30px; color: #333;">Import Log:</h3>';
        echo '<div class="log">';
        foreach ($logs as $log) {
            $class = 'log-' . $log['type'];
            echo '<div class="log-entry ' . $class . '">';
            echo htmlspecialchars($log['message']);
            echo '</div>';
        }
        echo '</div>';
    }

    echo '<div style="text-align: center;">';
    echo '<a href="crewside/examination.php" class="btn">Go to Examination Page</a>';
    echo '</div>';

} catch (Exception $e) {
    // Rollback on error
    if ($db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    
    echo '<div class="info-box error">';
    echo '<h3>✗ Import Failed</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
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
