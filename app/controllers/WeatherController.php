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
        // Sanitizing input and setting defaults
        $cityName = trim($cityName ?: 'Smederevo');
        
        // Initialization - preventing "undefined variable" in View if the API fails
        $error = null; 
        $weather = null; 
        $forecast = []; 
        $chartPoints = [];

        try {
            // Current weather API call
            $weather = $this->weatherAPI->getWeatherByCityName($cityName);
            
            if (!$weather) { 
                throw new \Exception("City not found: " . htmlspecialchars($cityName)); 
            }

            // Forecast API call (Fetching the 'daily' and 'chart' arrays)
            $fData = $this->weatherAPI->get5DayForecastByCityName($cityName);
            
            // Parsing/Organizing data for the View
            $forecast    = $fData['daily'] ?? [];
            $chartPoints = $fData['chart'] ?? [];

            // Database logic (Ensuring 'country' exists in weather data)
            $cityModel = new City();
            if (!$cityModel->findByName($cityName)) { 
                $cityModel->create($cityName, $weather['country']); 
            }

            // Logging the search
            $logger = new SearchLogger();
            $logger->logSearch(
                $weather['city_name'], 
                round($weather['temperature']), 
                $weather['description']
            );

        } catch (\Exception $e) { 
            // Catching the error to display it in the red box within the View
            $error = $e->getMessage(); 
        }

        // Rendering the final design
        require __DIR__ . '/../views/weather_view.php';
    }
}