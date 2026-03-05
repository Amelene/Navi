<?php
/**
 * Setup Script - Update crew_master table with additional fields
 * Run this once to add new columns to crew_master table
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Crew Master Table</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        h1 { color: #126E82; }
        .success { color: #27ae60; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: #c0392b; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #2c3e50; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Update Crew Master Table</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div class='info'>Starting database update...</div>";
    
    // Read SQL file
    $sqlFile = '../database/update_crew_master_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $conn->exec($statement);
            $successCount++;
            
            // Extract column name from ALTER TABLE statement
            if (preg_match('/ADD COLUMN.*?(\w+)\s+VARCHAR/', $statement, $matches)) {
                echo "<div class='success'>✓ Added column: {$matches[1]}</div>";
            } elseif (preg_match('/ADD COLUMN.*?(\w+)\s+DATE/', $statement, $matches)) {
                echo "<div class='success'>✓ Added column: {$matches[1]}</div>";
            } elseif (preg_match('/ADD COLUMN.*?(\w+)\s+TEXT/', $statement, $matches)) {
                echo "<div class='success'>✓ Added column: {$matches[1]}</div>";
            }
        } catch (PDOException $e) {
            // Ignore "Duplicate column" errors
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<div class='info'>ℹ Column already exists (skipped)</div>";
            } else {
                $errorCount++;
                echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    echo "<br><div class='success'><strong>✓ Database update completed!</strong></div>";
    echo "<div class='info'>Successful operations: $successCount</div>";
    
    if ($errorCount > 0) {
        echo "<div class='error'>Errors encountered: $errorCount</div>";
    }
    
    echo "<br><div class='info'><strong>New columns added to crew_master table:</strong></div>";
    echo "<pre>";
    echo "Emergency Contact:\n";
    echo "  - emergency_name\n";
    echo "  - emergency_relationship\n";
    echo "  - emergency_phone\n\n";
    
    echo "Bank Information:\n";
    echo "  - bank_name\n";
    echo "  - bank_account\n\n";
    
    echo "Government Numbers:\n";
    echo "  - sss_no\n";
    echo "  - philhealth_no\n";
    echo "  - pagibig_no\n";
    echo "  - passport_no\n\n";
    
    echo "Seafarer's Identification:\n";
    echo "  - srn_no\n";
    echo "  - remarks\n";
    echo "  - sirb_no, sirb_expiry\n";
    echo "  - dcoc_no, dcoc_expiry\n";
    echo "  - seamans_book_no, seamans_book_expiry\n\n";
    
    echo "Embarkation Details:\n";
    echo "  - embarkation_date, embarkation_place\n";
    echo "  - disembarkation_date, disembarkation_place, disembarkation_reason\n";
    echo "  - contract_start, contract_end, extension_contract\n";
    echo "</pre>";
    
    echo "<br><div class='success'><strong>✓ You can now use the crew details page with all editable fields!</strong></div>";
    echo "<p><a href='../crew.php' style='color: #126E82; text-decoration: none; font-weight: bold;'>→ Go to Crew Management</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>✗ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";
?>
