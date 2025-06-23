<?php
require_once '../config/config.php';
$locations = [];
$result = mysqli_query($connection, "SELECT id, name, address, latitude, longitude, neighborhood FROM locations WHERE is_active = 1");
while ($row = mysqli_fetch_assoc($result)) {
    $locations[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puncte de Colectare - Harta</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../public/css/locations.css">
</head>
<body>
    <div class="header">
        <h1>📍 Puncte de Colectare - Harta</h1>
        <p>Vezi toate punctele de colectare active pe harta. Click pe un marker pentru detalii.</p>
    </div>
    <div id="mapid"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>var locations = <?php echo json_encode($locations); ?>;</script>
    <script src="../public/js/location.js"></script>
</body>
</html>
