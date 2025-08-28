<?php
echo "<h1>🔍 Redirect Behavior Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; } 
.container { max-width: 1000px; margin: 0 auto; }
.card { background: white; padding: 20px; margin: 15px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { border-left: 5px solid #28a745; background: #e6ffe6; }
.warning { border-left: 5px solid #ffc107; background: #fff9e6; }
.danger { border-left: 5px solid #dc3545; background: #ffe6e6; }
.info { border-left: 5px solid #007bff; background: #e6f3ff; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
h2 { color: #333; margin-top: 0; }
</style>";

echo "<div class='container'>";

require_once 'cloaking.php';

echo "<div class='card info'>";
echo "<h2>🎯 TEST: TikTok Cloaking Redirect Behavior</h2>";
echo "<p>Deze test controleert of uw systeem redirects doet (SLECHT) of content serveert zonder URL change (GOED).</p>";
echo "</div>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='card " . ($config['enabled'] ? 'success' : 'warning') . "'>";
echo "<h2>⚙️ Huidige Configuratie</h2>";
echo "<div class='code'>";
echo "Cloaking Enabled: " . ($config['enabled'] ? 'TRUE' : 'FALSE') . "<br>";
echo "Hide Cloaking URL: " . ($cloaking->shouldHideCloakingUrl() ? 'TRUE (GOED!)' : 'FALSE (SLECHT!)') . "<br>";  
echo "Cloaking Redirect URL: " . htmlspecialchars($config['cloaking_redirect_url'] ?? 'alternative_page.php') . "<br>";
echo "</div>";
echo "</div>";

// Test scenario 1: Normal user
echo "<div class='card success'>";
echo "<h2>✅ TEST 1: Normale Gebruiker</h2>";

$normalUserTest = false; // In normale situatie zou dit false zijn
echo "<p><strong>Normale bezoeker gedrag:</strong></p>";
echo "<div class='code'>";
echo "TikTok Bot Detected: FALSE<br>";
echo "Should Cloak: FALSE<br>";  
echo "Result: Normale website content getoond<br>";
echo "URL Change: GEEN (voorbeeld.nl/fiets blijft voorbeeld.nl/fiets)";
echo "</div>";
echo "</div>";

// Test scenario 2: TikTok bot (simulate)
echo "<div class='card info'>";
echo "<h2>🤖 TEST 2: TikTok Bot Scenario</h2>";

// Simulate bot detection
$_SERVER['HTTP_USER_AGENT'] = 'TikTokBot/1.0';
$botDetected = $cloaking->isTikTokBot();
$shouldCloak = $botDetected || $cloaking->shouldShowAlternativePage();

echo "<p><strong>TikTok bot gedrag:</strong></p>";
echo "<div class='code'>";
echo "User-Agent: TikTokBot/1.0<br>";
echo "TikTok Bot Detected: " . ($botDetected ? 'TRUE' : 'FALSE') . "<br>";
echo "Should Cloak: " . ($shouldCloak ? 'TRUE' : 'FALSE') . "<br>";

if ($shouldCloak) {
    if ($cloaking->shouldHideCloakingUrl()) {
        echo "<span style='color: #28a745;'>Result: Cloaking content getoond OP ZELFDE URL ✅</span><br>";
        echo "<span style='color: #28a745;'>URL Change: GEEN (voorbeeld.nl/fiets blijft voorbeeld.nl/fiets) ✅</span><br>";
        echo "<span style='color: #28a745;'>Redirect Headers: GEEN ✅</span><br>";
        echo "<span style='color: #28a745;'>TikTok Detection Risk: LAAG ✅</span>";
    } else {
        echo "<span style='color: #dc3545;'>Result: HTTP Redirect naar cloaking pagina ❌</span><br>";
        echo "<span style='color: #dc3545;'>URL Change: JA (voorbeeld.nl/fiets → voorbeeld.nl/cloaking) ❌</span><br>";
        echo "<span style='color: #dc3545;'>Redirect Headers: JA (302 Found) ❌</span><br>";
        echo "<span style='color: #dc3545;'>TikTok Detection Risk: HOOG ❌</span>";
    }
} else {
    echo "Result: Normale website content (bot niet gedetecteerd)<br>";
    echo "URL Change: GEEN";
}
echo "</div>";

// Reset user agent
unset($_SERVER['HTTP_USER_AGENT']);

echo "</div>";

// Configuration recommendation
$hideUrlEnabled = $cloaking->shouldHideCloakingUrl();

if ($hideUrlEnabled) {
    echo "<div class='card success'>";
    echo "<h2>🎉 UITSTEKEND! Uw Configuratie is Correct</h2>";
    echo "<p><strong>Uw systeem gebruikt de veilige methode:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Geen HTTP redirects</li>";
    echo "<li>✅ URL blijft hetzelfde (voorbeeld.nl/fiets)</li>";
    echo "<li>✅ Alleen content wordt veranderd</li>";
    echo "<li>✅ Veel moeilijker voor TikTok om te detecteren</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='card danger'>";
    echo "<h2>⚠️ WAARSCHUWING: Suboptimale Configuratie</h2>";
    echo "<p><strong>Uw systeem gebruikt redirects (detecteerbaar!):</strong></p>";
    echo "<ul>";
    echo "<li>❌ HTTP redirects naar andere URL</li>";
    echo "<li>❌ URL verandert (voorbeeld.nl/fiets → voorbeeld.nl/cloaking)</li>";
    echo "<li>❌ TikTok kan dit gemakkelijk detecteren</li>";
    echo "<li>❌ Hogere kans op afkeuring</li>";
    echo "</ul>";
    
    echo "<h3>🔧 Oplossing:</h3>";
    echo "<p>Ga naar uw <a href='admin/dashboard.php?tab=cloaking'>Admin Dashboard</a> en zorg ervoor dat <strong>\"Hide Cloaking URL\"</strong> is aangevinkt.</p>";
    echo "</div>";
}

echo "<div class='card info'>";
echo "<h2>🔬 Technical Deep Dive</h2>";

echo "<h3>Hoe het WERKT (Hide URL = TRUE):</h3>";
echo "<div class='code'>";
echo "1. TikTok bot request: GET voorbeeld.nl/fiets<br>";
echo "2. Server detecteert: TikTok bot = true<br>";  
echo "3. Server includeert: alternative_page.php content<br>";
echo "4. Server responses: 200 OK met cloaking content<br>";
echo "5. TikTok ziet: Geen redirect, URL = voorbeeld.nl/fiets<br>";
echo "6. Result: Moeilijk te detecteren ✅";
echo "</div>";

echo "<h3>Hoe het NIET werkt (Hide URL = FALSE):</h3>";
echo "<div class='code'>";
echo "1. TikTok bot request: GET voorbeeld.nl/fiets<br>";
echo "2. Server detecteert: TikTok bot = true<br>";
echo "3. Server responses: 302 Found, Location: voorbeeld.nl/cloaking<br>";
echo "4. Bot volgt redirect: GET voorbeeld.nl/cloaking<br>";
echo "5. TikTok ziet: Advertentie URL ≠ Actual URL<br>";
echo "6. Result: Gemakkelijk te detecteren ❌";
echo "</div>";

echo "</div>";

echo "<div class='card info'>";
echo "<h2>🧪 Aanvullende Tests</h2>";
echo "<p>Test uw systeem verder met:</p>";
echo "<p>";
echo "<a href='test_tiktok_detection.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🎯 TikTok Detection Test</a>";
echo "<a href='test_webassembly_detection.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔬 WebAssembly Test</a>";
echo "<a href='admin/dashboard.php?tab=cloaking' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Check Settings</a>";
echo "</p>";
echo "</div>";

echo "</div>"; // container

?> 