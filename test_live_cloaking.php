<?php
require_once 'cloaking.php';

echo "<h1>🔴 LIVE Cloaking Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .danger { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

echo "<div class='danger'>";
echo "<h2>🔍 REAL-TIME Config Check</h2>";

// Force nieuwe instantie
$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<p><strong>Working Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Config File Path Check:</strong></p>";
echo "<ul>";
echo "<li>cloaking_config.json: " . (file_exists('cloaking_config.json') ? '✅ EXISTS' : '❌ NOT FOUND') . "</li>";
echo "<li>../cloaking_config.json: " . (file_exists('../cloaking_config.json') ? '✅ EXISTS' : '❌ NOT FOUND') . "</li>";
echo "<li>admin/cloaking_config.json: " . (file_exists('admin/cloaking_config.json') ? '⚠️ DUPLICATE!' : '✅ NOT FOUND (good)') . "</li>";
echo "</ul>";

if (file_exists('cloaking_config.json')) {
    echo "<p><strong>Raw Config File Content:</strong></p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto;'>" . htmlspecialchars(file_get_contents('cloaking_config.json')) . "</pre>";
}

echo "<p><strong>Loaded Config Object:</strong></p>";
echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto;'>" . htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT)) . "</pre>";

echo "<h3>🧪 Test Landen:</h3>";

$testCountries = ['NL', 'ES', 'US', 'DE', 'FR'];
foreach ($testCountries as $country) {
    $isAllowed = in_array($country, $config['allowed_countries']);
    $color = $isAllowed ? 'green' : 'red';
    $icon = $isAllowed ? '✅' : '❌';
    echo "<p><strong>$country:</strong> <span style='color: $color;'>$icon " . ($isAllowed ? 'TOEGESTAAN' : 'GEBLOKKEERD') . "</span></p>";
}

echo "</div>";

// Live test met parameters
if (isset($_GET['test_country'])) {
    $testCountry = $_GET['test_country'];
    echo "<div class='info'>";
    echo "<h2>🎯 Live Test voor: " . htmlspecialchars($testCountry) . "</h2>";
    
    $isAllowed = in_array($testCountry, $config['allowed_countries']);
    if ($isAllowed) {
        echo "<div class='success'>";
        echo "<h3>✅ TOEGESTAAN</h3>";
        echo "<p>Land <strong>" . htmlspecialchars($testCountry) . "</strong> is in de lijst van toegestane landen.</p>";
        echo "</div>";
    } else {
        echo "<div class='danger'>";
        echo "<h3>❌ GEBLOKKEERD</h3>";
        echo "<p>Land <strong>" . htmlspecialchars($testCountry) . "</strong> is NIET in de lijst van toegestane landen.</p>";
        echo "<p>Deze bezoeker zou doorgestuurd worden naar: <strong>" . htmlspecialchars($config['cloaking_redirect_url']) . "</strong></p>";
        echo "</div>";
    }
    echo "</div>";
}

echo "<div class='info'>";
echo "<h2>🧪 Test Links</h2>";
echo "<p>Test verschillende landen:</p>";
$testCountries = ['NL', 'ES', 'US', 'DE', 'FR', 'CN', 'RU'];
foreach ($testCountries as $country) {
    $bgColor = in_array($country, $config['allowed_countries']) ? '#28a745' : '#dc3545';
    echo "<a href='?test_country=$country' style='display: inline-block; background: $bgColor; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; margin: 3px;'>$country</a> ";
}
echo "</p>";
echo "</div>";

echo "<p><a href='admin/dashboard.php'>🔙 Admin Dashboard</a> | <a href='test_cloaking.php'>🧪 Basis Test</a> | <a href='test_cloaking_force.php'>🧪 Force Test</a></p>";

?> 