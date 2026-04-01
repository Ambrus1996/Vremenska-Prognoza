<?php
// ----------------------------------------------------------------
// THEMING: dynamic background based on weather icon
// ----------------------------------------------------------------
$bgImage = "https://images.unsplash.com/photo-1517483000871-1dbf64a6e1c6?q=80&w=2070&auto=format&fit=crop";

if (isset($weather) && is_array($weather)) {
    $iconCode = $weather['icon'] ?? '01d';
    if (strpos($iconCode, '09') !== false || strpos($iconCode, '10') !== false || strpos($iconCode, '11') !== false) {
        $bgImage = "https://images.unsplash.com/photo-1428592953211-077101b2021b?q=80&w=2074&auto=format&fit=crop";
    } elseif (strpos($iconCode, '13') !== false) {
        $bgImage = "https://images.unsplash.com/photo-1478265409131-1f65c88f965c?q=80&w=2000&auto=format&fit=crop";
    }
}

$getIconUrl = fn($code) => "https://openweathermap.org/img/wn/" . htmlspecialchars($code) . "@4x.png";

// ----------------------------------------------------------------
// Solar arc: sunrise → sunset progress
// ----------------------------------------------------------------
$finalSunrise = '--:--';
$finalSunset  = '--:--';
$sunProgress  = 0;
$daylightStr  = '--h --m';
$tzOffset     = $weather['timezone'] ?? 0;
$sunSource    = $weather['sys'] ?? null;

if (isset($sunSource['sunrise'], $sunSource['sunset'])) {
    $rise = $sunSource['sunrise'];
    $set  = $sunSource['sunset'];

    $finalSunrise = gmdate('H:i', $rise + $tzOffset);
    $finalSunset  = gmdate('H:i', $set  + $tzOffset);

    $now = time();
    if ($now >= $rise && $now <= $set) {
        $sunProgress = round(($now - $rise) / ($set - $rise) * 100);
    } elseif ($now > $set) {
        $sunProgress = 100;
    }

    $dlSec       = $set - $rise;
    $daylightStr = floor($dlSec / 3600) . 'h ' . floor(($dlSec % 3600) / 60) . 'm';
}

// ----------------------------------------------------------------
// Wind direction helper
// ----------------------------------------------------------------
$windDir = '';
if (isset($weather['windDeg'])) {
    $dirs    = ['N','NE','E','SE','S','SW','W','NW'];
    $windDir = $dirs[round($weather['windDeg'] / 45) % 8];
}

// ----------------------------------------------------------------
// Context chips
// ----------------------------------------------------------------
$chips = [];
if ($daylightStr !== '--h --m')  $chips[] = ['☀️', 'Daylight',   $daylightStr];
if ($windDir !== '')             $chips[] = ['🧭', 'Wind from',  $windDir];
if (!empty($weather['feelsLike'])) $chips[] = ['🌡️', 'Feels like', round($weather['feelsLike']) . '°C'];
if (!empty($weather['visibility'])) $chips[] = ['👁️', 'Visibility', $weather['visibility'] . ' km'];

// ----------------------------------------------------------------
// AQI helper data
// ----------------------------------------------------------------
$aqiColors = ['#34d399','#fbbf24','#f97316','#f87171','#c084fc'];

// ----------------------------------------------------------------
// Stat cards config
// ----------------------------------------------------------------
$humBar   = min(100, (int)($weather['humidity']  ?? 0));
$windMax  = 120;
$windBar  = min(100, round((($weather['windSpeed'] ?? 0) / $windMax) * 100));
$feelBar  = min(100, max(0, round((((float)($weather['feelsLike'] ?? $weather['temperature']) + 20) / 60) * 100)));
$presBar  = min(100, round((((int)($weather['pressure'] ?? 1013) - 950) / 100) * 100));
$cloudBar = min(100, (int)($weather['clouds']     ?? 0));
$visBar   = min(100, round((min((float)($weather['visibility'] ?? 10), 10) / 10) * 100));

