<?php
// $cities, $cityName, $weather, $forecast postoje iz kontrolera
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vremenska Prognoza</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="/assets/js/darkmode.js"></script>
</head>
<body class="<?php echo $weatherConditionClass ?? ''; ?>">
    <div class="container">
        <button id="toggleDarkMode">Noćni režim</button>
        <h1>Vremenska Prognoza</h1>

      <form action="index.php" method="get" class="search-form">
    <input type="text" id="city" name="city" placeholder="Unesite grad..." value="<?= htmlspecialchars($cityName ?? '') ?>">
    <input type="submit" value="Pretraga">
</form>


        <div class="weather-info">
            <?php if (isset($cityName) && isset($weather)): ?>
                <h2><?= htmlspecialchars($cityName); ?></h2>
                <hr>
                <div class="current-weather">
                    <?php
                        $weatherIcon = $weather['icon'];
                        if (in_array($weatherIcon, ["01d","01n"])) {
                            $iconPath = "/assets/images/sun.png";
                        } elseif (in_array($weatherIcon, ["02d","02n","03d","03n"])) {
                            $iconPath = "/assets/images/cloudy.png";
                        } elseif (in_array($weatherIcon, ["04d","04n"])) {
                            $iconPath = "/assets/images/overcast.png";
                        } elseif (in_array($weatherIcon, ["09d","09n","10d","10n"])) {
                            $iconPath = "/assets/images/rain.png";
                        } elseif (in_array($weatherIcon, ["11d","11n"])) {
                            $iconPath = "/assets/images/storm.png";
                        } else {
                            $iconPath = "https://openweathermap.org/img/wn/" . htmlspecialchars($weatherIcon) . "@2x.png";
                        }
                    ?>
                    <img src="<?= $iconPath ?>" alt="Weather Icon" style="width: 150px;">
                    <p class="temperature"><?= round($weather['temperature']); ?>°C</p>
                    <p class="details">
                        Vlažnost: <?= $weather['humidity']; ?>%<br>
                        Lokalno vreme: <?= $weather['local_time']; ?><br>
                        Brzina vetra: <?= $weather['windSpeed']; ?> km/h<br>
                        Osećaj temperature: <?= round($weather['feelsLike']); ?>°C<br>
                        Vidljivost: <?= $weather['visibility']; ?> km<br>
                    </p>
                </div>
            <?php endif; ?>

            <?php
                $weatherDescription = $weatherDescriptions[$weather['icon']] ?? 'Opis nije dostupan.';
            ?>
            <button id="weatherDescriptionBtn">Pročitaj više o vremenu danas</button>
            <div id="weatherDescriptionModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p><?= $weatherDescription; ?></p>
                </div>
            </div>

            <hr>
            <h2>Prognoza za narednih 5 dana</h2>

            <?php if (isset($forecast)): ?>
                <div class="forecast">
                    <?php foreach ($forecast as $day => $data): ?>
                        <div class="forecast-day">
                            <p class="date"><?= date("d.m.Y", strtotime($day)); ?></p>
                            <?php
                                $fIcon = $data['icon'];
                                if (in_array($fIcon, ["01d","01n"])) {
                                    $fPath = "/assets/images/sun.png";
                                } elseif (in_array($fIcon, ["02d","02n","03d","03n"])) {
                                    $fPath = "/assets/images/cloudy.png";
                                } elseif (in_array($fIcon, ["04d","04n"])) {
                                    $fPath = "/assets/images/overcast.png";
                                } elseif (in_array($fIcon, ["09d","09n","10d","10n"])) {
                                    $fPath = "/assets/images/rain.png";
                                } elseif (in_array($fIcon, ["11d","11n"])) {
                                    $fPath = "/assets/images/storm.png";
                                } else {
                                    $fPath = "https://openweathermap.org/img/wn/" . htmlspecialchars($fIcon) . "@2x.png";
                                }
                            ?>
                            <img src="<?= $fPath ?>" alt="Weather Icon" style="width: 90px;">
                            <p>Najviša: <?= round($data['max_temperature']); ?>°C</p>
                            <p>Najniža: <?= round($data['min_temperature']); ?>°C</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/assets/js/modal.js"></script>
</body>
</html>
