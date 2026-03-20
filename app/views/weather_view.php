<?php
// --- 1. THEMING ENGINE v2 (PHP Logic) ---

// Default Theme
$themeClasses = "from-blue-500 via-blue-400 to-blue-600"; 
$textColor = "text-white";
$glassClass = "bg-slate-900/20 border-white/10"; 

if (isset($weather) && is_array($weather)) {
    $iconCode = $weather['icon'] ?? '01d';
    $isNight = strpos($iconCode, 'n') !== false;
    
    if ($isNight) {
        // NIGHT THEME: Deep Midnight Blue (Fixed based on your feedback)
        $themeClasses = "from-slate-900 via-indigo-950 to-slate-900";
        $glassClass = "bg-slate-950/40 border-white/10";
    } else {
        // DAY THEMES
        $code = substr($iconCode, 0, 2);
        switch ($code) {
            case '01': // Clear Sky
                // Soft Royal Blue (Fixed: Less neon, more natural)
                $themeClasses = "from-sky-500 via-blue-500 to-blue-700"; 
                break;
            
            case '02': // Few Clouds
            case '03': // Scattered Clouds
                // Muted Blue / Slate mix (Fixed: Better for eyes)
                $themeClasses = "from-slate-500 via-blue-500 to-slate-600";
                break;
            
            case '04': // Broken Clouds
                $themeClasses = "from-slate-600 via-slate-500 to-slate-700"; 
                break;
            
            case '09': // Shower Rain
            case '10': // Rain
                $themeClasses = "from-slate-800 via-slate-700 to-gray-800";
                break;
            
            case '11': // Thunderstorm
                $themeClasses = "from-gray-900 via-indigo-950 to-black";
                break;
            
            case '13': // Snow
                $themeClasses = "from-blue-200 via-slate-200 to-gray-300"; 
                $textColor = "text-slate-800"; 
                $glassClass = "bg-white/40 border-white/50";
                break;
            
            case '50': // Mist/Fog
                $themeClasses = "from-gray-600 via-slate-600 to-gray-700"; 
                break;
                
            default:
                $themeClasses = "from-blue-600 via-blue-500 to-blue-700";
        }
    }
}

