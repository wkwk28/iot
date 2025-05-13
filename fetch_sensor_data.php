<?php
$boxId = "5c695736a1008400192b7326";
$apiUrl = "https://api.opensensemap.org/boxes/$boxId";
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Prepare chart data
$sensors = $data['sensors'];
$labels = [];
$values = [];
foreach ($sensors as $sensor) {
    $labels[] = $sensor['title'];
    $values[] = isset($sensor['lastMeasurement']['value']) ? floatval($sensor['lastMeasurement']['value']) : 0;
}

echo json_encode(['labels' => $labels, 'values' => $values]);
?>
