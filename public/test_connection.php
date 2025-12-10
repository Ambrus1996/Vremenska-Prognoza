<?php
// public/test_connection.php

// Enable error reporting to display issues directly in the browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Database Connection Test (Diagnostic Probe)</h1>";

// Connection Parameters (Matching the Docker Setup)
$host = 'weather_db';
$port = 3306;
// Fallback values are used if ENV vars are missing
$user = getenv('MYSQL_USER') ?: 'weather_user'; 
$pass = getenv('MYSQL_PASSWORD') ?: 'weather_pass';
$dbname = getenv('MYSQL_DATABASE') ?: 'weather';

echo "Target Host: <b>$host</b><br>";
echo "Target Port: <b>$port</b><br>";
echo "User: <b>$user</b><br>";

// 1. DNS RESOLUTION CHECK
// Check if PHP container can resolve the database container name to an IP address.
$ip = gethostbyname($host);
echo "Resolved IP for '$host': <b>$ip</b><br>";

if ($ip == $host) {
    // If gethostbyname returns the hostname itself, resolution failed.
    echo "<h3 style='color:red'>❌ DNS ERROR: Cannot resolve hostname '$host'.</h3>";
    echo "Possible cause: Docker containers are not on the same network.<br>";
} else {
    echo "<h3 style='color:green'>✅ DNS SUCCESS: Hostname resolves correctly.</h3>";
}

// 2. CONNECTION ATTEMPT
echo "<hr><h3>Attempting PDO Connection...</h3>";

try {
    // Construct DSN with explicit port to force TCP
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 5]);
    
    echo "<h2 style='color:green'>✅ CONNECTION SUCCESSFUL!</h2>";
    echo "Database is reachable and credentials are valid.<br>";
    
    // Optional: Check if the table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'search_history'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'search_history' found: <b style='color:green'>YES</b>";
    } else {
        echo "Table 'search_history' found: <b style='color:red'>NO</b>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ CONNECTION FAILED</h2>";
    echo "<strong>Error Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
}
?>