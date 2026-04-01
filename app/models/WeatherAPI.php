<?php
namespace App\Models;

use GuzzleHttp\Client;

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

    // ----------------------------------------------------------------
    // Current weather
    // ----------------------------------------------------------------
    public function getWeatherByCityName($cityName) {
        try {
            $response = $this->client->request('GET', 'weather', [
                'query' => [
                    'q'     => $cityName,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents());

            if (!isset($data->main) || !isset($data->weather[0])) {
                return null;
            }

            return [
                'country'     => $data->sys->country      ?? 'N/A',
                'city_name'   => $data->name               ?? $cityName,
                'temperature' => $data->main->temp         ?? 0,
                'humidity'    => $data->main->humidity     ?? 0,
                'pressure'    => $data->main->pressure     ?? 1013,
                'clouds'      => $data->clouds->all        ?? 0,
                'visibility'  => isset($data->visibility) ? round($data->visibility / 1000, 1) : 10,
                'windSpeed'   => isset($data->wind->speed) ? round($data->wind->speed * 3.6, 1) : 0,
                'windDeg'     => $data->wind->deg          ?? 0,   // needed for compass chip
                'feelsLike'   => $data->main->feels_like   ?? 0,
                'icon'        => $data->weather[0]->icon   ?? '01d',
                'description' => $data->weather[0]->description ?? '',
                'timezone'    => $data->timezone           ?? 0,
                // Coordinates – needed for AQI / UV calls
                'lat'         => $data->coord->lat         ?? null,
                'lon'         => $data->coord->lon         ?? null,
                'sys'         => [
                    'sunrise' => $data->sys->sunrise ?? null,
                    'sunset'  => $data->sys->sunset  ?? null,
                ],
            ];
        } catch (\Exception $e) {
            error_log("WeatherAPI Error: " . $e->getMessage());
            return null;
        }
    }

    // ----------------------------------------------------------------
    // 5-day / 3-hour forecast
    // ----------------------------------------------------------------
    public function get5DayForecastByCityName($cityName) {
        try {
            $response = $this->client->request('GET', 'forecast', [
                'query' => [
                    'q'     => $cityName,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents());
            if (!isset($data->list)) {
                return ['daily' => [], 'chart' => []];
            }

            $forecast  = [];
            $chartData = [];

            foreach ($data->list as $index => $slot) {
                // First 8 slots = 24 h → main chart
                if ($index < 8) {
                    $chartData[] = [
                        'time' => (new \DateTime($slot->dt_txt))->format('H:i'),
                        'temp' => round($slot->main->temp),
                    ];
                }

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
                        'hourly'          => [],
                    ];
                } else {
                    $forecast[$date]['max_temperature'] = max($forecast[$date]['max_temperature'], $slot->main->temp_max);
                    $forecast[$date]['min_temperature'] = min($forecast[$date]['min_temperature'], $slot->main->temp_min);
                }

                $forecast[$date]['hourly'][] = [
                    'time' => (new \DateTime($slot->dt_txt))->format('H:i'),
                    'temp' => round($slot->main->temp),
                ];
            }

            return [
                'daily' => array_slice($forecast, 0, 5, true),
                'chart' => $chartData,
            ];
        } catch (\Exception $e) {
            error_log("ForecastAPI Error: " . $e->getMessage());
            return ['daily' => [], 'chart' => []];
        }
    }

    // ----------------------------------------------------------------
    // Air Quality Index  (free tier – /air_pollution)
    //
    // Returns:
    //   aqi        int   1–5  (OWM scale)
    //   aqi_label  string     Good / Fair / Moderate / Poor / Very Poor
    //   aqi_color  string     CSS hex colour for the active AQI segment
    //   pm25       float      µg/m³
    //   pm10       float      µg/m³
    //   o3         float      µg/m³
    //   no2        float      µg/m³
    // ----------------------------------------------------------------
    public function getAirQuality(float $lat, float $lon): ?array {
        try {
            $response = $this->client->request('GET',
                'https://api.openweathermap.org/data/2.5/air_pollution', [
                    'query' => [
                        'lat'   => $lat,
                        'lon'   => $lon,
                        'appid' => $this->apiKey,
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents());

            if (!isset($data->list[0]->main->aqi)) {
                return null;
            }

            $aqi = (int) $data->list[0]->main->aqi;
            $comp = $data->list[0]->components ?? null;

            $labels = [1 => 'Good', 2 => 'Fair', 3 => 'Moderate', 4 => 'Poor', 5 => 'Very Poor'];
            $colors = [1 => '#34d399', 2 => '#fbbf24', 3 => '#f97316', 4 => '#f87171', 5 => '#c084fc'];

            return [
                'aqi'       => $aqi,
                'aqi_label' => $labels[$aqi] ?? 'Unknown',
                'aqi_color' => $colors[$aqi] ?? '#94a3b8',
                'pm25'      => $comp->pm2_5 ?? null,
                'pm10'      => $comp->pm10  ?? null,
                'o3'        => $comp->o3    ?? null,
                'no2'       => $comp->no2   ?? null,
            ];
        } catch (\Exception $e) {
            error_log("AirQualityAPI Error: " . $e->getMessage());
            return null;
        }
    }

    // ----------------------------------------------------------------
    // UV Index  (free tier – /uvi)
    //
    // Returns:
    //   uv         float  raw index value
    //   uv_label   string Low / Moderate / High / Very High / Extreme
    //   uv_color   string CSS hex colour
    //   uv_tip     string short protection advice
    // ----------------------------------------------------------------
    public function getUVIndex(float $lat, float $lon): ?array {
        try {
            $response = $this->client->request('GET',
                'https://api.openweathermap.org/data/2.5/uvi', [
                    'query' => [
                        'lat'   => $lat,
                        'lon'   => $lon,
                        'appid' => $this->apiKey,
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents());

            if (!isset($data->value)) {
                return null;
            }

            $uv = (float) $data->value;

            // Standard WHO scale
            if ($uv < 3) {
                $label = 'Low';       $color = '#34d399'; $tip = 'No protection needed.';
            } elseif ($uv < 6) {
                $label = 'Moderate';  $color = '#fbbf24'; $tip = 'SPF 30+ recommended 10:00–16:00.';
            } elseif ($uv < 8) {
                $label = 'High';      $color = '#f97316'; $tip = 'SPF 50+, seek shade midday.';
            } elseif ($uv < 11) {
                $label = 'Very High'; $color = '#f87171'; $tip = 'SPF 50+, limit sun exposure.';
            } else {
                $label = 'Extreme';   $color = '#c084fc'; $tip = 'Avoid direct sun 10:00–16:00.';
            }

            return [
                'uv'        => round($uv, 1),
                'uv_label'  => $label,
                'uv_color'  => $color,
                'uv_tip'    => $tip,
            ];
        } catch (\Exception $e) {
            error_log("UVIndexAPI Error: " . $e->getMessage());
            return null;
        }
    }
}