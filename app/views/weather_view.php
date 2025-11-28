<?php
// Helper function for icons (moved to top for cleaner HTML)
$getIconPath = function($iconCode) {
    $mapping = [
        "01d" => "/assets/images/sun.png", "01n" => "/assets/images/sun.png",
        "02d" => "/assets/images/cloudy.png", "02n" => "/assets/images/cloudy.png",
        "03d" => "/assets/images/cloudy.png", "03n" => "/assets/images/cloudy.png",
        "04d" => "/assets/images/overcast.png", "04n" => "/assets/images/overcast.png",
        "09d" => "/assets/images/rain.png", "09n" => "/assets/images/rain.png",
        "10d" => "/assets/images/rain.png", "10n" => "/assets/images/rain.png",
        "11d" => "/assets/images/storm.png", "11n" => "/assets/images/storm.png"
    ];
    
    if (array_key_exists($iconCode, $mapping)) {
        // Fallback if local images don't exist, use CDN directly for reliability
        // return $mapping[$iconCode]; 
        // For this demo, let's use OpenWeather icons directly to ensure they load if you don't have assets uploaded
        return "https://openweathermap.org/img/wn/" . htmlspecialchars($iconCode) . "@4x.png";
    }
    return "https://openweathermap.org/img/wn/" . htmlspecialchars($iconCode) . "@4x.png";
};
?>
<!DOCTYPE html>
<html lang="sr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vremenska Prognoza | DevOps Edition</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        glass: 'rgba(255, 255, 255, 0.15)',
                        glassDark: 'rgba(15, 23, 42, 0.6)'
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        
        /* Glassmorphism Background */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body 
    x-data="{ 
        darkMode: localStorage.getItem('theme') === 'dark',
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');"
    class="min-h-screen transition-colors duration-300 bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-slate-900 dark:to-slate-800 text-slate-800 dark:text-slate-100 font-sans"
>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <header class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-500 rounded-xl shadow-lg shadow-blue-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">Weather<span class="text-blue-600 dark:text-blue-400">App</span></h1>
            </div>

            <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/10 transition-colors">
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg x-show="darkMode" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </header>

        <div class="glass-panel p-2 rounded-2xl shadow-xl mb-10 transition-all hover:shadow-2xl">
            <form action="index.php" method="get" class="flex w-full">
                <input 
                    type="text" 
                    name="city" 
                    placeholder="Unesite ime grada (npr. Beograd)..." 
                    value="<?= htmlspecialchars($cityName ?? '') ?>"
                    class="w-full bg-transparent p-4 outline-none text-lg placeholder-slate-400 dark:text-white"
                    autocomplete="off"
                >
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 rounded-xl font-semibold transition-transform active:scale-95">
                    Traži
                </button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 shadow-sm" role="alert">
                <p class="font-bold">Greška</p>
                <p><?= htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!isset($error) && isset($weather) && is_array($weather)): ?>
            
            <div class="glass-panel rounded-3xl p-8 mb-8 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-blue-400 opacity-20 blur-3xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between">
                    <div class="text-center md:text-left mb-6 md:mb-0">
                        <h2 class="text-4xl md:text-5xl font-bold mb-2"><?= htmlspecialchars($weather['city_name'] ?? $cityName); ?></h2>
                        <p class="text-slate-500 dark:text-slate-400 text-lg"><?= date("l, d. F Y."); ?></p>
                        
                        <div class="flex items-center justify-center md:justify-start mt-4">
                            <span class="text-7xl md:text-8xl font-bold tracking-tighter">
                                <?= is_numeric($weather['temperature']) ? round($weather['temperature']) : $weather['temperature']; ?>°
                            </span>
                        </div>
                        <p class="text-blue-500 dark:text-blue-300 font-medium mt-2 capitalize"><?= htmlspecialchars($weather['description'] ?? ''); ?></p>
                    </div>

                    <div class="relative">
                        <img src="<?= $getIconPath($weather['icon']) ?>" alt="Weather Icon" class="w-48 h-48 drop-shadow-2xl floating-animation">
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center backdrop-blur-sm">
                        <p class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider mb-1">Vlažnost</p>
                        <p class="text-xl font-bold"><?= $weather['humidity']; ?>%</p>
                    </div>
                    <div class="bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center backdrop-blur-sm">
                        <p class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider mb-1">Vetar</p>
                        <p class="text-xl font-bold"><?= $weather['windSpeed']; ?> <span class="text-sm font-normal">km/h</span></p>
                    </div>
                    <div class="bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center backdrop-blur-sm">
                        <p class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider mb-1">Osećaj</p>
                        <p class="text-xl font-bold"><?= round($weather['feelsLike']); ?>°</p>
                    </div>
                    <div class="bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center backdrop-blur-sm">
                        <p class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider mb-1">Vidljivost</p>
                        <p class="text-xl font-bold"><?= number_format($weather['visibility'] / 1000, 1); ?> <span class="text-sm font-normal">km</span></p>
                    </div>
                </div>

                <div x-data="{ open: false }" class="mt-6 text-center">
                    <button @click="open = true" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        Detaljniji opis vremena &rarr;
                    </button>

                    <div x-show="open" 
                         x-transition.opacity
                         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                         style="display: none;">
                        
                        <div @click.away="open = false" 
                             class="bg-white dark:bg-slate-800 rounded-2xl p-6 max-w-sm w-full shadow-2xl transform transition-all">
                            <h3 class="text-lg font-bold mb-2">Detalji</h3>
                            <p class="text-slate-600 dark:text-slate-300">
                                <?= htmlspecialchars($weather['description'] ?? 'Nema dodatnog opisa.'); ?>
                            </p>
                            <button @click="open = false" class="mt-4 w-full bg-slate-200 dark:bg-slate-700 py-2 rounded-lg font-medium">Zatvori</button>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="text-xl font-bold mb-4 px-2">Narednih 5 dana</h3>
            
            <?php if (!empty($forecast)): ?>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <?php foreach ($forecast as $day => $data): ?>
                        <div class="glass-panel p-4 rounded-2xl flex flex-col items-center justify-center text-center hover:bg-white/40 dark:hover:bg-slate-700/40 transition-colors">
                            <p class="text-sm font-semibold mb-1"><?= date("D", strtotime($day)); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2"><?= date("d.m.", strtotime($day)); ?></p>
                            
                            <img src="<?= $getIconPath($data['icon']) ?>" alt="Icon" class="w-12 h-12 mb-2">
                            
                            <div class="flex gap-2 text-sm">
                                <span class="font-bold"><?= round($data['max_temperature']); ?>°</span>
                                <span class="text-slate-500 dark:text-slate-400"><?= round($data['min_temperature']); ?>°</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-slate-500">Prognoza nije dostupna.</p>
            <?php endif; ?>

        <?php endif; ?>
        
        <footer class="mt-12 text-center text-slate-400 text-xs">
            <p>Powered by OpenWeatherMap • Dockerized on AWS</p>
        </footer>

    </div>
</body>
</html>