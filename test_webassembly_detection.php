<?php
require_once 'cloaking.php';
require_once 'webassembly_countermeasures.php';

echo "<h1>🔬 WebAssembly Fingerprinting Detection Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; } 
.container { max-width: 1200px; margin: 0 auto; }
.card { background: white; padding: 20px; margin: 15px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.danger { border-left: 5px solid #dc3545; background: #ffe6e6; } 
.success { border-left: 5px solid #28a745; background: #e6ffe6; }
.warning { border-left: 5px solid #ffc107; background: #fff9e6; }
.info { border-left: 5px solid #007bff; background: #e6f3ff; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
h2 { color: #333; margin-top: 0; }
.status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
.test-button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
.test-button:hover { background: #0056b3; }
</style>";

$cloaking = new CloakingSystem();
$wasmCounter = new WebAssemblyCounterMeasures();

echo "<div class='container'>";

echo "<div class='card danger'>";
echo "<h2>🚨 ADVANCED TikTok 2025 WebAssembly Detection</h2>";
echo "<p>Deze pagina test de geavanceerde WebAssembly fingerprinting detectie die TikTok gebruikt in 2025 om cloaking te detecteren.</p>";
echo "</div>";

// Test de WebAssembly detectie
$wasmDetected = $cloaking->detectWebAssemblyFingerprinting();

echo "<div class='card " . ($wasmDetected ? 'danger' : 'success') . "'>";
echo "<h2>🔍 WebAssembly Fingerprinting Status</h2>";
echo "<div class='status-grid'>";

echo "<div>";
echo "<h3>Detectie Resultaat:</h3>";
echo "<p><strong>" . ($wasmDetected ? "🚨 WEBASSEMBLY FINGERPRINTING GEDETECTEERD" : "✅ GEEN WEBASSEMBLY FINGERPRINTING") . "</strong></p>";
echo "</div>";

echo "<div>";
echo "<h3>Wat betekent dit?</h3>";
if ($wasmDetected) {
    echo "<p>⚠️ U wordt herkend als potentiële bot die WebAssembly gebruikt voor fingerprinting. TikTok zou uw advertenties afkeuren.</p>";
} else {
    echo "<p>✅ Geen WebAssembly fingerprinting patronen gedetecteerd. Dit is goed voor TikTok advertenties.</p>";
}
echo "</div>";

echo "</div>";
echo "</div>";

// Browser informatie
echo "<div class='card info'>";
echo "<h2>📊 Browser & Request Informatie</h2>";
echo "<div class='code'>";
echo "<strong>User Agent:</strong> " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Onbekend') . "<br>";
echo "<strong>IP Adres:</strong> " . htmlspecialchars($cloaking->getVisitorIP()) . "<br>";
echo "<strong>Request Time:</strong> " . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time()) . "<br>";
echo "<strong>Request URI:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . "<br>";
echo "<strong>HTTP Referer:</strong> " . htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'Geen') . "<br>";
echo "</div>";
echo "</div>";

// Headers analyse
$headers = getallheaders() ?: [];
echo "<div class='card warning'>";
echo "<h2>🔍 HTTP Headers Analyse</h2>";
echo "<div class='code'>";

$suspiciousHeaders = [];
$wasmRelatedHeaders = ['Sec-Fetch-Dest', 'Content-Type', 'Accept'];

foreach ($headers as $name => $value) {
    $isSuspicious = false;
    foreach ($wasmRelatedHeaders as $wasmHeader) {
        if (stripos($name, $wasmHeader) !== false) {
            if (stripos($value, 'wasm') !== false || stripos($value, 'webassembly') !== false) {
                $isSuspicious = true;
                $suspiciousHeaders[] = "$name: $value";
            }
        }
    }
    
    echo "<strong>" . htmlspecialchars($name) . ":</strong> " . htmlspecialchars($value);
    if ($isSuspicious) {
        echo " <span style='color: red;'>⚠️ SUSPICIOUS</span>";
    }
    echo "<br>";
}

if (empty($suspiciousHeaders)) {
    echo "<br><span style='color: green;'>✅ Geen verdachte WebAssembly-gerelateerde headers gevonden</span>";
} else {
    echo "<br><span style='color: red;'>🚨 Verdachte headers gevonden:</span><br>";
    foreach ($suspiciousHeaders as $header) {
        echo "<span style='color: red;'>" . htmlspecialchars($header) . "</span><br>";
    }
}

echo "</div>";
echo "</div>";

// WebAssembly Counter-Measures Demo
echo "<div class='card info'>";
echo "<h2>🛡️ WebAssembly Counter-Measures Demo</h2>";
echo "<p>Klik op de knop hieronder om te zien hoe de WebAssembly counter-measures werken:</p>";

if (isset($_GET['demo_wasm_counter'])) {
    echo "<div class='success'>";
    echo "<h3>📝 WebAssembly Counter-Measures Script:</h3>";
    echo "<p>Het volgende script zou geïnjecteerd worden om WebAssembly fingerprinting te misleiden:</p>";
    
    $wasmScript = $wasmCounter->injectWasmCounterMeasures();
    echo "<div class='code'>";
    echo htmlspecialchars(substr($wasmScript, 0, 500)) . "...<br><br>";
    echo "<strong>Deze script:</strong><br>";
    echo "• Spooft WebAssembly API calls<br>";
    echo "• Voegt noise toe aan performance timing<br>";
    echo "• Verstoort memory measurements<br>";
    echo "• Misleidt hardware concurrency detection<br>";
    echo "</div>";
    echo "</div>";
    
    // Ook de browser spoofing demo
    echo "<div class='success'>";
    echo "<h3>🎭 Browser Spoofing Demo:</h3>";
    $browserScript = $wasmCounter->injectBrowserSpoofing();
    echo "<div class='code'>";
    echo htmlspecialchars(substr($browserScript, 0, 300)) . "...<br><br>";
    echo "<strong>Deze script verstoort:</strong><br>";
    echo "• Screen resolution fingerprinting<br>";
    echo "• Timezone detection<br>";
    echo "• Canvas fingerprinting<br>";
    echo "• Language/platform detection<br>";
    echo "</div>";
    echo "</div>";
}

echo "<a href='?demo_wasm_counter=1' class='test-button'>🛡️ Toon WebAssembly Counter-Measures</a>";
echo "</div>";

// Simulatie Tests
echo "<div class='card warning'>";
echo "<h2>🧪 WebAssembly Fingerprinting Simulatie</h2>";
echo "<p>Test verschillende WebAssembly fingerprinting scenario's:</p>";

// Simuleer WebAssembly request
if (isset($_GET['simulate_wasm'])) {
    echo "<div class='danger'>";
    echo "<h3>🚨 WebAssembly Simulatie Actief!</h3>";
    echo "<p>Er wordt nu een WebAssembly fingerprinting request gesimuleerd...</p>";
    
    // Log de poging
    $wasmCounter->logWasmAttempt([
        'simulation' => true,
        'type' => 'manual_test',
        'detected_patterns' => ['webassembly_fingerprint', 'performance.now'],
        'risk_level' => 'HIGH'
    ]);
    
    echo "<p>✅ WebAssembly fingerprinting poging gelogd in: <code>wasm_fingerprinting_log.json</code></p>";
    echo "</div>";
}

echo "<a href='?simulate_wasm=1' class='test-button' style='background: #dc3545;'>🧪 Simuleer WebAssembly Fingerprinting</a>";
echo "<a href='test_tiktok_detection.php' class='test-button'>🔙 Terug naar TikTok Detection</a>";
echo "<a href='advanced_cloaking_demo.php' class='test-button' style='background: #28a745;'>📈 Advanced Cloaking Demo</a>";
echo "</div>";

// Performance info
echo "<div class='card info'>";
echo "<h2>⚡ Performance Impact</h2>";
echo "<p>WebAssembly counter-measures hebben minimale performance impact:</p>";
echo "<div class='code'>";
echo "• JavaScript injection: ~2KB extra<br>";
echo "• Server-side detectie: ~0.1ms overhead<br>";
echo "• Memory usage: Verwaarloosbaar<br>";
echo "• User experience: Onzichtbaar<br>";
echo "</div>";
echo "</div>";

// Next Steps
echo "<div class='card success'>";
echo "<h2>🚀 Volgende Stappen</h2>";
echo "<p>Nu uw WebAssembly detectie werkt, zijn de volgende verbeteringen aanbevolen:</p>";
echo "<ul>";
echo "<li><strong>✅ FASE 1 COMPLEET:</strong> Basis TikTok bot detectie</li>";
echo "<li><strong>✅ FASE 2 COMPLEET:</strong> WebAssembly fingerprinting counter-measures</li>";
echo "<li><strong>🔄 FASE 3:</strong> X-Headers implementation (X-Argus, X-Ladon, etc.)</li>";
echo "<li><strong>⏳ FASE 4:</strong> Behavioral pattern analysis</li>";
echo "<li><strong>⏳ FASE 5:</strong> Multi-dimensional fingerprinting resistance</li>";
echo "</ul>";
echo "</div>";

echo "</div>"; // container

// Inject de WebAssembly counter-measures als ze gedetecteerd worden
if ($wasmDetected) {
    echo $wasmCounter->injectWasmCounterMeasures();
    echo $wasmCounter->injectBrowserSpoofing();
}
?> 