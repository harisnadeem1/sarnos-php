<?php
require_once 'cloaking.php';

echo "<h1>🌍 Geolocation Nauwkeurigheid Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test-result { padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; }
.warning { background: #fff3cd; color: #856404; }
.info { background: #e3f2fd; color: #0d47a1; }
.error { background: #f8d7da; color: #721c24; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

$cloaking = new CloakingSystem();
$testIP = $cloaking->getVisitorIP();

echo "<div class='test-result info'>";
echo "<h2>🔍 Huidige Test</h2>";
echo "<p><strong>Jouw IP:</strong> " . htmlspecialchars($testIP) . "</p>";
echo "</div>";

// Test verschillende bekende IP's
$testIPs = [
    '8.8.8.8' => 'Google DNS (VS)',
    '1.1.1.1' => 'Cloudflare DNS (VS)', 
    '208.67.222.222' => 'OpenDNS (VS)',
    '84.200.69.80' => 'Nederlandse provider',
    '213.75.112.100' => 'Nederlandse provider',
    $testIP => 'Jouw huidige IP'
];

echo "<div class='test-result info'>";
echo "<h2>📊 Multi-Source Geolocation Test</h2>";
echo "<p>Test van verschillende IP-adressen met verbeterde detectie:</p>";

echo "<table>";
echo "<tr><th>IP Adres</th><th>Beschrijving</th><th>Gedetecteerd Land</th><th>Status</th></tr>";

foreach ($testIPs as $ip => $description) {
    echo "<tr>";
    echo "<td style='font-family: monospace;'>" . htmlspecialchars($ip) . "</td>";
    echo "<td>" . htmlspecialchars($description) . "</td>";
    
    $startTime = microtime(true);
    $detectedCountry = $cloaking->getCountryFromIP($ip);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<td style='font-weight: bold;'>" . htmlspecialchars($detectedCountry) . "</td>";
    
    if ($detectedCountry === 'UNKNOWN') {
        echo "<td style='color: #dc3545;'>❌ Niet gedetecteerd ({$responseTime}ms)</td>";
    } else {
        echo "<td style='color: #28a745;'>✅ Gedetecteerd ({$responseTime}ms)</td>";
    }
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Toon geolocation accuracy logs
$geolocationLogFile = 'geolocation_accuracy.json';
if (file_exists($geolocationLogFile)) {
    $logs = json_decode(file_get_contents($geolocationLogFile), true) ?? [];
    $failures = array_filter($logs, function($log) { return $log['type'] === 'failure'; });
    $discrepancies = array_filter($logs, function($log) { return $log['type'] === 'discrepancy'; });
    
    echo "<div class='test-result warning'>";
    echo "<h2>📈 Geolocation Statistieken</h2>";
    echo "<p><strong>Totaal logs:</strong> " . count($logs) . "</p>";
    echo "<p><strong>API Failures:</strong> " . count($failures) . " (" . round((count($failures) / max(count($logs), 1)) * 100, 1) . "%)</p>";
    echo "<p><strong>Bron Discrepanties:</strong> " . count($discrepancies) . " (" . round((count($discrepancies) / max(count($logs), 1)) * 100, 1) . "%)</p>";
    
    $successRate = round(((count($logs) - count($failures)) / max(count($logs), 1)) * 100, 1);
    echo "<p><strong>Geschatte Nauwkeurigheid:</strong> ~{$successRate}%</p>";
    echo "</div>";
    
    if (!empty($discrepancies)) {
        echo "<div class='test-result warning'>";
        echo "<h3>🔍 Recente Discrepanties tussen Bronnen</h3>";
        echo "<table>";
        echo "<tr><th>Tijd</th><th>IP</th><th>Verschillende Resultaten</th><th>Gekozen</th></tr>";
        
        foreach (array_slice($discrepancies, -10) as $disc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($disc['datetime']) . "</td>";
            echo "<td style='font-family: monospace;'>" . htmlspecialchars($disc['ip']) . "</td>";
            echo "<td>" . implode(', ', $disc['detected_countries']) . "</td>";
            echo "<td style='font-weight: bold;'>" . htmlspecialchars($disc['chosen_country']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
} else {
    echo "<div class='test-result info'>";
    echo "<h2>📊 Geolocation Statistieken</h2>";
    echo "<p>Nog geen accuracy logs beschikbaar. Test een paar IP's om data te genereren.</p>";
    echo "</div>";
}

// Test specifiek IP
if (isset($_GET['test_ip']) && !empty($_GET['test_ip'])) {
    $testSpecificIP = $_GET['test_ip'];
    echo "<div class='test-result info'>";
    echo "<h2>🧪 Specifieke IP Test</h2>";
    echo "<p><strong>Test IP:</strong> " . htmlspecialchars($testSpecificIP) . "</p>";
    
    $result = $cloaking->getCountryFromIP($testSpecificIP);
    echo "<p><strong>Resultaat:</strong> " . htmlspecialchars($result) . "</p>";
    echo "</div>";
}

echo "<div class='test-result info'>";
echo "<h2>🧪 Custom IP Test</h2>";
echo "<form method='GET'>";
echo "<input type='text' name='test_ip' placeholder='Voer een IP adres in (bijv. 8.8.8.8)' style='padding: 8px; margin-right: 10px; width: 200px;'>";
echo "<button type='submit' style='padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px;'>Test IP</button>";
echo "</form>";
echo "</div>";

echo "<div class='test-result success'>";
echo "<h2>✅ Verbeteringen Geïmplementeerd</h2>";
echo "<ul>";
echo "<li><strong>Multi-source verificatie:</strong> 3 verschillende geolocation API's</li>";
echo "<li><strong>Consensus logica:</strong> Als meerdere bronnen hetzelfde zeggen = betrouwbaarder</li>";
echo "<li><strong>Failure tracking:</strong> Logs wanneer alle API's falen</li>";
echo "<li><strong>Discrepancy monitoring:</strong> Bijhouden van tegenstrijdige resultaten</li>";
echo "<li><strong>Performance optimized:</strong> Snelle timeouts en fallbacks</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='admin/dashboard.php?tab=cloaking&debug=1'>🔙 Terug naar Admin (Debug Mode)</a> | <a href='test_cloaking.php'>🧪 Cloaking Test</a></p>";
?> 