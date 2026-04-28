<?php
// /opt/panel/www/classes/Database.php

class Database {
    private static $instance = null;
    private $pdo;

    // Private constructor to prevent direct instantiation
    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Fail hard on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return arrays, not objects
            PDO::ATTR_EMULATE_PREPARES   => false,                  // True prepared statements
        ];

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $e) {
            // In a production environment, log this to a file instead of echoing
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    // Get the single instance of the database
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Expose the PDO object for queries
    public function getConnection() {
        return $this->pdo;
    }
}