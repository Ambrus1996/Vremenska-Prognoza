<?php

namespace App\Models;

use PDO;
use PDOException;

class SearchLogger
{
    private $pdo = null;

    public function __construct()
    {
        $host = 'weather_db';
        
        // Pametno trazenje varijabli
        $db   = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? $_SERVER['MYSQL_DATABASE'] ?? null;
        $user = $_ENV['MYSQL_USER']     ?? getenv('MYSQL_USER')     ?? $_SERVER['MYSQL_USER']     ?? null;
        $pass = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? $_SERVER['MYSQL_PASSWORD'] ?? null;
        
        $charset = 'utf8mb4';

        if (!$db || !$user) {
            // Tiho zapisi gresku u log, ne rusi sajt!
            error_log("SearchLogger Error: Database credentials missing.");
            return;
        }

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        
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