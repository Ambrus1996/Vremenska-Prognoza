<?php 
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
?>
<head>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<?php

require_once __DIR__ . '/../vendor/autoload.php';


// Ucitavanje .env 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..'); 
$dotenv->load();

require_once __DIR__ . '/../app/models/WeatherAPI.php'; 
require_once __DIR__ . '/../app/controllers/WeatherController.php'; 

$weatherAPI = new \App\Models\WeatherAPI($_ENV['OPENWEATHERMAP_API_KEY']);
$weatherController = new \App\Controllers\WeatherController($weatherAPI);

$cityName = $_GET['city'] ?? null;

$weatherController->showWeather($cityName);
?>
<body>
    <script src="../assets/js/modal.js"></script>
    <script src="../assets/js/darkmode.js"></script>
</body>