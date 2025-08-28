<?php
require_once 'cloaking.php';

echo "<h1>🧪 Test Enkele Logging</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>";

// Clear de data eerst
$cloaking = new CloakingSystem();
$cloaking->clearMonitoringData();

echo "<p>✅ Monitoring data geleegd voor test</p>";

// Simuleer een bezoeker
checkCloaking();

echo "<p>✅ checkCloaking() aangeroepen (simuleert bezoeker)</p>";

// Check hoeveel entries er zijn
$data = $cloaking->getLiveMonitoringData(10);

echo "<h2>Resultaat:</h2>";
echo "<p><strong>Aantal entries:</strong> " . count($data) . "</p>";

if (count($data) == 1) {
    echo "<p style='color: green;'>✅ SUCCESS: Maar één entry! Dubbele logging is opgelost.</p>";
    echo "<h3>Entry details:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($data[0], JSON_PRETTY_PRINT)) . "</pre>";
} elseif (count($data) == 2) {
    echo "<p style='color: red;'>❌ PROBLEEM: Nog steeds dubbele entries</p>";
    echo "<h3>Beide entries:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ Onverwacht aantal entries: " . count($data) . "</p>";
}

echo "<p><a href='admin/dashboard.php?tab=monitoring'>Bekijk in Dashboard</a></p>";
?> 