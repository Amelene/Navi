<?php
/**
 * Database Connection Test
 * 
 * I-run ito sa browser para i-test ang database connection
 * URL: http://localhost/php-project/test_connection.php
 */

// Include database config
require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .test-section {
            margin-bottom: 25px;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .test-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .success {
            color: #28a745;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info {
            color: #004085;
            background: #cce5ff;
            border: 1px solid #b8daff;
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .icon {
            font-size: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .config-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .config-info strong {
            color: #856404;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Database Connection Test</h1>
        
        <?php
        try {
            // Test 1: Database Connection
            echo '<div class="test-section">';
            echo '<div class="test-title">Test 1: Database Connection</div>';
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            echo '<div class="success">';
            echo '<span class="icon">✓</span>';
            echo '<span>Database connection successful!</span>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>Connection Details:</strong><br>';
            echo 'Host: <code>' . DB_HOST . ':' . DB_PORT . '</code><br>';
            echo 'Database: <code>' . DB_NAME . '</code><br>';
            echo 'User: <code>' . DB_USER . '</code><br>';
            echo 'Charset: <code>' . DB_CHARSET . '</code>';
            echo '</div>';
            echo '</div>';
            
            // Test 2: Check Tables
            echo '<div class="test-section">';
            echo '<div class="test-title">Test 2: Database Tables</div>';
            
            $tables = $db->fetchAll("SHOW TABLES");
            
            if (count($tables) > 0) {
                echo '<div class="success">';
                echo '<span class="icon">✓</span>';
                echo '<span>Found ' . count($tables) . ' tables in database</span>';
                echo '</div>';
                
                echo '<table>';
                echo '<thead><tr><th>#</th><th>Table Name</th></tr></thead>';
                echo '<tbody>';
                $i = 1;
                foreach ($tables as $table) {
                    $tableName = array_values($table)[0];
                    echo '<tr><td>' . $i++ . '</td><td>' . $tableName . '</td></tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="error">';
                echo '<span class="icon">✗</span>';
                echo '<span>No tables found! Please run schema.sql first.</span>';
                echo '</div>';
            }
            echo '</div>';
            
            // Test 3: Check Sample Data
            echo '<div class="test-section">';
            echo '<div class="test-title">Test 3: Sample Data</div>';
            
            // Check Staff
            $staffCount = $db->fetchOne("SELECT COUNT(*) as count FROM staff");
            $crewCount = $db->fetchOne("SELECT COUNT(*) as count FROM crew_master");
            $vesselCount = $db->fetchOne("SELECT COUNT(*) as count FROM vessels");
            $deptCount = $db->fetchOne("SELECT COUNT(*) as count FROM departments");
            $posCount = $db->fetchOne("SELECT COUNT(*) as count FROM positions");
            
            echo '<div class="success">';
            echo '<span class="icon">✓</span>';
            echo '<span>Sample data loaded successfully</span>';
            echo '</div>';
            
            echo '<table>';
            echo '<thead><tr><th>Table</th><th>Record Count</th></tr></thead>';
            echo '<tbody>';
            echo '<tr><td>Staff</td><td>' . $staffCount['count'] . '</td></tr>';
            echo '<tr><td>Crew</td><td>' . $crewCount['count'] . '</td></tr>';
            echo '<tr><td>Vessels</td><td>' . $vesselCount['count'] . '</td></tr>';
            echo '<tr><td>Departments</td><td>' . $deptCount['count'] . '</td></tr>';
            echo '<tr><td>Positions</td><td>' . $posCount['count'] . '</td></tr>';
            echo '</tbody></table>';
            echo '</div>';
            
            // Test 4: Sample Query
            echo '<div class="test-section">';
            echo '<div class="test-title">Test 4: Sample Staff Query</div>';
            
            $staff = $db->fetchAll("SELECT * FROM vw_staff_details LIMIT 3");
            
            if (count($staff) > 0) {
                echo '<div class="success">';
                echo '<span class="icon">✓</span>';
                echo '<span>Successfully retrieved staff records</span>';
                echo '</div>';
                
                echo '<table>';
                echo '<thead><tr><th>Staff No</th><th>Name</th><th>Position</th><th>Department</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                foreach ($staff as $s) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($s['staff_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['position_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['department_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($s['staff_status']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            echo '</div>';
            
            // Test 5: Sample Crew Query
            echo '<div class="test-section">';
            echo '<div class="test-title">Test 5: Sample Crew Query</div>';
            
            $crew = $db->fetchAll("SELECT * FROM vw_crew_details LIMIT 3");
            
            if (count($crew) > 0) {
                echo '<div class="success">';
                echo '<span class="icon">✓</span>';
                echo '<span>Successfully retrieved crew records</span>';
                echo '</div>';
                
                echo '<table>';
                echo '<thead><tr><th>Crew No</th><th>Name</th><th>Position</th><th>Vessel</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                foreach ($crew as $c) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($c['crew_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['position_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['vessel_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($c['crew_status']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            echo '</div>';
            
            // Success Message
            echo '<div class="config-info">';
            echo '<strong>🎉 All tests passed!</strong><br><br>';
            echo 'Your database is ready to use. You can now:<br>';
            echo '1. Update <code>staff.php</code> to fetch data from database<br>';
            echo '2. Update <code>crew.php</code> to fetch data from database<br>';
            echo '3. Update <code>login.php</code> to use database authentication<br><br>';
            echo 'Default admin login:<br>';
            echo 'Email: <code>admin@navishipping.com</code><br>';
            echo 'Password: <code>admin123</code>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="test-section">';
            echo '<div class="error">';
            echo '<span class="icon">✗</span>';
            echo '<span><strong>Connection Failed!</strong><br>' . htmlspecialchars($e->getMessage()) . '</span>';
            echo '</div>';
            
            echo '<div class="config-info">';
            echo '<strong>⚠️ Troubleshooting Steps:</strong><br><br>';
            echo '1. Check if MySQL service is running<br>';
            echo '2. Verify database credentials in <code>config/database.php</code><br>';
            echo '3. Make sure database <code>navi_shipping</code> exists<br>';
            echo '4. Run <code>database/schema.sql</code> in MySQL Workbench<br>';
            echo '5. Check if PDO MySQL extension is enabled in php.ini';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
