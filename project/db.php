<?php
/**
 * Database Connection Configuration
 * People Say So - MySQL Database Connection
 */

// Database credentials - loaded from environment or config file
// For production, set these as environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'sql103.hstn.me');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'mseet_41580932_p');
define('DB_USER', getenv('DB_USER') ?: 'mseet_41580932');
define('DB_PASS', getenv('DB_PASS') ?: 'jXqvPhfVnCWD');

// PDO connection with error handling
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Datenbankverbindung fehlgeschlagen");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
