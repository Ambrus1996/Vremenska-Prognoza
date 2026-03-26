<?php
namespace App\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WeatherAPI {
    private $client;
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.openweathermap.org/data/2.5/',
            'timeout'  => 5.0,
        ]);
    }

    public function getWeatherByCityName($cityName) {
        try {
            $response = $this->client->request('GET', 'weather', [
                'query' => [
                    'q' => $cityName, 
                    'appid' => $this->apiKey, 
                    'units' => 'metric'
                ]
            ]);

            $contents = $response->getBody()->getContents();
            $data = json_decode($contents);

            // Check if key data exists before accessing it
            if (!isset($data->main) || !isset($data->weather[0])) {
                return null;
            }

            return [
                'country'     => $data->sys->country ?? 'N/A',
                'city_name'   => $data->name ?? $cityName,
                'temperature' => $data->main->temp ?? 0,
                'humidity'    => $data->main->humidity ?? 0,
                'pressure'    => $data->main->pressure ?? 1013,
                'clouds'      => $data->clouds->all ?? 0,
                'visibility'  => isset($data->visibility) ? ($data->visibility / 1000) : 10,
                'windSpeed'   => isset($data->wind->speed) ? round($data->wind->speed * 3.6, 2) : 0,
                'feelsLike'   => $data->main->feels_like ?? 0,
                'icon'        => $data->weather[0]->icon ?? '01d',
                'description' => $data->weather[0]->description ?? '',
                'timezone'    => $data->timezone ?? 0,
                'sys'         => [
                    'sunrise' => $data->sys->sunrise ?? null,
                    'sunset'  => $data->sys->sunset ?? null,
                ]
            ];
        } catch (\Exception $e) {
            error_log("WeatherAPI Error: " . $e->getMessage());
            return null;
        }
    }

    public function get5DayForecastByCityName($cityName) {
        try {
            $response = $this->client->request('GET', 'forecast', [
                'query' => ['q' => $cityName, 'appid' => $this->apiKey, 'units' => 'metric']
            ]);
            $data = json_decode($response->getBody()->getContents());
            
            if (!isset($data->list)) { return ['daily' => [], 'chart' => []]; }

            $forecast = []; 
            $chartData = [];

            foreach ($data->list as $index => $slot) {
                // Main website chart (first 8 slots = 24h)
                if ($index < 8) {
                    $chartData[] = [
                        'time' => (new \DateTime($slot->dt_txt))->format('H:i'),
                        'temp' => round($slot->main->temp)
                    ];
                }

                // Grouping by days for cards and MODAL
                $date = (new \DateTime($slot->dt_txt))->format('Y-m-d');
                if (!isset($forecast[$date])) {
                    $forecast[$date] = [
                        'max_temperature' => $slot->main->temp_max,
                        'min_temperature' => $slot->main->temp_min,
                        'icon'            => $slot->weather[0]->icon,
                        'full_date'       => (new \DateTime($date))->format('l, F d'),
                        'description'     => $slot->weather[0]->description,
                        'humidity'        => $slot->main->humidity,
                        'windSpeed'       => round($slot->wind->speed * 3.6),
                        'pressure'        => $slot->main->pressure,
                        'clouds'          => $slot->clouds->all,
                        'hourly'          => []
                    ];
                } else {
                    $forecast[$date]['max_temperature'] = max($forecast[$date]['max_temperature'], $slot->main->temp_max);
                    $forecast[$date]['min_temperature'] = min($forecast[$date]['min_temperature'], $slot->main->temp_min);
                }
                
                // Filling the new array with exact temperatures for the given day
                $forecast[$date]['hourly'][] = [
                    'time' => (new \DateTime($slot->dt_txt))->format('H:i'),
                    'temp' => round($slot->main->temp)
                ];
            }

            return [
                'daily' => array_slice($forecast, 0, 5, true), 
                'chart' => $chartData
            ];
        } catch (\Exception $e) {
            error_log("ForecastAPI Error: " . $e->getMessage());
            return ['daily' => [], 'chart' => []];
        }
    }
}