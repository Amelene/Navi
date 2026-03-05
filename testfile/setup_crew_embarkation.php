<?php
/**
 * Setup Script for Crew Embarkation Table
 * Run this file once to create the crew_embarkation table
 */

require_once '../config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Setting up Crew Embarkation Table...</h2>";
    
    // Read SQL file
    $sqlFile = '../database/crew_embarkation_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Execute SQL
    $conn->exec($sql);
    
    echo "<p style='color: green; font-weight: bold;'>✓ Crew embarkation table created successfully!</p>";
    
    // Verify table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'crew_embarkation'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p style='color: green;'>✓ Table 'crew_embarkation' verified in database</p>";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE crew_embarkation");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Go to <a href='../crew.php'>Crew Management</a></li>";
        echo "<li>Click on 'VIEW DETAILS' for any crew member</li>";
        echo "<li>Click 'EDIT' button to edit crew details</li>";
        echo "<li>Add embarkation/disembarkation dates manually</li>";
        echo "<li>Change status (on_board, on_vacation, inactive)</li>";
        echo "<li>Click 'SAVE' to save changes</li>";
        echo "</ol>";
        
    } else {
        echo "<p style='color: red;'>✗ Table verification failed</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Crew Embarkation Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #126E82;
        }
        h3 {
            color: #FF8A4C;
            margin-top: 30px;
        }
        table {
            background: white;
            width: 100%;
            margin: 20px 0;
        }
        th {
            background: #126E82;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 8px;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        ol {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
        }
        li {
            margin: 10px 0;
        }
        a {
            color: #126E82;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
</body>
</html>
