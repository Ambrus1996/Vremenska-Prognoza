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
            'base_uri' => 'http://api.openweathermap.org/data/2.5/',
            'timeout'  => 5.0, // Added timeout to prevent indefinite hanging
        ]);
    }

    public function getWeatherByCityName($cityName)
    {
        try {
            $response = $this->client->request('GET', 'weather', [
                'query' => [
                    'q' => $cityName,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]
            ]);

            $weatherData = json_decode($response->getBody()->getContents());

            // Calculate local time
            $dateTime = new \DateTime('@' . $weatherData->dt);
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
            // Use timezone offset from API instead of hardcoded +2 minutes
            $dateTime->modify("+" . ($weatherData->timezone ?? 0) . " seconds");

            return [
                // [FIX] Added 'country' because controller expects it for DB
                'country'     => $weatherData->sys->country ?? 'N/A',
                'city_name'   => $weatherData->name ?? $cityName,
                'temperature' => $weatherData->main->temp ?? 0,
                'humidity'    => $weatherData->main->humidity ?? 0,
                'local_time'  => $dateTime->format('H:i'),
                'icon'        => $weatherData->weather[0]->icon ?? '01d',
                'windSpeed'   => isset($weatherData->wind->speed) ? round($weatherData->wind->speed * 3.6, 2) : 'N/A',
                'feelsLike'   => $weatherData->main->feels_like ?? 'N/A',
                'visibility'  => isset($weatherData->visibility) ? ($weatherData->visibility / 1000) : 'N/A',
                // [FIX] UV Index is NOT available on this endpoint, setting safe default
                'uvIndex'     => 'N/A',
                'description' => $weatherData->weather[0]->description ?? ''
            ];
        } catch (\Exception $e) {
            // Log error here if needed, returning null to signal failure
            return null;
        }
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

            if (!isset($forecastData->list)) {
                return [];
            }

            $forecast = [];
            foreach ($forecastData->list as $timeSlot) {
                $date = (new \DateTime($timeSlot->dt_txt))->format('Y-m-d');
                if (!isset($forecast[$date])) {
                    $forecast[$date] = [
                        'max_temperature' => $timeSlot->main->temp_max,
                        'min_temperature' => $timeSlot->main->temp_min,
                        'icon'            => $timeSlot->weather[0]->icon,
                        // Added day name for better UI (e.g., "Mon", "Tue")
                        'day_name'        => (new \DateTime($date))->format('D')
                    ];
                } else {
                    $forecast[$date]['max_temperature'] = max($forecast[$date]['max_temperature'], $timeSlot->main->temp_max);
                    $forecast[$date]['min_temperature'] = min($forecast[$date]['min_temperature'], $timeSlot->main->temp_min);
                }
            }

            return array_slice($forecast, 0, 5, true);
        } catch (\Exception $e) {
            return [];
        }
    }
}