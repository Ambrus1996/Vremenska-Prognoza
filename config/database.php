<?php
// File: config/database.php

class Database
{
    private $conn;

    public function connect()
    {
        $this->conn = null;

        // [FIX - Medium] Removed Dotenv loading from here.
        // It's now loaded centrally and correctly in public/index.php.

        $host = getenv('DB_HOST') ?: 'weather_db';
        $db_name = getenv('DB_NAME') ?: 'weather';
        $username = getenv('DB_USER') ?: 'weather_user';
        $password = getenv('DB_PASS') ?: 'weather_pass';

        try {
            $dsn = "mysql:host={$host};dbname={$db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            
            // [FIX - High] NEVER echo the raw exception message to the user!
            // It leaks database credentials and infrastructure details.
            
            // 1. Log the *real* error for the DevOps/L2 team (you) to see
            error_log("Database Connection Error: " . $e->getMessage());
            
            // 2. Throw a generic, safe exception for the application to handle
            throw new \PDOException("A database connection error occurred.", $e->getCode());
        }

        return $this->conn;
    }
}