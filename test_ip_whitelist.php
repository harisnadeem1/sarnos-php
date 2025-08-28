<?php
require_once 'cloaking.php';

echo "<h1>🛡️ IP Whitelist Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .danger { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; } .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px; }</style>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='info'>";
echo "<h2>🔍 IP Whitelist Status</h2>";
echo "<p>Deze pagina test de IP whitelist functionaliteit.</p>";
echo "</div>";

// Huidige configuratie
echo "<div class='warning'>";
echo "<h2>⚙️ Huidige Configuratie</h2>";
echo "<p><strong>Cloaking ingeschakeld:</strong> " . ($config['enabled'] ? '✅ JA' : '❌ NEE') . "</p>";
echo "<p><strong>Toegestane landen:</strong> " . implode(', ', $config['allowed_countries']) . "</p>";
echo "<p><strong>IP Whitelist:</strong></p>";

if (isset($config['ip_whitelist']) && is_array($config['ip_whitelist']) && count($config['ip_whitelist']) > 0) {
    echo "<ul>";
    foreach ($config['ip_whitelist'] as $ip) {
        echo "<li><code>" . htmlspecialchars($ip) . "</code></li>";
    }
    echo "</ul>";
} else {
    echo "<p><em>Geen IP-adressen in whitelist</em></p>";
}
echo "</div>";

// Test huidige IP
$visitorIP = $cloaking->getVisitorIP();
$detectedCountry = $cloaking->getCountryFromIP($visitorIP);
$isWhitelisted = $cloaking->isIPWhitelisted($visitorIP);
$shouldCloak = $cloaking->shouldShowAlternativePage();

echo "<div class='info'>";
echo "<h2>🏠 Jouw IP Status</h2>";
echo "<p><strong>Jouw IP:</strong> " . htmlspecialchars($visitorIP) . "</p>";
echo "<p><strong>Gedetecteerd land:</strong> " . htmlspecialchars($detectedCountry) . "</p>";
echo "<p><strong>Is whitelisted:</strong> " . ($isWhitelisted ? '✅ JA' : '❌ NEE') . "</p>";
echo "<p><strong>Land toegestaan:</strong> " . (in_array($detectedCountry, $config['allowed_countries']) ? '✅ JA' : '❌ NEE') . "</p>";
echo "</div>";

// Test resultaat
echo "<div class='info'>";
echo "<h2>🧪 Cloaking Test Resultaat</h2>";

if (!$config['enabled']) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Cloaking Uitgeschakeld</h3>";
    echo "<p>Cloaking is momenteel uitgeschakeld. Het resultaat zou zijn:</p>";
    echo "</div>";
}

if ($isWhitelisted) {
    echo "<div class='success'>";
    echo "<h3>🛡️ IP WHITELISTED</h3>";
    echo "<p><strong>Status:</strong> Je IP staat op de whitelist - je krijgt altijd toegang!</p>";
    echo "<p><strong>Reden:</strong> IP whitelist heeft prioriteit boven alle andere regels.</p>";
    echo "</div>";
} else {
    if ($shouldCloak) {
        echo "<div class='danger'>";
        echo "<h3>🚫 CLOAKING ACTIEF</h3>";
        echo "<p><strong>Status:</strong> Je zou doorgestuurd worden naar de cloaking pagina.</p>";
        echo "<p><strong>Reden:</strong> IP niet whitelisted en land '" . htmlspecialchars($detectedCountry) . "' is niet toegestaan.</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ TOEGANG TOEGESTAAN</h3>";
        echo "<p><strong>Status:</strong> Je krijgt toegang tot de normale website.</p>";
        echo "<p><strong>Reden:</strong> Land '" . htmlspecialchars($detectedCountry) . "' is toegestaan.</p>";
        echo "</div>";
    }
}
echo "</div>";

// Test verschillende IP formaten
echo "<div class='warning'>";
echo "<h2>🧪 Test IP Patronen</h2>";
echo "<p>Test hoe verschillende IP formaten werken:</p>";

if (isset($_GET['test_ip'])) {
    $testIP = $_GET['test_ip'];
    $testResult = $cloaking->isIPWhitelisted($testIP);
    
    echo "<div class='info'>";
    echo "<h3>Test Resultaat voor: " . htmlspecialchars($testIP) . "</h3>";
    echo "<p><strong>Is whitelisted:</strong> " . ($testResult ? '✅ JA' : '❌ NEE') . "</p>";
    echo "</div>";
}

echo "<form method='GET' style='margin: 15px 0;'>";
echo "<input type='text' name='test_ip' placeholder='Voer IP in om te testen (bijv. 192.168.1.100)' style='padding: 8px; width: 300px;'>";
echo "<button type='submit' style='padding: 8px 15px; margin-left: 10px;'>Test IP</button>";
echo "</form>";

echo "<p><strong>Voorbeelden om te testen:</strong></p>";
echo "<ul>";
echo "<li><a href='?test_ip=127.0.0.1'>127.0.0.1</a> (localhost)</li>";
echo "<li><a href='?test_ip=192.168.1.100'>192.168.1.100</a> (privé netwerk)</li>";
echo "<li><a href='?test_ip=10.0.0.50'>10.0.0.50</a> (privé netwerk)</li>";
echo "<li><a href='?test_ip=8.8.8.8'>8.8.8.8</a> (Google DNS)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🔧 IP Whitelist Beheer</h2>";
echo "<p>Voeg IP-adressen toe aan de whitelist via het admin dashboard:</p>";
echo "<p><a href='admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Ga naar Admin Dashboard</a></p>";

echo "<h3>Ondersteunde Formaten:</h3>";
echo "<ul>";
echo "<li><strong>Exact IP:</strong> <code>192.168.1.100</code></li>";
echo "<li><strong>CIDR range:</strong> <code>192.168.1.0/24</code> (hele subnet)</li>";
echo "<li><strong>Wildcard:</strong> <code>192.168.1.*</code> (alle IPs in range)</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='admin/dashboard.php'>🔙 Admin Dashboard</a> | <a href='test_cloaking.php'>🧪 Basis Test</a> | <a href='test_localhost_cloaking.php'>🏠 Localhost Test</a></p>";

?> 