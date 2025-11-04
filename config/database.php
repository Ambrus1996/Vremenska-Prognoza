<?php

class Database
{
    private $conn;

    public function connect()
    {
        $this->conn = null;

        // Ucitaj .env fajl ako nije ucitan
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->safeLoad();
        }

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
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }
}
