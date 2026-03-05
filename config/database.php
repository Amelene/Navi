<?php
/**
 * Database Configuration
 * 
 * I-update ang values based sa iyong MySQL Workbench settings
 */

// Database Configuration
define('DB_HOST', 'localhost');        // MySQL server
define('DB_PORT', '3306');             // MySQL port (default: 3306)
define('DB_NAME', 'navi_shipping');    // Database name
define('DB_USER', 'root');             // MySQL username
define('DB_PASS', '');                 // MySQL password (blank for XAMPP default)
define('DB_CHARSET', 'utf8mb4');       // Character set

// Error Reporting (set to false in production)
define('DB_DEBUG', true);

/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (DB_DEBUG) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact administrator.");
            }
        }
    }
    
    /**
     * Get Database Instance (Singleton Pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO Connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute Query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DB_DEBUG) {
                die("Query Error: " . $e->getMessage() . "<br>SQL: " . $sql);
            } else {
                die("An error occurred. Please try again.");
            }
        }
    }
    
    /**
     * Fetch All Records
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch Single Record
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Execute Query (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            if (DB_DEBUG) {
                throw new Exception("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            } else {
                throw new Exception("An error occurred. Please try again.");
            }
        }
    }
    
    /**
     * Get Last Insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin Transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit Transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback Transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database instance
 */
function getDB() {
    return Database::getInstance();
}
?>
