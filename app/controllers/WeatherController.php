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
        $error       = null;
        $weather     = null;
        $forecast    = [];
        $chartPoints = [];
        $airQuality  = null;   // NEW
        $uvIndex     = null;   // NEW

        try {
            // ---- Current weather ----------------------------------------
            $weather = $this->weatherAPI->getWeatherByCityName($cityName);

            if (!$weather) {
                throw new \Exception("City not found: " . htmlspecialchars($cityName));
            }

            // ---- Forecast -----------------------------------------------
            $fData       = $this->weatherAPI->get5DayForecastByCityName($cityName);
            $forecast    = $fData['daily'] ?? [];
            $chartPoints = $fData['chart']  ?? [];

            // ---- AQI + UV  (only if we got coordinates) -----------------
            $lat = $weather['lat'] ?? null;
            $lon = $weather['lon'] ?? null;

            if ($lat !== null && $lon !== null) {
                $airQuality = $this->weatherAPI->getAirQuality((float)$lat, (float)$lon);
                $uvIndex    = $this->weatherAPI->getUVIndex((float)$lat, (float)$lon);
            }

            // ---- DB: persist city ---------------------------------------
            $cityModel = new City();
            if (!$cityModel->findByName($cityName)) {
                $cityModel->create($cityName, $weather['country']);
            }

            // ---- Log search ---------------------------------------------
            $logger = new SearchLogger();
            $logger->logSearch(
                $weather['city_name'],
                round($weather['temperature']),
                $weather['description']
            );

        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        // ---- Render view ------------------------------------------------
        require __DIR__ . '/../views/weather_view.php';
    }
}