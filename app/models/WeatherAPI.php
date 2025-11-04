<?php

namespace App\Models;

use GuzzleHttp\Client;

class WeatherAPI
{
    private $client;
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            // Base URI se koristi za sve zahteve
            'base_uri' => 'http://api.openweathermap.org/data/2.5/',
        ]);
    }

    public function getWeatherByCityName($cityName)
    {
        $response = $this->client->request('GET', 'weather', [
            'query' => [
                'q' => $cityName,
                'appid' => $this->apiKey,
                'units' => 'metric', // Za prikaz temperature u celzijusima
            ]
        ]);
    
        $weatherData = json_decode($response->getBody()->getContents());
    
        $dateTime = new \DateTime('@' . $weatherData->dt); 
        $dateTime->setTimezone(new \DateTimeZone('UTC')); 
        $dateTime->modify("+$weatherData->timezone seconds + 2 minutes"); 
    
        return [
            'temperature' => $weatherData->main->temp,
            'humidity' => $weatherData->main->humidity,
            'local_time' => $dateTime->format('H:i'),
            'icon' => $weatherData->weather[0]->icon,
            'windSpeed' => round($weatherData->wind->speed * 3.6, 2) ?? 'N/A',
            'feelsLike' => $weatherData->main->feels_like ?? 'N/A', 
            'visibility' => isset($weatherData->visibility) ? ($weatherData->visibility / 1000) : 'N/A',
            'uvIndex' => $weatherData->current->uvi ?? 'N/A'  
        ];        
    }
    

    public function get5DayForecastByCityName($cityName)
{
    try {
        $response = $this->client->request('GET', 'forecast', [
            'query' => [
                'q' => $cityName,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]
        ]);

        $forecastData = json_decode($response->getBody()->getContents());

        $forecast = [];
        foreach ($forecastData->list as $timeSlot) {
            $date = (new \DateTime($timeSlot->dt_txt))->format('Y-m-d');
            if (!isset($forecast[$date])) {
                $forecast[$date] = [
                    'max_temperature' => $timeSlot->main->temp_max,
                    'min_temperature' => $timeSlot->main->temp_min,
                    'icon' => $timeSlot->weather[0]->icon
                ];
            } else {
                $forecast[$date]['max_temperature'] = max($forecast[$date]['max_temperature'], $timeSlot->main->temp_max);
                $forecast[$date]['min_temperature'] = min($forecast[$date]['min_temperature'], $timeSlot->main->temp_min);
            }
        }

        // Vraćanje samo prvih 5 dana
        return array_slice($forecast, 0, 5, true);
    } catch (\Exception $e) {
        // ovde se moze logovati greske po potrebi
        return []; // Vracanje praznog niza u slucaju greske
    }
}

}
