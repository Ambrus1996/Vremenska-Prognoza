<?php

namespace App\Models;

use PDO;
use PDOException;

class SearchLogger
{
    private $pdo;

   public function __construct()
    {
        $host = 'weather_db'; 
        
        $db   = getenv('MYSQL_DATABASE');
        $user = getenv('MYSQL_USER');
        $pass = getenv('MYSQL_PASSWORD');
        $charset = 'utf8mb4';

        if (!$db || !$user) {
            die("❌ ERROR: PHP ne vidi .env varijable! Proveri docker-compose i .env fajl.");
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
            // OVO JE PROMENA: Umesto error_log, ispisujemo na ekran!
            die("❌ GRESKA SA BAZOM: " . $e->getMessage());
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
            error_log("Failed to log search: " . $e->getMessage());
        }
    }
}