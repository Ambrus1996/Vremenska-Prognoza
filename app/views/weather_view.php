<?php
// --- THEMING ENGINE (DYNAMIC BG BASED ON WEATHER) ---
$bgImage = "https://images.unsplash.com/photo-1517483000871-1dbf64a6e1c6?q=80&w=2070&auto=format&fit=crop";

if (isset($weather) && is_array($weather)) {
    $iconCode = $weather['icon'] ?? '01d';
    // Changing the background if it's raining or snowing
    if (strpos($iconCode, '09') !== false || strpos($iconCode, '10') !== false || strpos($iconCode, '11') !== false) {
        $bgImage = "https://images.unsplash.com/photo-1428592953211-077101b2021b?q=80&w=2074&auto=format&fit=crop"; 
    } elseif (strpos($iconCode, '13') !== false) {
        $bgImage = "https://images.unsplash.com/photo-1478265409131-1f65c88f965c?q=80&w=2000&auto=format&fit=crop"; 
    }
}

$getIconUrl = function($iconCode) { 
    return "https://openweathermap.org/img/wn/" . htmlspecialchars($iconCode) . "@4x.png"; 
};

// Sun logic (Strict 24h format: H:i)
$finalSunrise = '--:--'; 
$finalSunset = '--:--';

$tzOffset = $weather['timezone'] ?? 0;
$sunSource = $weather['sys'] ?? $weather['city'] ?? null;

