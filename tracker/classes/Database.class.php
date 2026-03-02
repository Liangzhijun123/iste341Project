<?php
/**
 * Database Class
 * A wrapper for PHP Data Objects (PDO) to manage MySQL connections.
 * This class abstracts database interactions to prevent SQL injection 
 * and provides centralized error handling.
 */
class Database {
    /**
     * @var PDO $pdo The PHP Data Object instance for the connection.
     */
    private $pdo;

    /**
     * Constructor
     * Automatically establishes a connection to the MySQL server using 
     * server-side environment variables or default local credentials.
     */
    public function __construct() {
        // Configuration pulled from server environment or defaults
        $host = $_SERVER['DB_SERVER'] ?? 'localhost';
        $db   = $_SERVER['DB'] ?? 'zl5660';
        $user = $_SERVER['DB_USER'] ?? 'zl5660';
        $pass = $_SERVER['DB_PASSWORD'] ?? 'YOUR_PASSWORD';
        $charset = "utf8mb4";

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        try {
            // Initialize connection with strict error reporting and associative fetching
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            // Immediate termination if the database is unreachable
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Executes SELECT queries.
     * Uses prepared statements to safely handle user input and return datasets.
     * * @param string $sql The SQL query with placeholders.
     * @param array $params An array of values to bind to the placeholders.
     * @return array An associative array containing all result rows.
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(); 
    }

    /**
     * Executes INSERT, UPDATE, and DELETE queries.
     * * @param string $sql The SQL command with placeholders.
     * @param array $params An array of values to bind to the placeholders.
     * @return bool True on successful execution, False otherwise.
     */
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Provides access to the raw PDO connection.
     * Useful for transactions or advanced database operations.
     * * @return PDO The current PDO connection instance.
     */
    public function getConnection() {
        return $this->pdo;
    }
}