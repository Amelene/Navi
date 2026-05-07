<?php

/**
 * Simple .env loader (no external dependency)
 */
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
loadEnvFile($envPath);

/**
 * Get env value with fallback
 */
function envValue($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

/**
 * Convert env string to bool
 */
function envBool($key, $default = false) {
    $value = envValue($key, null);
    if ($value === null) return $default;

    $normalized = strtolower(trim((string)$value));
    return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
}

/**
 * Auto-detect local environment if APP_ENV is not set
 */
function isLocalEnvironment() {
    $appEnv = strtolower((string) envValue('APP_ENV', ''));
    if ($appEnv !== '') {
        return in_array($appEnv, ['local', 'development', 'dev'], true);
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $cliServerName = $_SERVER['HOSTNAME'] ?? '';
    $isCli = (PHP_SAPI === 'cli');

    $localHosts = ['localhost', '127.0.0.1', '::1'];
    return in_array($host, $localHosts, true)
        || in_array($serverName, $localHosts, true)
        || in_array($cliServerName, $localHosts, true)
        || str_starts_with($host, 'localhost:')
        || $isCli;
}

// Defaults (kept for backward compatibility)
$defaultLocal = [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'navi_shipping',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4',
    'DB_DEBUG' => true,
];

$defaultLive = [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'navizaft_navi',
    'DB_USER' => 'navizaft_navi',
    'DB_PASS' => 'Admin@navi',
    'DB_CHARSET' => 'utf8mb4',
    'DB_DEBUG' => false,
];

$useLocalDefaults = isLocalEnvironment();
$selectedDefaults = $useLocalDefaults ? $defaultLocal : $defaultLive;

// Database Configuration
define('DB_HOST', envValue('DB_HOST', $selectedDefaults['DB_HOST']));        // MySQL server
define('DB_PORT', envValue('DB_PORT', $selectedDefaults['DB_PORT']));        // MySQL port (default: 3306)
define('DB_NAME', envValue('DB_NAME', $selectedDefaults['DB_NAME']));        // Database name
define('DB_USER', envValue('DB_USER', $selectedDefaults['DB_USER']));        // MySQL username
define('DB_PASS', envValue('DB_PASS', $selectedDefaults['DB_PASS']));        // MySQL password
define('DB_CHARSET', envValue('DB_CHARSET', $selectedDefaults['DB_CHARSET'])); // Character set

// Error Reporting (set to false in production)
define('DB_DEBUG', envBool('DB_DEBUG', $selectedDefaults['DB_DEBUG']));

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
            error_log("DB execute error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
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
