<!DOCTYPE html>
<html>
<head>
    <title>List of Cities</title>
</head>
<body>
    <h1>List of Cities</h1>

    <?php if (!empty($cities)): ?>
        <ul>
            <?php foreach ($cities as $city): ?>
                <li><?php echo htmlspecialchars($city['name']); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No cities found.</p>
    <?php endif; ?>
</body>
</html>