// Icon Helper Function
$getIconUrl = function($iconCode) {
    return "https://openweathermap.org/img/wn/" . htmlspecialchars($iconCode) . "@4x.png";
};
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeatherApp</title>
    
    <link rel="icon" 
      href="https://openweathermap.org/img/wn/<?= htmlspecialchars($weather['icon'] ?? '02d') ?>@4x.png" 
      type="image/png" 
      sizes="192x192">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .glass-card {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br transition-all duration-1000 ease-in-out <?= $themeClasses ?> <?= $textColor ?> flex flex-col items-center py-10 selection:bg-cyan-500 selection:text-white">

    <div class="w-full max-w-md md:max-w-3xl px-4 transition-all duration-500">
        
        <div class="relative group z-50 mb-10">
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-2xl blur opacity-30 group-hover:opacity-80 transition duration-1000 group-hover:duration-200"></div>
            
            <div class="relative glass-card bg-slate-900/60 border border-white/10 rounded-2xl p-2">
                <form action="index.php" method="get" class="flex items-center">
                    <input 
                        type="text" 
                        name="city" 
                        placeholder="Unesite grad (npr. Beograd)..." 
                        value="<?= htmlspecialchars($cityName ?? '') ?>"
                        class="w-full bg-transparent p-3 outline-none text-lg font-semibold placeholder-gray-400 text-white"
                        autocomplete="off"
                    >
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white p-3 rounded-xl transition-all font-bold tracking-wide shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div x-data="{ show: true }" x-show="show" class="glass-card bg-red-900/40 border border-red-500/50 p-4 rounded-2xl mb-6 flex items-center justify-between text-red-100 animate-bounce">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
                <button @click="show = false">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!isset($error) && isset($weather) && is_array($weather)): ?>
            
            <div class="glass-card <?= $glassClass ?> rounded-[2.5rem] p-8 md:p-12 mb-8 shadow-2xl relative overflow-hidden transition-all duration-500 hover:shadow-black/20">
                
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight drop-shadow-xl">
                            <?= htmlspecialchars($weather['city_name'] ?? $cityName); ?>
                        </h1>
                        <p class="text-xl opacity-80 mt-2 font-light tracking-widest uppercase">
                            <?= date("l, d. F"); ?>
                        </p>
                    </div>
                    <div class="text-right hidden md:block">
                        <div class="bg-white/10 px-4 py-2 rounded-full backdrop-blur-md text-sm font-bold tracking-wider border border-white/10 shadow-inner">
                            LIVE
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="flex flex-col items-center md:items-start">
                        <div class="text-[8rem] leading-none font-bold tracking-tighter drop-shadow-2xl">
                            <?= round($weather['temperature']); ?>°
                        </div>
                        <div class="text-2xl font-medium opacity-90 capitalize mt-2 flex items-center gap-2">
                            <?= htmlspecialchars($weather['description']); ?>
                        </div>
                    </div>

                    <div class="relative w-64 h-64 animate-float">
                        <div class="absolute inset-0 bg-white/10 blur-[60px] rounded-full"></div>
                        <img src="<?= $getIconUrl($weather['icon']) ?>" alt="Weather" class="relative z-10 w-full h-full object-contain drop-shadow-2xl transform scale-125">
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12">
                    <div class="bg-black/20 p-4 rounded-2xl border border-white/5 backdrop-blur-sm text-center transition hover:bg-white/10">
                        <p class="text-xs uppercase tracking-widest opacity-60 mb-1">Vlažnost</p>
                        <p class="text-2xl font-bold"><?= $weather['humidity']; ?>%</p>
                    </div>
                    <div class="bg-black/20 p-4 rounded-2xl border border-white/5 backdrop-blur-sm text-center transition hover:bg-white/10">
                        <p class="text-xs uppercase tracking-widest opacity-60 mb-1">Vetar</p>
                        <p class="text-2xl font-bold"><?= round($weather['windSpeed']); ?> <span class="text-sm font-normal">km/h</span></p>
                    </div>
                    <div class="bg-black/20 p-4 rounded-2xl border border-white/5 backdrop-blur-sm text-center transition hover:bg-white/10">
                        <p class="text-xs uppercase tracking-widest opacity-60 mb-1">Osećaj</p>
                        <p class="text-2xl font-bold"><?= round($weather['feelsLike']); ?>°</p>
                    </div>
                    <div class="bg-black/20 p-4 rounded-2xl border border-white/5 backdrop-blur-sm text-center transition hover:bg-white/10">
                        <p class="text-xs uppercase tracking-widest opacity-60 mb-1">Pritisak</p>
                        <p class="text-2xl font-bold">1013 <span class="text-sm font-normal">hPa</span></p>
                    </div>
                </div>

                <?php 
                if (isset($weather['sys']['sunrise']) && isset($weather['sys']['sunset'])): 
                    // Calculate local time for sun events
                    $timezone = $weather['timezone'] ?? 0;
                    $sunrise = gmdate('H:i', $weather['sys']['sunrise'] + $timezone);
                    $sunset = gmdate('H:i', $weather['sys']['sunset'] + $timezone);
                ?>
                <div x-data="{ open: false }" class="mt-6 text-center">
                    <button @click="open = true" class="text-sm opacity-60 hover:opacity-100 transition-opacity underline decoration-dotted outline-none">
                        Vreme izlaska i zalaska sunca &rarr;
                    </button>

                    <div x-show="open" 
                         x-transition.opacity
                         class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4"
                         style="display: none;">
                        
                        <div @click.away="open = false" 
                             class="bg-slate-900/95 rounded-3xl p-8 max-w-sm w-full shadow-2xl border border-white/10 text-center">
                            
                            <h3 class="text-xl font-bold mb-6 text-white">Sunce danas</h3>
                            
                            <div class="flex justify-around items-center mb-6">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-yellow-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" />
                                    </svg>
                                    <span class="text-slate-400 text-sm uppercase tracking-wider">Izlazak</span>
                                    <span class="text-2xl font-bold text-white"><?= $sunrise ?></span>
                                </div>
                                
                                <div class="h-12 w-px bg-white/20"></div>

                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-orange-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                    <span class="text-slate-400 text-sm uppercase tracking-wider">Zalazak</span>
                                    <span class="text-2xl font-bold text-white"><?= $sunset ?></span>
                                </div>
                            </div>

                            <button @click="open = false" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold transition-colors">Zatvori</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($forecast)): ?>
                <div class="glass-card <?= $glassClass ?> rounded-3xl p-6 md:p-8">
                    <h3 class="text-xl font-bold mb-6 opacity-90 pl-2 border-l-4 border-current">Prognoza za 5 dana</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <?php foreach ($forecast as $day => $data): ?>
                            <div class="flex flex-col items-center p-4 rounded-2xl bg-black/20 border border-white/5 hover:bg-white/10 transition transform hover:-translate-y-1 duration-300">
                                <span class="text-sm font-semibold opacity-70 mb-1"><?= date("D", strtotime($day)); ?></span>
                                <span class="text-xs opacity-50 mb-2"><?= date("d.m.", strtotime($day)); ?></span>
                                
                                <img src="<?= $getIconUrl($data['icon']) ?>" class="w-12 h-12 mb-2 drop-shadow-md">
                                
                                <div class="flex items-center gap-2 font-bold">
                                    <span class="text-lg"><?= round($data['max_temperature']); ?>°</span>
                                    <span class="text-sm opacity-50"><?= round($data['min_temperature']); ?>°</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <div class="mt-12 text-center opacity-40 text-sm font-light hover:opacity-100 transition-opacity">
            <p>&copy; <?= date("Y"); ?> WeatherApp • DevOps Powered by AWS & Docker</p>
        </div>

    </div>

</body>
</html>