$stats = [
    ['blue',   'fa-droplet',         'Humidity',   ($weather['humidity'] ?? 0) . '%',                                   $humBar],
    ['gray',   'fa-wind',            'Wind',       round($weather['windSpeed'] ?? 0) . ' km/h',                         $windBar],
    ['red',    'fa-temperature-half','Feels Like', round($weather['feelsLike'] ?? $weather['temperature']) . '°',        $feelBar],
    ['cyan',   'fa-gauge-high',      'Pressure',   ($weather['pressure'] ?? 1013) . ' hPa',                              $presBar],
    ['purple', 'fa-cloud',           'Clouds',     ($weather['clouds'] ?? 0) . '%',                                      $cloudBar],
    ['green',  'fa-eye',             'Visibility', ($weather['visibility'] ?? 10) . ' km',                               $visBar],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Pro — <?= htmlspecialchars($cityName ?? 'Home') ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">

   
    <link rel="stylesheet" href="/weather.css">

   
   <style>
        body { background-image: url('<?= $bgImage ?>'); }
    </style>
</head>
<body class="flex items-center justify-center p-2 md:p-6">
<div class="bg-overlay"></div>

<?php if (isset($error)): ?>
    <div class="z-50 bg-red-500/20 p-6 rounded-2xl border border-red-500/40 text-white max-w-md text-center">
        <i class="fa-solid fa-triangle-exclamation text-red-400 text-3xl mb-3 block"></i>
        <?= htmlspecialchars($error) ?>
    </div>

<?php elseif ($weather): ?>

<div class="main-dashboard w-full max-w-[1500px] flex flex-col lg:flex-row relative overflow-hidden"
     x-data="{
         show:         false,
         modalOpen:    false,
         selectedDate: null,
         forecastObj:  window.forecastData
     }"
     x-init="setTimeout(() => show = true, 50)">

    <!-- ================================================================
         LEFT PANEL
    ================================================================ -->
    <div class="w-full lg:w-[38%] p-6 md:p-10 flex flex-col gap-6
                border-b lg:border-b-0 lg:border-r border-white/[0.05]">

        <!-- Search -->
        <form action="index.php" method="get">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass text-cyan-400/70 text-sm"></i>
                <input type="text" name="city"
                       placeholder="Search city..."
                       value="<?= htmlspecialchars($cityName ?? '') ?>">
                <?php if (!empty($weather['country'])): ?>
                    <span class="country-tag"><?= htmlspecialchars($weather['country']) ?></span>
                <?php endif; ?>
                <span class="live-badge"><span class="live-dot"></span>Live</span>
            </div>
        </form>

        <!-- Temperature hero -->
        <div class="flex flex-col items-center lg:items-start gap-3 flex-1 justify-center">
            <span class="date-chip"><?= date('D, M d, Y') ?></span>

            <h1 class="text-4xl md:text-6xl font-black tracking-tight leading-tight mt-1">
                <?= htmlspecialchars($weather['city_name']) ?>
            </h1>

            <div class="flex items-center gap-6 my-2">
                <div class="flex items-start">
                    <span class="temp-num"><?= round($weather['temperature']) ?></span>
                    <span class="temp-deg">°</span>
                </div>
                <div class="weather-icon-glow">
                    <img src="<?= $getIconUrl($weather['icon']) ?>"
                         class="w-24 h-24 md:w-32 md:h-32 drop-shadow-2xl">
                </div>
            </div>

            <p class="text-lg md:text-2xl font-bold text-gray-400 capitalize">
                <?= htmlspecialchars($weather['description']) ?>
            </p>
        </div>

        <!-- Solar arc -->
        <div class="solar-strip">
            <div class="solar-icon-box rise">☀️</div>
            <div>
                <div class="solar-time-label">Sunrise</div>
                <div class="solar-time-value"><?= $finalSunrise ?></div>
            </div>

            <div class="solar-arc-track flex-1">
                <div class="solar-arc-fill" style="width:<?= $sunProgress ?>%">
                    <div class="solar-sun-dot"></div>
                </div>
            </div>

            <div class="text-right">
                <div class="solar-time-label">Sunset</div>
                <div class="solar-time-value"><?= $finalSunset ?></div>
            </div>
            <div class="solar-icon-box set">🌙</div>
        </div>

        <!-- Context chips -->
        <?php if (!empty($chips)): ?>
        <div class="chip-row">
            <?php foreach ($chips as [$icon, $label, $value]): ?>
                <span class="chip"><?= $icon ?> <?= $label ?>: <strong><?= $value ?></strong></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /left panel -->

    <!-- ================================================================
         RIGHT PANEL
    ================================================================ -->
    <div class="w-full lg:w-[62%] p-6 md:p-10 bg-black/[0.15] flex flex-col gap-5 md:gap-6">

        <!-- Stat cards -->
        <div>
            <p class="panel-label mb-3">Current Conditions</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
                <?php foreach ($stats as [$color, $icon, $label, $value, $bar]): ?>
                <div class="stat-card" data-color="<?= $color ?>">
                    <i class="fa-solid <?= $icon ?> text-xl mb-1
                        <?php if ($color === 'blue')   echo 'text-blue-400';
                              elseif ($color === 'gray')   echo 'text-slate-400';
                              elseif ($color === 'red')    echo 'text-red-400';
                              elseif ($color === 'cyan')   echo 'text-cyan-400';
                              elseif ($color === 'purple') echo 'text-violet-400';
                              elseif ($color === 'green')  echo 'text-emerald-400'; ?>
                    "></i>
                    <span class="stat-label"><?= $label ?></span>
                    <span class="stat-value"><?= $value ?></span>
                    <div class="stat-bar-track">
                        <div class="stat-bar-fill" style="width:<?= $bar ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 24h chart -->
        <div class="chart-panel">
            <div class="flex justify-between items-center mb-4">
                <span class="panel-label">24H Temperature Trend</span>
                <span class="live-badge"><span class="live-dot"></span>Live Data</span>
            </div>
            <div class="flex-1 relative w-full" style="min-height:180px;">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <!-- AQI + UV row (visible only when API data is available) -->
        <?php if ($airQuality || $uvIndex): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <?php if ($airQuality): ?>
            <div class="aqi-card">
                <p class="panel-label">Air Quality Index</p>

                <!-- Coloured scale: 5 segments, active one pops -->
                <div class="aqi-scale">
                    <?php foreach ($aqiColors as $i => $col): ?>
                        <div class="aqi-seg <?= ($i + 1 === $airQuality['aqi']) ? 'aqi-active' : '' ?>"
                             style="background:<?= $col ?>"></div>
                    <?php endforeach; ?>
                </div>

                <div class="aqi-value" style="color:<?= htmlspecialchars($airQuality['aqi_color']) ?>">
                    <?= $airQuality['aqi'] ?>
                </div>
                <div class="aqi-status" style="color:<?= htmlspecialchars($airQuality['aqi_color']) ?>">
                    ✓ <?= htmlspecialchars($airQuality['aqi_label']) ?>
                </div>

                <?php if ($airQuality['pm25'] !== null): ?>
                <div style="font-size:10px;color:rgba(255,255,255,0.3);margin-top:4px;">
                    PM2.5 <?= round($airQuality['pm25'],1) ?> µg/m³
                    &nbsp;·&nbsp;
                    PM10 <?= round($airQuality['pm10'],1) ?> µg/m³
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($uvIndex): ?>
            <div class="uv-card">
                <p class="panel-label">UV Index</p>
                <div class="uv-row">
                    <div class="uv-num" style="color:<?= htmlspecialchars($uvIndex['uv_color']) ?>">
                        <?= $uvIndex['uv'] ?>
                    </div>
                    <div>
                        <div class="uv-level" style="color:<?= htmlspecialchars($uvIndex['uv_color']) ?>">
                            <?= htmlspecialchars($uvIndex['uv_label']) ?>
                        </div>
                        <div class="uv-tip"><?= htmlspecialchars($uvIndex['uv_tip']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <!-- Forecast strip -->
        <div>
            <p class="panel-label mb-3">5-Day Forecast</p>
            <div class="flex gap-2 md:gap-3 overflow-x-auto no-scrollbar pb-1">
                <?php
                $todayKey = date('Y-m-d');
                $allMaxes = array_column($forecast, 'max_temperature');
                $allMins  = array_column($forecast, 'min_temperature');
                $rangeMin = empty($allMins)  ? 0  : min($allMins);
                $rangeMax = empty($allMaxes) ? 30 : max($allMaxes);
                $spread   = ($rangeMax - $rangeMin) ?: 1;
                ?>
                <?php foreach ($forecast as $day => $data):
                    $rangeBar = round(($data['max_temperature'] - $rangeMin) / $spread * 100);
                ?>
                <div class="forecast-card <?= $day === $todayKey ? 'fc-active' : '' ?>"
                     @click="selectedDate = '<?= $day ?>'; modalOpen = true;
                             $nextTick(() => { renderModalChart('<?= $day ?>'); })">
                    <span class="fc-day"><?= date('D', strtotime($day)) ?></span>
                    <img src="<?= $getIconUrl($data['icon']) ?>"
                         class="w-10 h-10 md:w-12 md:h-12 transition-transform duration-300">
                    <span class="fc-max"><?= round($data['max_temperature']) ?>°</span>
                    <div class="fc-range-track">
                        <div class="fc-range-fill" style="width:<?= $rangeBar ?>%"></div>
                    </div>
                    <span class="fc-min"><?= round($data['min_temperature']) ?>°</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /right panel -->

    <!-- ================================================================
         MODAL
    ================================================================ -->
    <div x-show="modalOpen"
         style="display:none;"
         class="fixed inset-0 z-[100] flex items-center justify-center p-3 md:p-6 bg-black/70 backdrop-blur-lg"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-180"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="glass-modal w-full max-w-3xl p-6 md:p-10 relative flex flex-col gap-7
                    overflow-y-auto max-h-[94vh] no-scrollbar"
             @click.outside="modalOpen = false"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <button @click="modalOpen = false"
                    class="absolute top-5 right-5 w-9 h-9 flex items-center justify-center
                           rounded-full bg-white/5 border border-white/10
                           text-gray-400 hover:text-white hover:bg-white/10 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <template x-if="selectedDate && forecastObj[selectedDate]">
                <div class="flex flex-col gap-7 w-full">

                    <!-- Header: date + temp + mini stats -->
                    <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">

                        <!-- Left: icon + temp -->
                        <div class="flex-1 flex flex-col items-center md:items-start gap-2">
                            <span class="date-chip" x-text="forecastObj[selectedDate].full_date"></span>
                            <p class="text-cyan-400 font-bold uppercase tracking-widest text-xs"
                               x-text="forecastObj[selectedDate].description"></p>

                            <div class="flex items-center gap-5 mt-2">
                                <div class="weather-icon-glow">
                                    <img :src="'https://openweathermap.org/img/wn/' + forecastObj[selectedDate].icon + '@4x.png'"
                                         class="w-24 h-24 drop-shadow-2xl"
                                         style="animation:floatIcon 5s ease-in-out infinite;">
                                </div>
                                <div>
                                    <div class="flex items-start">
                                        <span class="font-black leading-none"
                                              style="font-size:60px;
                                                     background:linear-gradient(145deg,#fff 20%,rgba(255,255,255,0.6) 100%);
                                                     -webkit-background-clip:text;
                                                     -webkit-text-fill-color:transparent;
                                                     background-clip:text;
                                                     letter-spacing:-3px;"
                                              x-text="Math.round(forecastObj[selectedDate].max_temperature)"></span>
                                        <span style="font-size:28px;font-weight:900;color:#22d3ee;margin-top:8px;">°</span>
                                    </div>
                                    <p class="text-lg font-bold text-gray-500">
                                        Min: <span class="text-white"
                                                   x-text="Math.round(forecastObj[selectedDate].min_temperature)"></span>°
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: mini stat grid -->
                        <div class="flex-1 grid grid-cols-2 gap-3">
                            <div class="modal-stat-card">
                                <i class="fa-solid fa-droplet text-blue-400 mb-1"></i>
                                <span class="panel-label">Humidity</span>
                                <span class="font-bold text-lg"
                                      x-text="forecastObj[selectedDate].humidity + '%'"></span>
                            </div>
                            <div class="modal-stat-card">
                                <i class="fa-solid fa-wind text-slate-400 mb-1"></i>
                                <span class="panel-label">Wind</span>
                                <span class="font-bold text-lg"
                                      x-text="forecastObj[selectedDate].windSpeed + ' km/h'"></span>
                            </div>
                            <div class="modal-stat-card">
                                <i class="fa-solid fa-gauge-high text-cyan-400 mb-1"></i>
                                <span class="panel-label">Pressure</span>
                                <span class="font-bold text-lg"
                                      x-text="forecastObj[selectedDate].pressure + ' hPa'"></span>
                            </div>
                            <div class="modal-stat-card">
                                <i class="fa-solid fa-cloud text-slate-400 mb-1"></i>
                                <span class="panel-label">Clouds</span>
                                <span class="font-bold text-lg"
                                      x-text="forecastObj[selectedDate].clouds + '%'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Daily trend chart -->
                    <div class="chart-panel">
                        <span class="panel-label mb-4 block">Daily Temperature Trend</span>
                        <div class="relative w-full" style="min-height:220px;">
                            <canvas id="modalChart"></canvas>
                        </div>
                    </div>

                </div>
            </template>
        </div>
    </div>

</div><!-- /main-dashboard -->
<?php endif; ?>

<!-- ================================================================
     SCRIPTS
================================================================ -->
<script>
    window.forecastData   = <?= json_encode($forecast     ?? [], JSON_NUMERIC_CHECK) ?>;
    const mainChartPoints = <?= json_encode($chartPoints  ?? [], JSON_NUMERIC_CHECK) ?>;
    let   modalChartInstance = null;

    /* ---- shared chart options factory ---- */
    function buildChartOptions(ctx, labels, temps) {
        const grad = ctx.createLinearGradient(0, 0, 0, 260);
        grad.addColorStop(0, 'rgba(34,211,238,0.22)');
        grad.addColorStop(1, 'transparent');

        return {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: temps,
                    borderColor: '#22d3ee',
                    borderWidth: 2.5,
                    fill: true,
                    backgroundColor: grad,
                    tension: 0.45,
                    pointRadius: 3,
                    pointBackgroundColor: '#22d3ee',
                    pointBorderColor: 'transparent',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(5,13,26,0.9)',
                        borderColor: 'rgba(34,211,238,0.3)',
                        borderWidth: 1,
                        titleColor: '#22d3ee',
                        bodyColor: '#fff',
                        cornerRadius: 10,
                        padding: 10,
                        callbacks: { label: c => ` ${c.parsed.y}°C` }
                    }
                },
                scales: {
                    y: { display: false },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: 'rgba(255,255,255,0.28)',
                            font: { size: 9, weight: 'bold', family: 'Outfit' },
                            maxRotation: 0,
                        },
                        border: { display: false }
                    }
                }
            }
        };
    }

    /* ---- Main 24h chart ---- */
    window.addEventListener('load', () => {
        const canvas = document.getElementById('mainChart');
        if (!canvas || !mainChartPoints.length) return;

        const labels = mainChartPoints.map(p => p.time);
        const temps  = mainChartPoints.map(p => p.temp);
        new Chart(canvas.getContext('2d'), buildChartOptions(canvas.getContext('2d'), labels, temps));
    });

    /* ---- Modal chart ---- */
    window.renderModalChart = function(dateKey) {
        const dayData = window.forecastData[dateKey];
        if (!dayData?.hourly) return;

        const canvas = document.getElementById('modalChart');
        if (!canvas) return;

        if (modalChartInstance) modalChartInstance.destroy();

        const labels = dayData.hourly.map(h => h.time);
        const temps  = dayData.hourly.map(h => h.temp);
        modalChartInstance = new Chart(canvas.getContext('2d'), buildChartOptions(canvas.getContext('2d'), labels, temps));
    };
</script>
</body>
</html>