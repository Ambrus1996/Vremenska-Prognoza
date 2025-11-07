<?php
// File: app/models/WeatherAPI.php

namespace App\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class WeatherAPI
{
    private $client;
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.openweathermap.org/data/2.5/',
            'timeout'  => 5.0, // Set a timeout for API requests
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

            $body = $response->getBody()->getContents();
            $weatherData = json_decode($body);

            // --- [FIX - High] Validate the API payload BEFORE accessing properties ---
            if (json_last_error() !== JSON_ERROR_NONE) {
                // The API returned broken/malformed JSON.
                error_log("WeatherAPI Error: API returned invalid JSON.");
                throw new \Exception("API returned invalid JSON response.");
            }
            
            // Check for the *existence* of nested properties before trying to read them.
            // This handles cases where the city is not found (404) or the API response is partial.
            if (!isset($weatherData->sys, $weatherData->main, $weatherData->weather[0], $weatherData->wind)) {
                error_log("WeatherAPI Error: Weather data structure is incomplete for city: " . $cityName);
                throw new \Exception("Weather data structure is incomplete for " . htmlspecialchars($cityName));
            }
            // --- End of Validation Fix ---


            $dateTime = new \DateTime('@' . $weatherData->dt);
            $dateTime->setTimezone(new \DateTimeZone('UTC'));
            
            // [FIX - Medium] Correctly handle negative (western) timezone offsets
            $timezoneOffset = (int) ($weatherData->timezone ?? 0);
            $dateTime->modify(sprintf('%+d seconds', $timezoneOffset));

            return [
                'country'     => $weatherData->sys->country ?? 'N/A',
                'city_name'   => $weatherData->name ?? $cityName,
                'temperature' => $weatherData->main->temp ?? 0,
                'humidity'    => $weatherData->main->humidity ?? 0,
                'local_time'  => $dateTime->format('H:i'),
                'icon'        => $weatherData->weather[0]->icon ?? '01d',
                'windSpeed'   => isset($weatherData->wind->speed) ? round($weatherData->wind->speed * 3.6, 2) : 'N/A',
                'feelsLike'   => $weatherData->main->feels_like ?? 'N/A',
                'visibility'  => isset($weatherData->visibility) ? ($weatherData->visibility / 1000) : 'N/A',
                'uvIndex'     => 'N/A', // Not available on this endpoint
                'description' => $weatherData->weather[0]->description ?? ''
            ];
            
        } catch (ClientException $e) {
            // Catches 4xx errors (e.g., 401 Bad API Key, 404 City Not Found)
            error_log("WeatherAPI ClientException: " . $e->getMessage());
            return null; // Return null to signal failure to the controller
        } catch (\Exception $e) {
            // Catches our validation errors (Bad JSON, missing fields) or other general errors
            error_log("WeatherAPI Exception: " . $e->getMessage());
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

            $body = $response->getBody()->getContents();
            $forecastData = json_decode($body);

            // [FIX] Added validation here too for robustness
            if (json_last_error() !== JSON_ERROR_NONE || !isset($forecastData->list)) {
                error_log("Forecast API Error: Invalid JSON or missing 'list' structure.");
                return [];
            }

            $forecast = [];
            foreach ($forecastData->list as $timeSlot) {
                // Add checks inside the loop too for safety
                if (!isset($timeSlot->main, $timeSlot->weather[0])) {
                    continue; // Skip this malformed entry
                }
                
                $date = (new \DateTime($timeSlot->dt_txt))->format('Y-m-d');
                
                if (!isset($forecast[$date])) {
                    $forecast[$date] = [
                        'max_temperature' => $timeSlot->main->temp_max,
                        'min_temperature' => $timeSlot->main->temp_min,
                        'icon'            => $timeSlot->weather[0]->icon,
                        'day_name'        => (new \DateTime($date))->format('D')
                    ];
                } else {
                    $forecast[$date]['max_temperature'] = max($forecast[$date]['max_temperature'], $timeSlot->main->temp_max);
                    $forecast[$date]['min_temperature'] = min($forecast[$date]['min_temperature'], $timeSlot->main->temp_min);
                }
            }

            return array_slice($forecast, 0, 5, true);
        } catch (ClientException $e) {
            // Catches 4xx errors
            error_log("Forecast API ClientException: " . $e->getMessage());
            return [];
        } catch (\Exception $e) {
            // Catches our validation errors or other general errors
            error_log("Forecast API Exception: " . $e->getMessage());
            return [];
        }
    }
}