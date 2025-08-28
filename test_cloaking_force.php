<?php
require_once 'cloaking.php';

echo "<h1>🧪 Geforceerde Cloaking Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; } .danger { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

// Debug: Force reload configuration
$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

// Debug: Show exactly what's in the config
echo "<div class='danger'>";
echo "<h2>🔍 Live Config Debug</h2>";
echo "<p><strong>Current working directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Config file exists:</strong> " . (file_exists('cloaking_config.json') ? 'YES' : 'NO') . "</p>";
if (file_exists('cloaking_config.json')) {
    echo "<p><strong>Raw file content:</strong></p>";
    echo "<pre>" . htmlspecialchars(file_get_contents('cloaking_config.json')) . "</pre>";
}
echo "<p><strong>Loaded config:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT)) . "</pre>";
echo "</div>";

if (!$config['enabled']) {
    echo "<div class='warning'>";
    echo "<h2>⚠️ Cloaking is Uitgeschakeld</h2>";
    echo "<p>Schakel cloaking eerst in via het admin dashboard om deze test te kunnen uitvoeren.</p>";
    echo "<p><a href='admin/dashboard.php'>Ga naar Admin Dashboard</a></p>";
    
    // Debug info
    echo "<h3>🔍 Debug Info:</h3>";
    echo "<p>Config enabled: " . ($config['enabled'] ? 'TRUE' : 'FALSE') . "</p>";
    echo "<p>Config countries: " . implode(', ', $config['allowed_countries']) . "</p>";
    echo "</div>";
    // Don't exit, continue to show debug info
}

echo "<div class='info'>";
echo "<h2>🔧 Geforceerde Land Test</h2>";
echo "<p>Deze pagina simuleert een bezoeker uit een specifiek land door de cloaking functie te forceren.</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>📊 Huidige Configuratie</h2>";
echo "<p><strong>Cloaking ingeschakeld:</strong> " . ($config['enabled'] ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>Toegestane landen:</strong> " . implode(', ', $config['allowed_countries']) . "</p>";
echo "<p><strong>Doorverwijzing URL:</strong> " . htmlspecialchars($config['cloaking_redirect_url'] ?? 'alternative_page.php') . "</p>";
echo "</div>";

// Test knoppen
echo "<div class='info'>";
echo "<h2>🌍 Test Verschillende Landen</h2>";
echo "<p>Klik op een knop om te simuleren hoe een bezoeker uit dat land de site zou ervaren:</p>";
echo "<div style='margin: 15px 0;'>";

// Toegestane landen (groen)
foreach ($config['allowed_countries'] as $country) {
    echo "<a href='?force_country=" . urlencode($country) . "' style='display: inline-block; background: #28a745; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; margin: 5px;'>✅ " . htmlspecialchars($country) . "</a>";
}

echo "<br><br>";

// Niet-toegestane landen (rood)
$blockedCountries = ['US', 'CN', 'RU', 'IN', 'BR', 'FR', 'ES', 'IT'];
foreach ($blockedCountries as $country) {
    if (!in_array($country, $config['allowed_countries'])) {
        echo "<a href='?force_country=" . urlencode($country) . "' style='display: inline-block; background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; margin: 5px;'>🚫 " . htmlspecialchars($country) . "</a>";
    }
}

echo "</div>";
echo "</div>";

// Verwerk geforceerd land
if (isset($_GET['force_country'])) {
    $forcedCountry = $_GET['force_country'];
    
    // Force fresh config reload voor accurate test
    $freshCloaking = new CloakingSystem();
    $freshConfig = $freshCloaking->getConfig();
    
    echo "<div class='warning'>";
    echo "<h2>🎭 Simulatie voor: " . htmlspecialchars($forcedCountry) . "</h2>";
    
    // Debug informatie
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>🔍 Config Check:</strong></p>";
    echo "<p>Enabled: " . ($freshConfig['enabled'] ? 'TRUE' : 'FALSE') . "</p>";
    echo "<p>Allowed Countries: " . implode(', ', $freshConfig['allowed_countries']) . "</p>";
    echo "<p>Testing Country: " . htmlspecialchars($forcedCountry) . "</p>";
    echo "<p>Is Allowed: " . (in_array($forcedCountry, $freshConfig['allowed_countries']) ? 'TRUE' : 'FALSE') . "</p>";
    echo "</div>";
    
    if (in_array($forcedCountry, $freshConfig['allowed_countries'])) {
        echo "<div class='success'>";
        echo "<h3>✅ Toegang Toegestaan</h3>";
        echo "<p>Een bezoeker uit <strong>" . htmlspecialchars($forcedCountry) . "</strong> zou toegang krijgen tot de normale webshop.</p>";
        echo "<p><strong>Reden:</strong> " . htmlspecialchars($forcedCountry) . " staat in de lijst van toegestane landen: " . implode(', ', $freshConfig['allowed_countries']) . "</p>";
        echo "</div>";
        
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='index.php?test_allowed=" . urlencode($forcedCountry) . "' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ga naar Normale Webshop (als " . htmlspecialchars($forcedCountry) . ")</a>";
        echo "</div>";
        
    } else {
        echo "<div class='danger'>";
        echo "<h3>🚫 Toegang Geblokkeerd</h3>";
        echo "<p>Een bezoeker uit <strong>" . htmlspecialchars($forcedCountry) . "</strong> zou automatisch doorgestuurd worden naar de cloaking pagina.</p>";
        echo "<p><strong>Reden:</strong> " . htmlspecialchars($forcedCountry) . " staat NIET in de lijst van toegestane landen: " . implode(', ', $freshConfig['allowed_countries']) . "</p>";
        echo "<p><strong>Doorverwijzing naar:</strong> " . htmlspecialchars($freshConfig['cloaking_redirect_url']) . "</p>";
        echo "</div>";
        
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='index.php?test_foreign=" . urlencode($forcedCountry) . "' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚫 Test Blokkering (als " . htmlspecialchars($forcedCountry) . ")</a>";
        echo " ";
        echo "<a href='" . htmlspecialchars($freshConfig['cloaking_redirect_url']) . "' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👁️ Bekijk Cloaking Pagina</a>";
        echo "</div>";
    }
    echo "</div>";
}

echo "<div class='info'>";
echo "<h2>🔧 Test Instructies</h2>";
echo "<ol>";
echo "<li><strong>Groene knoppen (✅):</strong> Landen die toegang hebben - deze zouden de normale webshop moeten zien</li>";
echo "<li><strong>Rode knoppen (🚫):</strong> Landen die geblokkeerd zijn - deze zouden doorgestuurd moeten worden</li>";
echo "<li><strong>Test de blokkering:</strong> Klik op een rode knop en dan op 'Test Blokkering' om te zien of het systeem correct doorverwijst</li>";
echo "<li><strong>Bekijk cloaking pagina:</strong> Klik op 'Bekijk Cloaking Pagina' om te zien welke pagina geblokkeerde bezoekers te zien krijgen</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h2>⚠️ Belangrijke Opmerkingen</h2>";
echo "<ul>";
echo "<li>Deze test werkt alleen als cloaking is ingeschakeld in het admin dashboard</li>";
echo "<li>In de echte situatie wordt het land automatisch gedetecteerd via IP-adres</li>";
echo "<li>Localhost wordt altijd toegestaan voor development doeleinden</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='admin/dashboard.php'>🔙 Terug naar Admin Dashboard</a> | <a href='test_cloaking.php'>🧪 Basis Cloaking Test</a></p>";

?> 