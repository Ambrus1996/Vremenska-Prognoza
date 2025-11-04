<?php
namespace App\Controllers;

require_once __DIR__ . '/../../app/models/City.php';

class WeatherController
{
    private $weatherAPI;

    public function __construct($weatherAPI)
    {
        $this->weatherAPI = $weatherAPI;
    }

    public function showWeather($cityName = null)
    {
        $cityModel = new \City();

        if ($cityName === null || $cityName === '') {
            $cityName = 'Beograd';
        }

        $cityName = trim($cityName);
        $cityName = mb_convert_case($cityName, MB_CASE_TITLE, "UTF-8");

        // prvo povuci vreme – tu imamo i country
        $weather  = $this->weatherAPI->getWeatherByCityName($cityName);
        $forecast = $this->weatherAPI->get5DayForecastByCityName($cityName);

        $country = $weather['country'] ?? ($weather['sys']['country'] ?? null);

        // da li grad već postoji u bazi?
        $exists = $cityModel->findByName($cityName);
        if (!$exists) {
            $cityModel->create($cityName, $country);
        }

        require __DIR__ . '/../views/weather_view.php';
    }
}
