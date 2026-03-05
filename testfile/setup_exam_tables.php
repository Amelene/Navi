<?php
/**
 * Setup Exam Tables
 * 
 * This script creates the necessary tables for the examination system
 * Run this file once before importing questions
 */

require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Exam Tables</title>
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
            max-width: 800px;
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
        .table-list {
            list-style: none;
            padding: 0;
        }
        .table-list li {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
        }
        .table-list li:last-child {
            border-bottom: none;
        }
        .table-list li::before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
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
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Setup Exam Tables</h1>
            <p>Creating database tables for examination system</p>
        </div>
        <div class="content">
<?php

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Read SQL file
    $sqlFile = '../database/exam_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $successCount = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            // Skip comments and empty statements
            if (empty(trim($statement))) continue;
            
            $conn->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    if (empty($errors)) {
        echo '<div class="info-box success">';
        echo '<h3>✓ Database Setup Completed Successfully!</h3>';
        echo '<p>All exam tables have been created successfully.</p>';
        echo '</div>';
        
        echo '<div class="info-box">';
        echo '<h3>📋 Created Tables:</h3>';
        echo '<ul class="table-list">';
        echo '<li>exam_categories - Stores exam categories</li>';
        echo '<li>questions - Stores all questions</li>';
        echo '<li>question_options - Stores answer options</li>';
        echo '<li>exam_attempts - Tracks user exam attempts</li>';
        echo '<li>exam_answers - Stores user answers</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="info-box">';
        echo '<h3>📊 Created Views:</h3>';
        echo '<ul class="table-list">';
        echo '<li>vw_questions_with_options - Questions with their options</li>';
        echo '<li>vw_exam_results - Exam results summary</li>';
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="btn-group">';
        echo '<a href="import_deck_questions.php" class="btn">Next: Import Questions →</a>';
        echo '</div>';
        
    } else {
        echo '<div class="info-box error">';
        echo '<h3>✗ Some Errors Occurred</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<div class="btn-group">';
        echo '<a href="javascript:location.reload()" class="btn">Try Again</a>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="info-box error">';
    echo '<h3>✗ Setup Failed</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    
    echo '<div class="btn-group">';
    echo '<a href="javascript:location.reload()" class="btn">Try Again</a>';
    echo '</div>';
}

?>
        </div>
    </div>
</body>
</html>
