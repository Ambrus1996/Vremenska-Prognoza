<?php

namespace App\Controllers;

use App\Models\City;
use App\Models\SearchLogger;

require_once __DIR__ . '/../models/City.php';
require_once __DIR__ . '/../models/SearchLogger.php';

class WeatherController
{
    private $weatherAPI;

    public function __construct($weatherAPI)
    {
        $this->weatherAPI = $weatherAPI;
    }

    public function showWeather($cityName = null)
    {
        $cityName = $cityName ?: 'Beograd';
        $cityName = trim($cityName);

        $weather = null;
        $forecast = [];
        $error = null;

        try {

            $weather  = $this->weatherAPI->getWeatherByCityName($cityName);
            $forecast = $this->weatherAPI->get5DayForecastByCityName($cityName);

            if (!$weather) {
                throw new \Exception("Could not find weather data for city: " . htmlspecialchars($cityName));
            }

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

        require __DIR__ . '/../views/weather_view.php';
    }
}