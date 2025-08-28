<?php
require_once 'cloaking.php';

echo "<h1>🏠 Localhost Cloaking Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .danger { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; } .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='info'>";
echo "<h2>🔍 Localhost Cloaking Status</h2>";
echo "<p>Deze pagina test of cloaking correct werkt op localhost.</p>";
echo "</div>";

echo "<div class='danger'>";
echo "<h2>⚙️ Huidige Configuratie</h2>";
echo "<p><strong>Cloaking ingeschakeld:</strong> " . ($config['enabled'] ? '✅ JA' : '❌ NEE') . "</p>";
echo "<p><strong>Toegestane landen:</strong> " . implode(', ', $config['allowed_countries']) . "</p>";
echo "<p><strong>Doorverwijzing URL:</strong> " . htmlspecialchars($config['cloaking_redirect_url']) . "</p>";
echo "</div>";

// Test localhost behandeling
$visitorIP = $cloaking->getVisitorIP();
$detectedCountry = $cloaking->getCountryFromIP($visitorIP);
$shouldCloak = $cloaking->shouldShowAlternativePage();

echo "<div class='warning'>";
echo "<h2>🏠 Localhost Detectie</h2>";
echo "<p><strong>Jouw IP:</strong> " . htmlspecialchars($visitorIP) . "</p>";
echo "<p><strong>Gedetecteerd als land:</strong> " . htmlspecialchars($detectedCountry) . "</p>";
echo "<p><strong>Is localhost:</strong> " . (in_array($visitorIP, ['127.0.0.1', '::1']) || strpos($visitorIP, '192.168.') === 0 ? '✅ JA' : '❌ NEE') . "</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🧪 Cloaking Test Resultaat</h2>";

if (!$config['enabled']) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Cloaking Uitgeschakeld</h3>";
    echo "<p>Cloaking is momenteel uitgeschakeld. Schakel het in via het admin dashboard om te testen.</p>";
    echo "</div>";
} else {
    $isCountryAllowed = in_array($detectedCountry, $config['allowed_countries']);
    
    if ($shouldCloak) {
        echo "<div class='danger'>";
        echo "<h3>🚫 CLOAKING ACTIEF</h3>";
        echo "<p><strong>Status:</strong> Je zou doorgestuurd worden naar de cloaking pagina!</p>";
        echo "<p><strong>Reden:</strong> Land '" . htmlspecialchars($detectedCountry) . "' is niet toegestaan.</p>";
        echo "<p><strong>Doorverwijzing naar:</strong> " . htmlspecialchars($config['cloaking_redirect_url']) . "</p>";
        echo "</div>";
        
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='" . htmlspecialchars($config['cloaking_redirect_url']) . "' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👁️ Bekijk Cloaking Pagina</a>";
        echo "</div>";
        
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ GEEN CLOAKING</h3>";
        echo "<p><strong>Status:</strong> Je krijgt toegang tot de normale website.</p>";
        echo "<p><strong>Reden:</strong> Land '" . htmlspecialchars($detectedCountry) . "' is toegestaan.</p>";
        echo "</div>";
    }
}
echo "</div>";

echo "<div class='info'>";
echo "<h2>🔧 Test Instructies</h2>";
echo "<ol>";
echo "<li><strong>Schakel cloaking in</strong> via het admin dashboard</li>";
echo "<li><strong>Verwijder NL</strong> uit de toegestane landen lijst</li>";
echo "<li><strong>Herlaad deze pagina</strong> - je zou nu cloaking moeten zien</li>";
echo "<li><strong>Test de hoofdpagina:</strong> ga naar <a href='index.php'>index.php</a> - je zou doorgestuurd moeten worden</li>";
echo "</ol>";
echo "</div>";

// Live test met forcing
echo "<div class='warning'>";
echo "<h2>⚡ Live Test Links</h2>";
echo "<p>Forceer verschillende scenario's:</p>";
echo "<p>";
echo "<a href='index.php?test_foreign=NL' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🚫 Test als Nederland (geblokkeerd)</a>";
echo "<a href='index.php?test_allowed=ES' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>✅ Test als Spanje (toegestaan)</a>";
echo "</p>";
echo "</div>";

echo "<p><a href='admin/dashboard.php'>🔙 Admin Dashboard</a> | <a href='test_cloaking.php'>🧪 Basis Test</a> | <a href='force_config_refresh.php'>🔄 Config Refresh</a></p>";

?> 