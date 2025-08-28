<?php
require_once 'cloaking.php';

echo "<h1>🔍 Cloaking Configuratie Debug</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }</style>";

echo "<div class='info'>";
echo "<h2>📍 Directory Informatie</h2>";
echo "<p><strong>Huidige directory:</strong> " . htmlspecialchars(getcwd()) . "</p>";
echo "<p><strong>Basename directory:</strong> " . htmlspecialchars(basename(getcwd())) . "</p>";
echo "</div>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='info'>";
echo "<h2>📂 Config Bestand Locaties</h2>";

// Check verschillende mogelijke locaties
$locations = [
    'cloaking_config.json',
    '../cloaking_config.json', 
    'admin/cloaking_config.json'
];

foreach ($locations as $location) {
    $exists = file_exists($location);
    echo "<p><strong>" . htmlspecialchars($location) . ":</strong> ";
    if ($exists) {
        $content = file_get_contents($location);
        $data = json_decode($content, true);
        echo "✅ Bestaat - Enabled: " . ($data['enabled'] ?? 'undefined') . ", Countries: " . implode(',', $data['allowed_countries'] ?? []);
    } else {
        echo "❌ Bestaat niet";
    }
    echo "</p>";
}
echo "</div>";

echo "<div class='info'>";
echo "<h2>⚙️ Geladen Configuratie</h2>";
echo "<div class='code'>";
echo "<pre>" . htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT)) . "</pre>";
echo "</div>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Test Functionaliteit</h2>";
echo "<p><strong>Should show alternative:</strong> " . ($cloaking->shouldShowAlternativePage() ? 'TRUE' : 'FALSE') . "</p>";

$ip = $cloaking->getVisitorIP();
$country = $cloaking->getCountryFromIP($ip);
echo "<p><strong>Visitor IP:</strong> " . htmlspecialchars($ip) . "</p>";
echo "<p><strong>Detected Country:</strong> " . htmlspecialchars($country) . "</p>";
echo "<p><strong>Is country allowed:</strong> " . (in_array($country, $config['allowed_countries']) ? 'TRUE' : 'FALSE') . "</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🔧 Test Met Parameters</h2>";
echo "<p>Test verschillende scenario's:</p>";
echo "<p>";
echo "<a href='?test_foreign=US' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test als US bezoeker</a>";
echo "<a href='?test_foreign=CN' style='background: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test als Chinese bezoeker</a>";
echo "<a href='?test_allowed=ES' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test als Spaanse bezoeker</a>";
echo "</p>";

if (isset($_GET['test_foreign']) || isset($_GET['test_allowed'])) {
    echo "<h3>Test Resultaat:</h3>";
    if (isset($_GET['test_foreign'])) {
        $testCountry = $_GET['test_foreign'];
        $wouldBlock = !in_array($testCountry, $config['allowed_countries']);
        echo "<p>Test voor land: <strong>" . htmlspecialchars($testCountry) . "</strong></p>";
        echo "<p>Zou geblokkeerd worden: " . ($wouldBlock ? '<span style="color: red;">JA</span>' : '<span style="color: green;">NEE</span>') . "</p>";
    }
    
    if (isset($_GET['test_allowed'])) {
        $testCountry = $_GET['test_allowed'];
        $isAllowed = in_array($testCountry, $config['allowed_countries']);
        echo "<p>Test voor land: <strong>" . htmlspecialchars($testCountry) . "</strong></p>";
        echo "<p>Zou toegestaan worden: " . ($isAllowed ? '<span style="color: green;">JA</span>' : '<span style="color: red;">NEE</span>') . "</p>";
    }
}
echo "</div>";

echo "<p><a href='admin/dashboard.php'>🔙 Terug naar Admin Dashboard</a> | <a href='test_cloaking.php'>🧪 Basis Test</a></p>";

?> 