if (isset($sunSource['sunrise']) && isset($sunSource['sunset'])) {
    $finalSunrise = gmdate('H:i', $sunSource['sunrise'] + $tzOffset);
    $finalSunset  = gmdate('H:i', $sunSource['sunset'] + $tzOffset);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Pro - <?= htmlspecialchars($cityName ?? 'Home') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-image: url('<?= $bgImage ?>'); background-size: cover; background-position: center; background-attachment: fixed; color: white; overflow: hidden; }
        .bg-overlay { position: fixed; inset: 0; background: radial-gradient(circle at 0% 0%, rgba(15,23,42,0.5), rgba(2,6,23,0.96)); backdrop-filter: blur(50px); z-index: 0; }
        .main-dashboard { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; z-index: 10; overflow: hidden; }
        .glass-card { background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 24px; }
        .glass-card-light { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; transition: 0.3s; }
        .glass-card-light:hover { background: rgba(255,255,255,0.1); transform: translateY(-3px); }
        .cyan-accent { color: #22d3ee; }
        .animate-icon { animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="h-screen w-screen flex items-center justify-center p-4">
    <div class="bg-overlay"></div>

    <?php if (isset($error)): ?>
        <div class="z-50 bg-red-500/20 p-6 rounded-xl border border-red-500/50 text-white"><?= $error ?></div>
    <?php elseif ($weather): ?>
    
    <div class="main-dashboard w-[95%] max-w-[1500px] max-h-[92vh] flex flex-col lg:flex-row relative" 
         x-data="{ 
            show: false, 
            modalOpen: false, 
            selectedDate: null, 
            forecastObj: window.forecastData 
         }" 
         x-init="setTimeout(() => show = true, 50)">
        
        <div class="w-full lg:w-[38%] p-10 flex flex-col justify-between border-r border-white/5">
            <form action="index.php" method="get" class="bg-white/5 rounded-2xl px-5 py-3 border border-white/10 focus-within:border-cyan-400/50 transition-all">
                <input type="text" name="city" placeholder="Search city..." value="<?= htmlspecialchars($cityName ?? '') ?>" class="bg-transparent outline-none w-full text-white">
            </form>

            <div class="flex-1 flex flex-col justify-center py-8">
                <h1 class="text-7xl font-black tracking-tight mb-2"><?= htmlspecialchars($weather['city_name']) ?></h1>
                <p class="text-cyan-400 font-bold tracking-widest uppercase text-xl mb-10 drop-shadow-sm">
                    <?= date("l, F d, Y") ?>
                </p>
                
                <div class="flex items-center gap-8 mb-4">
                    <div class="text-[9rem] xl:text-[11rem] font-black leading-none tracking-tighter drop-shadow-2xl text-white">
                        <?= round($weather['temperature']) ?><span class="text-6xl xl:text-7xl cyan-accent align-top ml-1">°</span>
                    </div>
                    <img src="<?= $getIconUrl($weather['icon']) ?>" class="w-40 h-40 animate-icon drop-shadow-2xl">
                </div>
                <p class="text-3xl font-bold text-gray-400 capitalize"><?= htmlspecialchars($weather['description']) ?></p>
            </div>

            <div class="flex gap-12 border-t border-white/10 pt-8">
                <div class="flex items-center gap-4">
                    <i class="fa-solid fa-sun text-yellow-400 text-4xl drop-shadow-md"></i>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Sunrise</p>
                        <p class="text-2xl font-bold text-white"><?= $finalSunrise ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <i class="fa-solid fa-moon cyan-accent text-4xl drop-shadow-md"></i>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Sunset</p>
                        <p class="text-2xl font-bold text-white"><?= $finalSunset ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-[62%] p-10 bg-black/20 flex flex-col gap-8">
            <div class="grid grid-cols-3 gap-6">
                <?php 
                $stats = [
                    ['Humidity', $weather['humidity'].'%', 'fa-droplet', 'text-blue-400'],
                    ['Wind', round($weather['windSpeed']).' km/h', 'fa-wind', 'text-gray-300'],
                    ['Feels Like', round($weather['feelsLike'] ?? $weather['temperature']).'°', 'fa-temperature-half', 'text-red-400'],
                    ['Pressure', ($weather['pressure'] ?? 1013).' hPa', 'fa-gauge-high', 'cyan-accent'],
                    ['Clouds', ($weather['clouds'] ?? 0).'%', 'fa-cloud', 'text-gray-400'],
                    ['Visibility', ($weather['visibility'] ?? 10).' km', 'fa-eye', 'text-emerald-400'],
                ];
                foreach($stats as $s): ?>
                <div class="glass-card p-6 flex flex-col items-center">
                    <i class="fa-solid <?= $s[2] ?> <?= $s[3] ?> text-2xl mb-3"></i>
                    <p class="text-[10px] text-gray-500 uppercase font-black mb-1"><?= $s[0] ?></p>
                    <p class="text-xl font-black"><?= $s[1] ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="glass-card p-8 flex-1 min-h-[250px] flex flex-col">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest">24H Temperature Trend</h3>
                    <span class="text-[9px] font-black cyan-accent px-2 py-1 bg-cyan-400/10 rounded-full border border-cyan-400/20">LIVE DATA</span>
                </div>
                <div class="flex-1 relative w-full h-full"><canvas id="mainChart"></canvas></div>
            </div>

            <div class="flex justify-between gap-4">
                <?php foreach ($forecast as $day => $data): ?>
                <div @click="selectedDate = '<?= $day ?>'; modalOpen = true; $nextTick(() => { renderModalChart('<?= $day ?>'); })" 
                     class="glass-card-light flex-1 py-4 flex flex-col items-center group cursor-pointer">
                    <span class="text-[10px] font-black text-gray-400 group-hover:text-cyan-400 uppercase tracking-widest transition-colors">
                        <?= date("D", strtotime($day)) ?>
                    </span>
                    <img src="<?= $getIconUrl($data['icon']) ?>" class="w-12 h-12 my-2 group-hover:scale-110 transition duration-300">
                    <div class="font-black text-sm">
                        <span class="text-white"><?= round($data['max_temperature']) ?>°</span>
                        <span class="text-gray-500 ml-1"><?= round($data['min_temperature']) ?>°</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div x-show="modalOpen" style="display: none;" 
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
             x-transition.opacity.duration.300ms>
            
            <div class="glass-card w-full max-w-4xl p-10 relative border-t border-white/20 shadow-2xl flex flex-col gap-10" 
                 @click.outside="modalOpen = false" 
                 x-show="modalOpen" 
                 x-transition.scale.duration.300ms>
                
                <button @click="modalOpen = false" class="absolute top-6 right-6 text-gray-400 hover:text-white transition z-50">
                    <i class="fa-solid fa-xmark text-3xl"></i>
                </button>
                
                <template x-if="selectedDate && forecastObj[selectedDate]">
                    <div class="flex flex-col w-full gap-8">
                        
                        <div class="flex flex-col md:flex-row justify-between items-center gap-8 w-full">
                            
                            <div class="flex-1 flex flex-col">
                                <h2 class="text-4xl font-black text-white mb-1 drop-shadow-md" x-text="forecastObj[selectedDate].full_date"></h2>
                                <p class="text-cyan-400 font-bold uppercase tracking-widest text-sm mb-4" x-text="forecastObj[selectedDate].description"></p>
                                
                                <div class="flex items-center gap-4">
                                    <img :src="'https://openweathermap.org/img/wn/' + forecastObj[selectedDate].icon + '@4x.png'" class="w-24 h-24 drop-shadow-xl animate-icon">
                                    <div class="flex flex-col">
                                        <span class="text-6xl font-black text-white leading-none"><span x-text="Math.round(forecastObj[selectedDate].max_temperature)"></span><span class="text-4xl cyan-accent align-top">°</span></span>
                                        <span class="text-xl font-bold text-gray-500 mt-1">Min: <span x-text="Math.round(forecastObj[selectedDate].min_temperature)"></span>°</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 w-full grid grid-cols-2 gap-4">
                                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex flex-col items-center text-center">
                                    <i class="fa-solid fa-droplet text-blue-400 mb-2 text-xl"></i>
                                    <p class="text-[10px] uppercase font-black text-gray-500 tracking-widest">Humidity</p>
                                    <p class="font-bold text-white text-xl"><span x-text="forecastObj[selectedDate].humidity"></span>%</p>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex flex-col items-center text-center">
                                    <i class="fa-solid fa-wind text-gray-300 mb-2 text-xl"></i>
                                    <p class="text-[10px] uppercase font-black text-gray-500 tracking-widest">Wind</p>
                                    <p class="font-bold text-white text-xl"><span x-text="forecastObj[selectedDate].windSpeed"></span> km/h</p>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex flex-col items-center text-center">
                                    <i class="fa-solid fa-gauge-high cyan-accent mb-2 text-xl"></i>
                                    <p class="text-[10px] uppercase font-black text-gray-500 tracking-widest">Pressure</p>
                                    <p class="font-bold text-white text-xl"><span x-text="forecastObj[selectedDate].pressure"></span> hPa</p>
                                </div>
                                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex flex-col items-center text-center">
                                    <i class="fa-solid fa-cloud text-gray-400 mb-2 text-xl"></i>
                                    <p class="text-[10px] uppercase font-black text-gray-500 tracking-widest">Clouds</p>
                                    <p class="font-bold text-white text-xl"><span x-text="forecastObj[selectedDate].clouds"></span>%</p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full flex flex-col bg-black/20 rounded-3xl p-8 border border-white/5">
                            <h3 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-6">Daily Temperature Trend</h3>
                            <div class="relative w-full h-[250px]">
                                <canvas id="modalChart"></canvas>
                            </div>
                        </div>

                    </div>
                </template>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <script>
        window.forecastData = <?= json_encode($forecast ?? [], JSON_NUMERIC_CHECK) ?>;
        const mainChartPoints = <?= json_encode($chartPoints ?? [], JSON_NUMERIC_CHECK) ?>;
        let modalChartInstance = null;

        window.addEventListener('load', () => {
            const ctxMain = document.getElementById('mainChart');
            if (ctxMain && mainChartPoints.length > 0) {
                const mainLabels = mainChartPoints.map(p => p.time);
                const mainTemps = mainChartPoints.map(p => p.temp);
                
                const gradMain = ctxMain.getContext('2d').createLinearGradient(0, 0, 0, 250);
                gradMain.addColorStop(0, 'rgba(34, 211, 238, 0.25)'); 
                gradMain.addColorStop(1, 'transparent');

                new Chart(ctxMain.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: mainLabels,
                        datasets: [{ 
                            data: mainTemps, borderColor: '#22d3ee', borderWidth: 3, fill: true, backgroundColor: gradMain, 
                            tension: 0.4, pointRadius: 4, pointBackgroundColor: '#22d3ee', pointBorderColor: 'rgba(255,255,255,0.1)'
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { family: 'Outfit', weight: 'bold', size: 10 } } } } }
                });
            }
        });

        window.renderModalChart = function(dateKey) {
            const dayData = window.forecastData[dateKey];
            if (!dayData || !dayData.hourly) return;

            const canvas = document.getElementById('modalChart');
            if (!canvas) return;

            const ctxModal = canvas.getContext('2d');
            
            if (modalChartInstance) {
                modalChartInstance.destroy();
            }

            const labels = dayData.hourly.map(h => h.time);
            const temps = dayData.hourly.map(h => h.temp);

            const gradModal = ctxModal.createLinearGradient(0, 0, 0, 250);
            gradModal.addColorStop(0, 'rgba(34, 211, 238, 0.3)');
            gradModal.addColorStop(1, 'transparent');

            modalChartInstance = new Chart(ctxModal, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ 
                        data: temps, borderColor: '#22d3ee', borderWidth: 3, fill: true, backgroundColor: gradModal, 
                        tension: 0.4, pointRadius: 4, pointBackgroundColor: '#22d3ee', pointBorderColor: 'rgba(255,255,255,0.1)'
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    plugins: { legend: { display: false } }, 
                    scales: { 
                        y: { display: false }, 
                        x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit', weight: 'bold', size: 10 } } } 
                    } 
                }
            });
        };
    </script>
</body>
</html>