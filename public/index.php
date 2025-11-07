<?php
// File: public/index.php

// 1. Load Composer autoloader (MUST be first)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. [FIX - Medium] Load .env variables IMMEDIATELY at the start.
// This ensures all $_ENV/getenv() variables are available for the entire application.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad(); // safeLoad() doesn't throw an error if .env is missing (e.g., in production/CI)
} catch (\Exception $e) {
    // Log the error if Dotenv fails for other reasons
    error_log("Could not load .env file: " . $e->getMessage());
}

// 3. [FIX - High] Production-safe error reporting.
// NEVER display errors to the user in a production environment.
// Log them to the server/Docker log instead.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 4. Load application dependencies (manual requires for now)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/WeatherAPI.php';
require_once __DIR__ . '/../app/controllers/WeatherController.php';

// 5. [FIX - Medium] Guard against missing API key
// Read from environment (getenv() is generally more reliable)
$apiKey = getenv('OPENWEATHERMAP_API_KEY');
if (empty($apiKey)) {
    // This is a critical failure. Stop execution with a clean error.
    // The *real* error is in the log, not shown to the user.
    die("Application is not configured correctly. Please contact administrator.");
}

// 6. Dependency Injection
$weatherAPI = new \App\Models\WeatherAPI($apiKey);
$weatherController = new \App\Controllers\WeatherController($weatherAPI);

// 7. Basic Routing
$cityName = $_GET['city'] ?? null;
$weatherController->showWeather($cityName);

// Note: <head> and <body> tags were removed from here.
// This file is a PHP controller/router, not an HTML layout.
// The HTML layout should be self-contained in the view file.