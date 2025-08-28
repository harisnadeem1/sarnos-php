<?php
require_once 'cloaking.php';

echo "<h1>🔧 Force Monitoring Data Test</h1>";

$cloaking = new CloakingSystem();

echo "<h2>1️⃣ Check Current Data</h2>";
$currentData = $cloaking->getLiveMonitoringData(10);
echo "<p><strong>Current entries:</strong> " . count($currentData) . "</p>";

echo "<h2>2️⃣ Force Test Entries</h2>";

// Force verschillende test entries
$testEntries = [
    ['action' => 'country_allowed', 'ip' => '::1', 'country' => 'NL', 'ua' => 'Mozilla/5.0 Test'],
    ['action' => 'country_blocked', 'ip' => '203.0.113.1', 'country' => 'US', 'ua' => 'Mozilla/5.0 Blocked'],
    ['action' => 'ip_whitelisted', 'ip' => '192.168.1.100', 'country' => 'DE', 'ua' => 'Mozilla/5.0 Whitelisted'],
    ['action' => 'test_blocked_FR', 'ip' => '203.0.113.2', 'country' => 'FR', 'ua' => 'Mozilla/5.0 Test'],
    ['action' => 'country_allowed', 'ip' => '::1', 'country' => 'NL', 'ua' => 'Mozilla/5.0 Local']
];

foreach ($testEntries as $i => $entry) {
    echo "<p>✅ Forcing entry " . ($i + 1) . ": " . $entry['action'] . " (" . $entry['ip'] . " - " . $entry['country'] . ")</p>";
    $cloaking->logVisitDetailed($entry['action'], $entry['ip'], $entry['country'], $entry['ua']);
}

echo "<h2>3️⃣ Check After Force</h2>";
$newData = $cloaking->getLiveMonitoringData(10);
$stats = $cloaking->getMonitoringStats();

echo "<p><strong>New entries:</strong> " . count($newData) . "</p>";
echo "<p><strong>Stats:</strong></p>";
echo "<ul>";
echo "<li>Totaal: " . $stats['total'] . "</li>";
echo "<li>Toegelaten: " . $stats['toegelaten'] . "</li>";
echo "<li>Cloaked: " . $stats['cloaked'] . "</li>";
echo "<li>Geblokkeerd: " . $stats['geblokkeerd'] . "</li>";
echo "<li>Laatste 24u: " . $stats['last_24h'] . "</li>";
echo "</ul>";

echo "<h2>4️⃣ Recent Entries</h2>";
if (!empty($newData)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Tijd</th><th>IP</th><th>Land</th><th>Status</th><th>Actie</th><th>Beschrijving</th>";
    echo "</tr>";
    
    foreach (array_slice($newData, 0, 5) as $entry) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($entry['datetime']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['ip']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['country']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['status']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['action']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['description']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>5️⃣ File Check</h2>";
$logFile = 'live_monitoring.json';
echo "<p><strong>File exists:</strong> " . (file_exists($logFile) ? '✅ YES' : '❌ NO') . "</p>";
if (file_exists($logFile)) {
    echo "<p><strong>File size:</strong> " . filesize($logFile) . " bytes</p>";
    echo "<p><strong>File readable:</strong> " . (is_readable($logFile) ? '✅ YES' : '❌ NO') . "</p>";
    echo "<p><strong>File writable:</strong> " . (is_writable($logFile) ? '✅ YES' : '❌ NO') . "</p>";
}

echo "<h2>6️⃣ Test Normale Functie</h2>";
echo "<p>Test de normale checkCloaking() functie:</p>";

// Test normale functie
$_SERVER['REQUEST_URI'] = '/force_monitoring_data.php';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Force Test Browser';

echo "<p>🔄 Calling checkCloaking()...</p>";
$result = checkCloaking();
echo "<p>✅ checkCloaking() completed. Result: " . ($result ? 'Cloaked' : 'Allowed') . "</p>";

// Check opnieuw
$finalData = $cloaking->getLiveMonitoringData(10);
echo "<p><strong>Final entries count:</strong> " . count($finalData) . "</p>";

echo "<div style='margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 5px;'>";
echo "<h3>🎯 Test Resultaat</h3>";
echo "<p>Als je nu naar het Live Monitoring Dashboard gaat, zou je data moeten zien!</p>";
echo "<p><a href='admin/dashboard.php?tab=monitoring' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Open Live Monitoring Dashboard</a></p>";
echo "<p><a href='test_monitoring_ajax.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🧪 Test AJAX</a></p>";
echo "</div>";
?> 