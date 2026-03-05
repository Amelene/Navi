<?php
/**
 * DECK Department Only - CSV Import Script
 * Imports: Management, Operational, Support for Dry Cargo & Oil Tanker
 */

require_once '../config/database.php';
set_time_limit(600);
$db = Database::getInstance();

$csvFiles = [
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
    [
        'file' => '../crewside/csv/DECK OIL TANKER/deck mng-oil tankercsv.csv',
        'department' => 'DECK',
        'category' => 'MANAGEMENT',
        'vessel_type' => 'OIL TANKER',
        'description' => 'Deck Management examination for Oil Tanker vessels'
    ],
    [
        'file' => '../crewside/csv/DECK OIL TANKER/deck op-oil tankercsv.csv',
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
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import DECK Questions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .category-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #1e3c72;
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .category-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .category-badge {
            background: #1e3c72;
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
        .success .category-badge { background: #28a745; }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .error .category-badge { background: #dc3545; }
        .category-details {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
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
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚓ Import DECK Department Questions</h1>
            <p>Management, Operational, Support - Dry Cargo & Oil Tanker</p>
        </div>
        <div class="content">
<?php
$totalCategories = count($csvFiles);
$successCategories = 0;
$errorCategories = 0;
$totalQuestions = 0;

try {
    $db->beginTransaction();
    
    echo '<h2 style="color: #333; margin-bottom: 20px;">📊 Import Progress</h2>';
    
    foreach ($csvFiles as $csvConfig) {
        try {
            if (!file_exists($csvConfig['file'])) {
                throw new Exception("File not found: " . $csvConfig['file']);
            }
            
            // Get or create category
            $categoryQuery = "SELECT id FROM exam_categories 
                            WHERE department = ? AND category = ? AND vessel_type = ?";
            $category = $db->fetchOne($categoryQuery, [
                $csvConfig['department'],
                $csvConfig['category'],
                $csvConfig['vessel_type']
            ]);
            
            if (!$category) {
                $insertCategory = "INSERT INTO exam_categories (department, category, vessel_type, description, time_limit, passing_score) 
                                  VALUES (?, ?, ?, ?, 30, 60)";
                $db->execute($insertCategory, [
                    $csvConfig['department'],
                    $csvConfig['category'],
                    $csvConfig['vessel_type'],
                    $csvConfig['description']
                ]);
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $category['id'];
                // Update passing score to 60%
                $db->execute("UPDATE exam_categories SET passing_score = 60 WHERE id = ?", [$categoryId]);
            }
            
            // Clear existing questions
            $db->execute("DELETE qo FROM question_options qo 
                         INNER JOIN questions q ON qo.question_id = q.id 
                         WHERE q.exam_category_id = ?", [$categoryId]);
            $db->execute("DELETE FROM questions WHERE exam_category_id = ?", [$categoryId]);
            
            // Open CSV
            $file = fopen($csvConfig['file'], 'r');
            fgetcsv($file); // Skip header
            
            $questionCount = 0;
            $questionOrder = 1;
            
            while (($row = fgetcsv($file)) !== false) {
                $questionText = trim($row[0] ?? '');
                if (empty($questionText)) continue;
                
                $optionA = trim($row[1] ?? '');
                $optionB = trim($row[2] ?? '');
                $optionC = trim($row[3] ?? '');
                $optionD = trim($row[4] ?? '');
                $optionE = trim($row[5] ?? '');
                $correctAnswer = strtoupper(trim($row[6] ?? ''));
                
                // Extract Question ID
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
            
            // Update total questions
            $db->execute("UPDATE exam_categories SET total_questions = ? WHERE id = ?", [$questionCount, $categoryId]);
            
            echo '<div class="category-section success">';
            echo '<div class="category-header">';
            echo '<div class="category-title">' . $csvConfig['category'] . ' - ' . $csvConfig['vessel_type'] . '</div>';
            echo '<div class="category-badge">✓ ' . $questionCount . ' Questions</div>';
            echo '</div>';
            echo '<div class="category-details">';
            echo '<strong>File:</strong> ' . basename($csvConfig['file']) . '<br>';
            echo '<strong>Status:</strong> Successfully imported';
            echo '</div>';
            echo '</div>';
            
            $successCategories++;
            $totalQuestions += $questionCount;
            
        } catch (Exception $e) {
            echo '<div class="category-section error">';
            echo '<div class="category-header">';
            echo '<div class="category-title">' . $csvConfig['category'] . ' - ' . $csvConfig['vessel_type'] . '</div>';
            echo '<div class="category-badge">✗ Failed</div>';
            echo '</div>';
            echo '<div class="category-details">';
            echo '<strong>Error:</strong> ' . $e->getMessage();
            echo '</div>';
            echo '</div>';
            $errorCategories++;
        }
    }
    
    $db->commit();
    
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
    
    echo '<div style="text-align: center;">';
    echo '<a href="../crewside/index.php" class="btn">Go to Examination System</a>';
    echo '</div>';
    
} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->rollback();
    }
    echo '<div class="category-section error">';
    echo '<div class="category-header"><div class="category-title">✗ Import Failed</div></div>';
    echo '<div class="category-details"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div>';
}
?>
        </div>
    </div>
</body>
</html>
