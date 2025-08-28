<?php
// Force refresh van alle cloaking configuratie

echo "<h1>🔄 Config Force Refresh</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>";

// Controleer of het config bestand correct is
if (file_exists('cloaking_config.json')) {
    $content = file_get_contents('cloaking_config.json');
    echo "<h2>📂 Current Config File:</h2>";
    echo "<pre style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars($content);
    echo "</pre>";
    
    $config = json_decode($content, true);
    if ($config) {
        echo "<h2>✅ Parsed Successfully:</h2>";
        echo "<ul>";
        echo "<li><strong>Enabled:</strong> " . ($config['enabled'] ? 'TRUE' : 'FALSE') . "</li>";
        echo "<li><strong>Countries:</strong> " . implode(', ', $config['allowed_countries']) . "</li>";
        echo "<li><strong>Redirect URL:</strong> " . htmlspecialchars($config['cloaking_redirect_url']) . "</li>";
        echo "</ul>";
        
        echo "<h2>🧪 Test Results:</h2>";
        $testCountries = ['NL', 'ES', 'US', 'DE'];
        foreach ($testCountries as $country) {
            $allowed = in_array($country, $config['allowed_countries']);
            $status = $allowed ? '✅ ALLOWED' : '❌ BLOCKED';
            $color = $allowed ? 'green' : 'red';
            echo "<p><strong>$country:</strong> <span style='color: $color;'>$status</span></p>";
        }
    } else {
        echo "<h2>❌ JSON Parse Error!</h2>";
        echo "<p>Het config bestand bevat ongeldige JSON.</p>";
    }
} else {
    echo "<h2>❌ Config File Not Found!</h2>";
    echo "<p>Het bestand cloaking_config.json bestaat niet.</p>";
}

// Test met CloakingSystem class
echo "<h2>🔧 CloakingSystem Class Test:</h2>";
require_once 'cloaking.php';

$cloaking = new CloakingSystem();
$loadedConfig = $cloaking->getConfig();

echo "<pre style='background: #e8f4f8; padding: 15px; border-radius: 5px;'>";
echo "Loaded by CloakingSystem:\n";
echo json_encode($loadedConfig, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<p><a href='test_live_cloaking.php'>🧪 Go to Live Test</a> | <a href='admin/dashboard.php'>🔙 Admin Dashboard</a></p>";

?> 