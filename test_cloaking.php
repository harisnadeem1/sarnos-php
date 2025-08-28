<?php
require_once 'cloaking.php';

echo "<h1>🧪 Cloaking Test Pagina</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

// Force fresh load van configuratie
$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

// Double check: reload opnieuw voor zekerheid
$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='info'>";
echo "<h2>📊 Huidige Configuratie</h2>";
echo "<p><strong>Cloaking ingeschakeld:</strong> " . ($config['enabled'] ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>Toegestane landen:</strong> " . implode(', ', $config['allowed_countries']) . "</p>";
echo "<p><strong>Doorverwijzing URL:</strong> " . htmlspecialchars($config['cloaking_redirect_url'] ?? 'alternative_page.php') . "</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🌍 Jouw Bezoekersinformatie</h2>";
$visitorIP = $cloaking->getVisitorIP();
$visitorCountry = $cloaking->getCountryFromIP($visitorIP);
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Onbekend';

echo "<p><strong>IP Adres:</strong> " . htmlspecialchars($visitorIP) . "</p>";
echo "<p><strong>Land:</strong> " . htmlspecialchars($visitorCountry) . "</p>";
echo "<p><strong>Is lokaal IP:</strong> " . (in_array($visitorIP, ['127.0.0.1', '::1', 'localhost']) ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>Land in toegestane lijst:</strong> " . (in_array($visitorCountry, $config['allowed_countries']) ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>User Agent:</strong> " . htmlspecialchars(substr($userAgent, 0, 100)) . "...</p>";

// Debug informatie
echo "<h3>🔍 Debug Informatie</h3>";
echo "<p><strong>Cloaking enabled:</strong> " . ($config['enabled'] ? 'TRUE' : 'FALSE') . "</p>";
echo "<p><strong>Should show alternative:</strong> " . ($cloaking->shouldShowAlternativePage() ? 'TRUE' : 'FALSE') . "</p>";

// Force reload of config
echo "<h3>🔄 Config Verificatie</h3>";
$freshCloaking = new CloakingSystem();
$freshConfig = $freshCloaking->getConfig();

echo "<p><strong>FRESH Load - Enabled:</strong> " . ($freshConfig['enabled'] ? 'TRUE' : 'FALSE') . "</p>";
echo "<p><strong>FRESH Load - Countries:</strong> " . implode(', ', $freshConfig['allowed_countries']) . "</p>";
echo "<p><strong>Config bestand status:</strong> " . (file_exists('cloaking_config.json') ? 'EXISTS' : 'NOT FOUND') . "</p>";

if (file_exists('cloaking_config.json')) {
    $fileContent = file_get_contents('cloaking_config.json');
    echo "<p><strong>Raw file content:</strong></p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($fileContent) . "</pre>";
    
    // Parse and verify
    $parsedConfig = json_decode($fileContent, true);
    if ($parsedConfig) {
        echo "<p><strong>Parsed config verification:</strong></p>";
        echo "<ul>";
        echo "<li>Enabled: " . ($parsedConfig['enabled'] ? 'TRUE' : 'FALSE') . "</li>";
        echo "<li>Countries: " . implode(', ', $parsedConfig['allowed_countries']) . "</li>";
        echo "<li>Redirect URL: " . htmlspecialchars($parsedConfig['cloaking_redirect_url'] ?? 'NOT SET') . "</li>";
        echo "</ul>";
    }
}

echo "</div>";

echo "<div class='info'>";
echo "<h2>🔍 Cloaking Test Resultaat</h2>";
$shouldCloak = $cloaking->shouldShowAlternativePage();
if ($shouldCloak === false) {
    echo "<div class='success'>";
    echo "<p>✅ <strong>Geen cloaking</strong> - Je zou de normale website te zien krijgen</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<p>⚠️ <strong>Cloaking actief</strong> - Je zou doorgestuurd worden naar: " . htmlspecialchars($cloaking->getCloakingRedirectUrl()) . "</p>";
    echo "<p>Je zou een alternatieve pagina te zien krijgen in plaats van de echte webshop.</p>";
    echo "</div>";
}
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Test Functies</h2>";
echo "<p>Test de cloaking functionaliteit:</p>";
echo "<div style='margin: 15px 0;'>";
echo "<a href='?test_foreign=US' style='display: inline-block; background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🚫 Test als VS bezoeker</a>";
echo "<a href='?test_foreign=CN' style='display: inline-block; background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🚫 Test als Chinese bezoeker</a>";
echo "<a href='?test_allowed=NL' style='display: inline-block; background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>✅ Test als Nederlandse bezoeker</a>";
echo "<a href='test_cloaking.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔄 Reset test</a>";
echo "</div>";

if (isset($_GET['test_foreign']) || isset($_GET['test_allowed'])) {
    echo "<div class='warning'>";
    echo "<h3>🎭 Test Resultaat</h3>";
    
    if (isset($_GET['test_foreign'])) {
        $testCountry = $_GET['test_foreign'];
        echo "<p><strong>Test scenario:</strong> Bezoeker uit " . htmlspecialchars($testCountry) . "</p>";
        
        if (in_array($testCountry, $config['allowed_countries'])) {
            echo "<p class='success'>✅ Dit land is toegestaan - bezoeker zou normale site zien</p>";
        } else {
            echo "<p class='warning'>⚠️ Dit land is NIET toegestaan - bezoeker zou doorgestuurd worden naar: " . htmlspecialchars($config['cloaking_redirect_url'] ?? 'alternative_page.php') . "</p>";
            if ($config['enabled']) {
                echo "<p><strong>Waarschuwing:</strong> Omdat cloaking is ingeschakeld, zou deze bezoeker in het echte systeem worden geblokkeerd!</p>";
            } else {
                echo "<p><strong>Info:</strong> Cloaking is uitgeschakeld, dus deze bezoeker zou nog steeds toegang hebben.</p>";
            }
        }
    }
    
    if (isset($_GET['test_allowed'])) {
        $testCountry = $_GET['test_allowed'];
        echo "<p><strong>Test scenario:</strong> Bezoeker uit " . htmlspecialchars($testCountry) . "</p>";
        echo "<p class='success'>✅ Dit land is toegestaan - bezoeker zou normale site zien</p>";
    }
    
    echo "</div>";
}
echo "</div>";

echo "<div class='info'>";
echo "<h2>🔗 Snelle Test Links</h2>";
echo "<p>Snelle toegang tot test functies:</p>";
echo "<p>";
echo "<a href='debug_live_server.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🌐 Live Server Debug</a>";
echo "<a href='test_ip_whitelist.php' style='background: #e91e63; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🛡️ IP Whitelist</a>";
echo "<a href='test_localhost_cloaking.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🏠 Localhost Test</a>";
echo "<a href='force_config_refresh.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔄 Config Refresh</a>";
echo "<a href='test_live_cloaking.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔴 LIVE Test</a>";
echo "<a href='test_cloaking_force.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 Geforceerde Land Test</a>";
echo "<a href='debug_cloaking_config.php' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔍 Config Debug</a>";
echo "<a href='alternative_page.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👁️ Bekijk Cloaking Pagina</a>";
echo "</p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h2>⚠️ Belangrijke Waarschuwingen</h2>";
echo "<ul>";
echo "<li>Cloaking kan leiden tot problemen met advertentieplatformen</li>";
echo "<li>Zoekmachines kunnen je site bestraffen voor cloaking</li>";
echo "<li>Test altijd grondig voordat je het systeem activeert</li>";
echo "<li>Zorg ervoor dat je de juridische implicaties begrijpt</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='admin/dashboard.php'>🔙 Terug naar Admin Dashboard</a></p>";

?> 