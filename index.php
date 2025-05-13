<?php
// Fetch senseBox data
$boxId = "5c695736a1008400192b7326";
$apiUrl = "https://api.opensensemap.org/boxes/$boxId";
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

// Get location
$location = $data['currentLocation']['coordinates'];
$longitude = $location[0];
$latitude = $location[1];

// Reverse geocoding (OpenStreetMap)
$geoUrl = "https://nominatim.openstreetmap.org/reverse?lat=$latitude&lon=$longitude&format=json";
$options = ['http' => ['header' => "User-Agent: SenseMapApp"]];
$context = stream_context_create($options);
$geoData = json_decode(file_get_contents($geoUrl, false, $context), true);
$country = $geoData['address']['country'] ?? 'Unknown';

// Prepare chart data
$sensors = $data['sensors'];
$labels = [];
$values = [];
foreach ($sensors as $sensor) {
    $labels[] = $sensor['title'];
    $values[] = isset($sensor['lastMeasurement']['value']) ? floatval($sensor['lastMeasurement']['value']) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($data['name']) ?> - Sensor Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    #map { height: 300px; border-radius: 8px; }
    #countdown { font-size: 20px; color: #007bff; }
  </style>
</head>
<body>
<div class="container py-5">
  <h1 class="text-center mb-4"><?= htmlspecialchars($data['name']) ?></h1>

  <div class="mb-3 text-center">
    <p><strong>Country:</strong> <?= htmlspecialchars($country) ?></p>
    <p><strong>Latitude:</strong> <?= $latitude ?>, <strong>Longitude:</strong> <?= $longitude ?></p>
  </div>

  <div id="map" class="mb-5"></div>

  <div class="text-center mb-4">
    <p id="countdown">Refreshing in 60 seconds...</p>
  </div>

  <div class="mb-5">
    <canvas id="sensorChart"></canvas>
  </div>

  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($sensors as $sensor): ?>
      <div class="col">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($sensor['title']) ?></h5>
            <p class="card-text">
              <strong>Value:</strong> <?= htmlspecialchars($sensor['lastMeasurement']['value'] ?? 'N/A') ?>
              <?= htmlspecialchars($sensor['unit'] ?? '') ?><br>
              <strong>Updated:</strong> <?= date('Y-m-d H:i:s', strtotime($sensor['lastMeasurement']['createdAt'] ?? '')) ?>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  // Display map
  const map = L.map('map').setView([<?= $latitude ?>, <?= $longitude ?>], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  L.marker([<?= $latitude ?>, <?= $longitude ?>]).addTo(map)
    .bindPopup("<?= htmlspecialchars($data['name']) ?>").openPopup();

  // Display chart with initial data
  let labels = <?= json_encode($labels) ?>;
  let values = <?= json_encode($values) ?>;
  
  const ctx = document.getElementById('sensorChart').getContext('2d');
  let sensorChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Sensor Values',
        data: values,
        backgroundColor: 'rgba(75, 192, 192, 0.6)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Value' }
        }
      }
    }
  });

  // Function to update the chart
  function updateChart() {
    $.ajax({
      url: 'fetch_sensor_data.php', // File to fetch updated sensor data (can be PHP or external source)
      method: 'GET',
      success: function(response) {
        const newData = JSON.parse(response);
        labels = newData.labels;
        values = newData.values;
        
        // Update chart data
        sensorChart.data.labels = labels;
        sensorChart.data.datasets[0].data = values;
        sensorChart.update();
      }
    });
  }

  // Countdown timer and refresh mechanism
  let countdownTime = 60;
  const countdownElement = $('#countdown');
  function startCountdown() {
    countdownElement.text(`Refreshing in ${countdownTime} seconds...`);
    if (countdownTime <= 0) {
      updateChart();  // Refresh chart
      countdownTime = 60;  // Reset countdown
    }
    countdownTime--;
  }

  // Start countdown and refresh every 60 seconds
  setInterval(startCountdown, 1000);

</script>
</body>
</html>
