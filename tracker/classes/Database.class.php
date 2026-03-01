<?php
class Database {
    private $pdo;

    public function __construct() {

        $host = $_SERVER['DB_SERVER'] ?? 'localhost';
        $db   = $_SERVER['DB'] ?? 'zl5660';
        $user = $_SERVER['DB_USER'] ?? 'zl5660';
        $pass = $_SERVER['DB_PASSWORD'] ?? 'YOUR_PASSWORD';
        $charset = "utf8mb4";

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Run SELECT queries and return results
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(); // automatically fetches all rows
    }

    // Run INSERT, UPDATE, DELETE queries
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Optional: get PDO instance if you really need it
    public function getConnection() {
        return $this->pdo;
    }
}