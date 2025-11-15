<?php
// Variables $cityName, $weather, $forecast, $error are passed from the Controller
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vremenska Prog</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    </head>
<body>
    <div class="container">
        <button id="toggleDarkMode">Noćni režim</button>
        <h1>Vremenska Prog</h1>

        <form action="index.php" method="get" class="search-form">
            <input type="text" id="city" name="city" placeholder="Unesite grad..." value="<?= htmlspecialchars($cityName ?? '') ?>">
            <input type="submit" value="Pretraga">
        </form>

        <?php if (isset($error)): ?>
            <div class="error-message" style="color: red; margin: 20px 0; padding: 15px; border: 1px solid red; border-radius: 5px; background-color: #fee;">
                <strong>Greška:</strong> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($error) && isset($weather) && is_array($weather)): ?>
            <div class="weather-info">
                <h2><?= htmlspecialchars($weather['city_name'] ?? $cityName); ?></h2>
                <hr>
                <div class="current-weather">
                    <?php
                        // Helper function for icons
                        $getIconPath = function($iconCode) {
                             if (in_array($iconCode, ["01d","01n"])) return "/assets/images/sun.png";
                             if (in_array($iconCode, ["02d","02n","03d","03n"])) return "/assets/images/cloudy.png";
                             if (in_array($iconCode, ["04d","04n"])) return "/assets/images/overcast.png";
                             if (in_array($iconCode, ["09d","09n","10d","10n"])) return "/assets/images/rain.png";
                             if (in_array($iconCode, ["11d","11n"])) return "/assets/images/storm.png";
                             return "https://openweathermap.org/img/wn/" . htmlspecialchars($iconCode) . "@2x.png";
                        };
                        $iconPath = $getIconPath($weather['icon']);
                    ?>
                    <img src="<?= $iconPath ?>" alt="Weather Icon" style="width: 150px;">
                    
                    <p class="temperature">
                        <?= is_numeric($weather['temperature']) ? round($weather['temperature']) . '°C' : $weather['temperature']; ?>
                    </p>
                    
                    <p class="details">
                        Vlažnost: <?= $weather['humidity']; ?>%<br>
                        Lokalno vreme: <?= $weather['local_time']; ?><br>
                        Brzina vetra: <?= $weather['windSpeed']; ?> km/h<br>
                        Osećaj temperature: <?= is_numeric($weather['feelsLike']) ? round($weather['feelsLike']) . '°C' : $weather['feelsLike']; ?><br>
                        Vidljivost: <?= $weather['visibility']; ?> km<br>
                    </p>
                </div>

                <button id="weatherDescriptionBtn">Pročitaj više o vremenu danas</button>
                <div id="weatherDescriptionModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <p><?= htmlspecialchars($weather['description'] ?? 'Opis nije dostupan.'); ?></p>
                    </div>
                </div>

                <hr>
                <h2>Prognoza za narednih 5 dana</h2>

                <?php if (!empty($forecast)): ?>
                    <div class="forecast">
                        <?php foreach ($forecast as $day => $data): ?>
                            <div class="forecast-day">
                                <p class="date">
                                    <?= htmlspecialchars($data['day_name'] ?? '') ?> <br>
                                    <?= date("d.m.", strtotime($day)); ?>
                                </p>
                                <img src="<?= $getIconPath($data['icon']) ?>" alt="Weather Icon" style="width: 90px;">
                                <p>Najviša: <?= is_numeric($data['max_temperature']) ? round($data['max_temperature']) . '°C' : $data['max_temperature']; ?></p>
                                <p>Najniža: <?= is_numeric($data['min_temperature']) ? round($data['min_temperature']) . '°C' : $data['min_temperature']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Prognoza trenutno nije dostupna.</p>
                <?php endif; ?>
            </div>
        <?php endif; // End if (!isset($error) && isset($weather)) ?>

    </div>

    <script src="/assets/js/darkmode.js"></script>
    <?php if (!isset($error) && isset($weather)): ?>
        <script src="/assets/js/modal.js"></script>
    <?php endif; ?>
</body>
</html>