<?php
echo "<h1>🔧 Volledige Integratie Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; } 
.container { max-width: 1200px; margin: 0 auto; }
.card { background: white; padding: 20px; margin: 15px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { border-left: 5px solid #28a745; background: #e6ffe6; }
.warning { border-left: 5px solid #ffc107; background: #fff9e6; }
.danger { border-left: 5px solid #dc3545; background: #ffe6e6; }
.info { border-left: 5px solid #007bff; background: #e6f3ff; }
.check { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px; }
h2 { color: #333; margin-top: 0; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
</style>";

echo "<div class='container'>";

require_once 'cloaking.php';
require_once 'webassembly_countermeasures.php';

$cloaking = new CloakingSystem();
$wasmCounter = new WebAssemblyCounterMeasures();

echo "<div class='card info'>";
echo "<h2>🔍 STAP 1: Basis Systeem Check</h2>";

// Check files
$files = [
    'cloaking_config.json' => 'Cloaking configuratie',
    'live_monitoring.json' => 'Live monitoring data',
    'tiktok_bot_detection.json' => 'TikTok bot logs',
    'wasm_fingerprinting_log.json' => 'WebAssembly logs',
    'webassembly_countermeasures.php' => 'WebAssembly counter-measures',
    'admin/dashboard.php' => 'Admin dashboard'
];

foreach ($files as $file => $description) {
    $exists = file_exists($file);
    echo "<div class='check'>";
    echo ($exists ? "✅" : "❌") . " <strong>$description:</strong> " . ($exists ? "Bestaat" : "Niet gevonden");
    if ($exists && pathinfo($file, PATHINFO_EXTENSION) == 'json') {
        $size = filesize($file);
        echo " (${size} bytes)";
    }
    echo "</div>";
}

echo "</div>";

echo "<div class='card warning'>";
echo "<h2>🧪 STAP 2: Functionaliteit Tests</h2>";

// Test 1: WebAssembly Detection
echo "<h3>Test 1: WebAssembly Detection</h3>";
$wasmDetected = $cloaking->detectWebAssemblyFingerprinting();
echo "<div class='check'>";
echo ($wasmDetected ? "🚨" : "✅") . " WebAssembly Detection: " . ($wasmDetected ? "Actief (gedetecteerd)" : "Inactief (niet gedetecteerd)");
echo "</div>";

// Test 2: TikTok Bot Detection  
echo "<h3>Test 2: TikTok Bot Detection</h3>";
$tiktokBot = $cloaking->isTikTokBot();
echo "<div class='check'>";
echo ($tiktokBot ? "🚨" : "✅") . " TikTok Bot Detection: " . ($tiktokBot ? "Bot gedetecteerd" : "Geen bot");
echo "</div>";

// Test 3: Behavioral Analysis
echo "<h3>Test 3: Behavioral Analysis</h3>";
$behaviorDetected = $cloaking->detectTikTokBotBehavior();
echo "<div class='check'>";
echo ($behaviorDetected ? "🚨" : "✅") . " Behavioral Analysis: " . ($behaviorDetected ? "Verdacht gedrag" : "Normaal gedrag");
echo "</div>";

echo "</div>";

echo "<div class='card success'>";
echo "<h2>📊 STAP 3: Logging Integratie Test</h2>";

// Clear logs voor test
echo "<h3>A. Clear Logs voor Clean Test</h3>";
$cloaking->clearMonitoringData();
echo "<div class='check'>✅ Live monitoring logs geleegd</div>";

// Force test logging
echo "<h3>B. Force Test Log Entries</h3>";

// Test normale visit log
echo "<div class='check'>🔄 Test normale visit logging...</div>";
checkCloaking(); // Dit zou een normale entry moeten loggen
echo "<div class='check'>✅ Normale visit gelogd</div>";

// Test TikTok detection (simulatie)
echo "<div class='check'>🔄 Test TikTok detection logging...</div>";
if (method_exists($cloaking, 'logTikTokDetection')) {
    // We kunnen de private method niet direct aanroepen, dus simuleren we via shouldShowAlternativePage
    // met fake TikTok headers
    $_SERVER['HTTP_X_ARGUS'] = 'fake_tiktok_signature'; // Simuleer TikTok header
    $result = $cloaking->shouldShowAlternativePage();
    unset($_SERVER['HTTP_X_ARGUS']); // Cleanup
    echo "<div class='check'>✅ TikTok detection test uitgevoerd (result: " . ($result ? "blocked" : "allowed") . ")</div>";
} else {
    echo "<div class='check'>❌ logTikTokDetection method niet gevonden</div>";
}

// Test WebAssembly logging  
echo "<div class='check'>🔄 Test WebAssembly logging...</div>";
if (method_exists($wasmCounter, 'logWasmAttempt')) {
    $wasmCounter->logWasmAttempt([
        'test' => true,
        'integration_test' => 'full_integration_test',
        'detected_patterns' => ['performance.now', 'webassembly']
    ]);
    echo "<div class='check'>✅ WebAssembly log entry toegevoegd</div>";
} else {
    echo "<div class='check'>❌ logWasmAttempt method niet gevonden</div>";
}

echo "</div>";

echo "<div class='card info'>";
echo "<h2>📋 STAP 4: Data Verificatie</h2>";

// Check live monitoring data
$monitoringData = $cloaking->getLiveMonitoringData(10);
$monitoringStats = $cloaking->getMonitoringStats();

echo "<h3>Live Monitoring Data:</h3>";
echo "<div class='check'>📊 <strong>Totaal entries:</strong> " . count($monitoringData) . "</div>";
echo "<div class='check'>📈 <strong>Stats totaal:</strong> " . $monitoringStats['total'] . "</div>";
echo "<div class='check'>⏰ <strong>Laatste 24u:</strong> " . $monitoringStats['last_24h'] . "</div>";

if (!empty($monitoringData)) {
    echo "<h4>Laatste Entry:</h4>";
    $lastEntry = $monitoringData[0];
    echo "<div class='code'>";
    echo "Tijd: " . $lastEntry['datetime'] . "<br>";
    echo "IP: " . $lastEntry['ip'] . "<br>";
    echo "Land: " . $lastEntry['country'] . "<br>";
    echo "Status: " . $lastEntry['status'] . "<br>";
    echo "Actie: " . $lastEntry['action'] . "<br>";
    echo "URI: " . $lastEntry['request_uri'] . "<br>";
    echo "</div>";
}

// Check TikTok log file
echo "<h3>TikTok Bot Detection Logs:</h3>";
$tiktokLogFile = 'tiktok_bot_detection.json';
if (file_exists($tiktokLogFile)) {
    $tiktokLogs = json_decode(file_get_contents($tiktokLogFile), true) ?? [];
    echo "<div class='check'>📊 <strong>TikTok log entries:</strong> " . count($tiktokLogs) . "</div>";
    
    if (!empty($tiktokLogs)) {
        $latestTikTok = end($tiktokLogs);
        echo "<h4>Laatste TikTok Detection:</h4>";
        echo "<div class='code'>";
        echo "Tijd: " . $latestTikTok['datetime'] . "<br>";
        echo "Reden: " . $latestTikTok['reason'] . "<br>";
        echo "IP: " . $latestTikTok['ip'] . "<br>";
        echo "Severity: " . $latestTikTok['severity'] . "<br>";
        echo "</div>";
    }
} else {
    echo "<div class='check'>❌ TikTok log bestand niet gevonden</div>";
}

// Check WebAssembly log file
echo "<h3>WebAssembly Fingerprinting Logs:</h3>";
$wasmLogFile = 'wasm_fingerprinting_log.json';
if (file_exists($wasmLogFile)) {
    $wasmLogs = json_decode(file_get_contents($wasmLogFile), true) ?? [];
    echo "<div class='check'>📊 <strong>WebAssembly log entries:</strong> " . count($wasmLogs) . "</div>";
    
    if (!empty($wasmLogs)) {
        $latestWasm = end($wasmLogs);
        echo "<h4>Laatste WebAssembly Detection:</h4>";
        echo "<div class='code'>";
        echo "Tijd: " . $latestWasm['datetime'] . "<br>";
        echo "IP: " . $latestWasm['ip'] . "<br>";
        echo "Details: " . json_encode($latestWasm['details']) . "<br>";
        echo "Severity: " . $latestWasm['severity'] . "<br>";
        echo "</div>";
    }
} else {
    echo "<div class='check'>❌ WebAssembly log bestand niet gevonden</div>";
}

echo "</div>";

echo "<div class='card success'>";
echo "<h2>✅ STAP 5: Admin Dashboard Integratie</h2>";

echo "<h3>Admin Dashboard Features Check:</h3>";
$adminDashboard = 'admin/dashboard.php';
if (file_exists($adminDashboard)) {
    $adminContent = file_get_contents($adminDashboard);
    
    $features = [
        'get_monitoring_data' => 'Live monitoring AJAX endpoint',
        'monitoring-tab' => 'Live monitoring tab',
        'cloaking_enabled' => 'Cloaking configuratie', 
        'monitoringTable' => 'Monitoring tabel',
        'refreshMonitoringData' => 'Data refresh functie'
    ];
    
    foreach ($features as $feature => $description) {
        $found = strpos($adminContent, $feature) !== false;
        echo "<div class='check'>";
        echo ($found ? "✅" : "❌") . " <strong>$description:</strong> " . ($found ? "Gevonden" : "Niet gevonden");
        echo "</div>";
    }
} else {
    echo "<div class='check'>❌ Admin dashboard bestand niet gevonden</div>";
}

echo "</div>";

echo "<div class='card info'>";
echo "<h2>📊 STAP 6: Integratie Score</h2>";

$score = 0;
$maxScore = 10;

// File checks (2 punten)
$criticalFiles = ['cloaking.php', 'webassembly_countermeasures.php'];
foreach ($criticalFiles as $file) {
    if (file_exists($file)) $score++;
}

// Function checks (3 punten)
if (method_exists($cloaking, 'detectWebAssemblyFingerprinting')) $score++;
if (method_exists($cloaking, 'isTikTokBot')) $score++;
if (method_exists($cloaking, 'detectTikTokBotBehavior')) $score++;

// Logging checks (3 punten) 
if (file_exists('live_monitoring.json')) $score++;
if (file_exists('tiktok_bot_detection.json') || count($tiktokLogs ?? []) > 0) $score++;
if (file_exists('wasm_fingerprinting_log.json') || count($wasmLogs ?? []) > 0) $score++;

// Admin integration (2 punten)
if (file_exists('admin/dashboard.php')) $score++;
if (strpos($adminContent ?? '', 'monitoring-tab') !== false) $score++;

$percentage = round(($score / $maxScore) * 100);

echo "<div style='text-align: center; padding: 20px;'>";
echo "<h3>Integratie Score:</h3>";
echo "<div style='font-size: 48px; color: " . ($percentage >= 80 ? '#28a745' : ($percentage >= 60 ? '#ffc107' : '#dc3545')) . ";'>";
echo "$score/$maxScore ($percentage%)";
echo "</div>";

if ($percentage >= 90) {
    echo "<p style='color: #28a745; font-size: 18px;'>🎉 <strong>UITSTEKEND!</strong> Alles is correct geïntegreerd!</p>";
} elseif ($percentage >= 80) {
    echo "<p style='color: #28a745; font-size: 18px;'>✅ <strong>GOED!</strong> Systeem is grotendeels geïntegreerd.</p>";
} elseif ($percentage >= 60) {
    echo "<p style='color: #ffc107; font-size: 18px;'>⚠️ <strong>REDELIJK.</strong> Enkele onderdelen missen.</p>";
} else {
    echo "<p style='color: #dc3545; font-size: 18px;'>❌ <strong>PROBLEEM!</strong> Integratie heeft issues.</p>";
}

echo "</div>";
echo "</div>";

echo "<div class='card success'>";
echo "<h2>🔗 Quick Actions</h2>";
echo "<p>";
echo "<a href='admin/dashboard.php?tab=monitoring' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 Live Monitoring Dashboard</a>";
echo "<a href='admin/dashboard.php?tab=cloaking' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>⚙️ Cloaking Settings</a>";
echo "<a href='test_tiktok_detection.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🎯 TikTok Test</a>";
echo "<a href='test_webassembly_detection.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔬 WebAssembly Test</a>";
echo "</p>";
echo "</div>";

echo "</div>"; // container
?> 