<?php

namespace App\Models;

use PDO;
use PDOException;

class SearchLogger
{
    private $pdo = null;

    public function __construct()
    {
        $db   = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'weather';
        $user = $_ENV['MYSQL_USER']     ?? getenv('MYSQL_USER')     ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?? null;
        $pass = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? null;
        
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'weather_db';
        $port = '3306';
        $charset = 'utf8mb4';

        if (!$db || !$user) {
            error_log("SearchLogger Error: Database credentials missing.");
            return;
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("SearchLogger Connection Failed: " . $e->getMessage());
        }
    }

    public function logSearch($cityName, $temp, $description)
    {
        if (!$this->pdo) return;

        $clientIp = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        $sql = "INSERT INTO search_history (city_name, temperature, description, client_ip) VALUES (?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cityName, $temp, $description, $clientIp]);
        } catch (PDOException $e) {
            error_log("SearchLogger Insert Failed: " . $e->getMessage());
        }
    }
}