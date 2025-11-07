<?php
// File: public/index.php

// 1. Load Composer autoloader (MUST be first)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. [FIX] Load .env variables IMMEDIATELY at the start.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad(); 
} catch (\Exception $e) {
    error_log("Could not load .env file: " . $e->getMessage());
}

// 3. [FIX] Production-safe error reporting.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// 4. Load manual dependencies (will be fixed by autoloading later)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/WeatherAPI.php';
require_once __DIR__ . '/../app/controllers/WeatherController.php';

// 5. [FIX] Guard against missing API key
$apiKey = getenv('OPENWEATHERMAP_API_KEY');
if (empty($apiKey)) {
    die("Application is not configured correctly. Missing API key.");
}

// 6. Dependency Injection
$weatherAPI = new \App\Models\WeatherAPI($apiKey);
$weatherController = new \App\Controllers\WeatherController($weatherAPI);

// 7. Basic Routing
$cityName = $_GET['city'] ?? null;
$weatherController->showWeather($cityName);