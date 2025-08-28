<?php
// Test de tijdzone fix
date_default_timezone_set('Europe/Amsterdam');

echo "<h1>🕐 Tijdzone Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>";

echo "<h2>Huidige Tijd Informatie</h2>";
echo "<p><strong>Server tijdzone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Huidige datum/tijd:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Timestamp:</strong> " . time() . "</p>";
echo "<p><strong>UTC tijd:</strong> " . gmdate('Y-m-d H:i:s') . "</p>";

// Test de cloaking functie
require_once 'cloaking.php';

echo "<h2>Test Cloaking Logging</h2>";
$cloaking = new CloakingSystem();

// Genereer een test log entry
$cloaking->logVisitDetailed('test_timezone', '127.0.0.1', 'NL', 'Test Browser');

echo "<p>✅ Test log entry gegenereerd met huidige tijdzone</p>";

// Haal de laatste monitoring data op
$data = $cloaking->getLiveMonitoringData(5);
if (!empty($data)) {
    echo "<h3>Laatste Log Entry:</h3>";
    $lastEntry = $data[0];
    echo "<p><strong>Timestamp:</strong> " . $lastEntry['timestamp'] . "</p>";
    echo "<p><strong>Datetime:</strong> " . $lastEntry['datetime'] . "</p>";
    echo "<p><strong>Converted back:</strong> " . date('Y-m-d H:i:s', $lastEntry['timestamp']) . "</p>";
} else {
    echo "<p>❌ Geen monitoring data gevonden</p>";
}

echo "<h2>Vergelijking</h2>";
echo "<p>Als de tijd nu correct is, zou 'Datetime' gelijk moeten zijn aan 'Huidige datum/tijd' hierboven.</p>";
echo "<p><a href='admin/dashboard.php?tab=monitoring'>Bekijk in Admin Dashboard</a></p>";
?> 