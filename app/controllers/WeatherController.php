<?php
namespace App\Controllers;

use App\Models\City;
use App\Models\SearchLogger;

require_once __DIR__ . '/../models/City.php';
require_once __DIR__ . '/../models/SearchLogger.php';

class WeatherController {
    private $weatherAPI;

    public function __construct($weatherAPI) {
        $this->weatherAPI = $weatherAPI;
    }

    public function showWeather($cityName = null) {
    $cityName    = trim($cityName ?: 'Smederevo');
    $cacheKey    = "weather_cache_" . strtolower(str_replace(' ', '_', $cityName));
    $fromCache   = false;
    
    // Initialize variables to prevent View errors
    $error       = null;
    $weather     = null;
    $forecast    = [];
    $chartPoints = [];
    $airQuality  = null;
    $uvIndex     = null;

    // --- REDIS CACHE CHECK ---
    $redis = new \Redis();
    try {
        // Connect to Redis using the hostname defined in docker-compose
        $redis->connect(getenv('REDIS_HOST') ?: 'redis', 6379);
        
        if ($redis->exists($cacheKey)) {
            // Retrieve data from RAM
            $cachedData = json_decode($redis->get($cacheKey), true);
            
            $weather     = $cachedData['weather'];
            $forecast    = $cachedData['forecast'];
            $chartPoints = $cachedData['chartPoints'];
            $airQuality  = $cachedData['airQuality'];
            $uvIndex     = $cachedData['uvIndex'];
            $fromCache   = true;
        }
    } catch (\Exception $e) {
        // Log error and continue to API as fallback
        error_log("Redis Connection Error: " . $e->getMessage());
    }

    // --- FETCH DATA FROM API IF NOT IN CACHE ---
    if (!$fromCache) {
        try {
            // Fetch current weather
            $weather = $this->weatherAPI->getWeatherByCityName($cityName);

            if (!$weather) {
                throw new \Exception("City not found: " . htmlspecialchars($cityName));
            }

            // Fetch 5-day forecast and chart data
            $fData       = $this->weatherAPI->get5DayForecastByCityName($cityName);
            $forecast    = $fData['daily'] ?? [];
            $chartPoints = $fData['chart']  ?? [];

            // Extract coordinates for AQI and UV calls
            $lat = $weather['lat'] ?? null;
            $lon = $weather['lon'] ?? null;

            if ($lat !== null && $lon !== null) {
                $airQuality = $this->weatherAPI->getAirQuality((float)$lat, (float)$lon);
                $uvIndex    = $this->weatherAPI->getUVIndex((float)$lat, (float)$lon);
            }

            // --- SAVE TO REDIS (TTL: 1800 seconds / 30 minutes) ---
            if ($redis->isConnected()) {
                $dataToCache = [
                    'weather'     => $weather,
                    'forecast'    => $forecast,
                    'chartPoints' => $chartPoints,
                    'airQuality'  => $airQuality,
                    'uvIndex'     => $uvIndex
                ];
                $redis->setex($cacheKey, 1800, json_encode($dataToCache));
            }

            // --- DATABASE LOGGING (Only for new API requests) ---
            $cityModel = new City();
            if (!$cityModel->findByName($cityName)) {
                $cityModel->create($cityName, $weather['country']);
            }

            $logger = new SearchLogger();
            $logger->logSearch(
                $weather['city_name'],
                round($weather['temperature']),
                $weather['description']
            );

        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }

    // ---- Render the View --------------------------------------------
    require __DIR__ . '/../views/weather_view.php';
}
}