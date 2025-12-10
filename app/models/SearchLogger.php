<?php

namespace App\Models;

use PDO;
use PDOException;

class SearchLogger
{
    private $pdo = null;

    public function __construct()
    {
        // 1. HARDCODE HOST AND PORT (Temporary/Debug Fix)
        // We hardcode this to rule out any ENV variable issues regarding the hostname.
        // In the Docker network, the service name is 'weather_db'.
        $host = 'weather_db';
        $port = 3306; 

        // 2. RETRIEVE CREDENTIALS
        // Try $_ENV first, fallback to getenv(), fallback to defaults.
        // We look for both MYSQL_ prefix and DB_ prefix to be safe.
        $db   = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'weather';
        $user = $_ENV['MYSQL_USER']     ?? getenv('MYSQL_USER')     ?? 'weather_user';
        $pass = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? 'weather_pass';
        $charset = 'utf8mb4';

        // 3. CONSTRUCT DSN STRING - CRITICAL PART
        // Explicitly specifying 'port' forces PHP to use TCP/IP connection.
        // This prevents the common "No such file or directory" error (which happens when PHP tries to use UNIX sockets).
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Timeout set to 5 seconds to prevent hanging if the network is down
            PDO::ATTR_TIMEOUT            => 5, 
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log specific details to the container logs for debugging
            error_log("SearchLogger CRITICAL ERROR:");
            error_log("DSN tried: " . $dsn); 
            error_log("User used: " . $user);
            error_log("Error message: " . $e->getMessage());
        }
    }

    public function logSearch($cityName, $temp, $description)
    {
        // If connection failed in constructor, abort logging silently
        if (!$this->pdo) {
            return;
        }

        // Get Client IP (Handle proxy headers if behind Nginx)
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