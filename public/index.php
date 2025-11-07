<?php
// File: public/index.php

// 1. Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. [FIX - Medium] Load .env variables IMMEDIATELY at the start.
// This ensures all $_ENV variables are available for the entire application.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad(); // safeLoad() doesn't throw an error if .env is missing (e.g., in production)
} catch (\Exception $e) {
    // Handle exception if Dotenv fails for other reasons
    error_log("Could not load .env file: " . $e->getMessage());
}

// 3. [FIX - High] Production-safe error reporting.
// NEVER display errors to the user. Log them instead (e.g., to Docker logs).
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 4. Load application dependencies (manual requires, will be fixed by autoloading later)
require_once __DIR__ . '/../config/database.php'; // Provides the 'Database' class
require_once __DIR__ . '/../app/models/WeatherAPI.php';
require_once __DIR__ . '/../app/controllers/WeatherController.php';

// 5. [FIX - Medium] Guard against missing API key
// Read from environment (getenv is more reliable than $_ENV)
$apiKey = getenv('OPENWEATHERMAP_API_KEY');
if (empty($apiKey)) {
    // This is a critical failure. Stop execution with a clean error.
    // The error log will contain the details.
    die("Application is not configured correctly. Missing API key.");
}

// 6. Dependency Injection
$weatherAPI = new \App\Models\WeatherAPI($apiKey);
$weatherController = new \App\Controllers\WeatherController($weatherAPI);

// 7. Basic Routing
$cityName = $_GET['city'] ?? null;
$weatherController->showWeather($cityName);