<?php
require_once 'cloaking.php';

echo "<h1>🎯 TikTok Bot Detection Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; } 
.info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } 
.danger { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; } 
.success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; } 
.warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px; }
.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; }
</style>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='danger'>";
echo "<h2>🚨 ENHANCED TikTok Bot Detection Status</h2>";
echo "<p>Deze pagina test de nieuwe TikTok-specifieke bot detectie mechanismen.</p>";
echo "</div>";

// Test Results
echo "<div class='info'>";
echo "<h2>🔍 Detection Results</h2>";

$isTikTokBot = $cloaking->isTikTokBot();
$hasBotBehavior = $cloaking->detectTikTokBotBehavior();
$shouldCloak = $cloaking->shouldShowAlternativePage();

echo "<p><strong>TikTok Bot Headers/UA:</strong> " . ($isTikTokBot ? '🚨 DETECTED' : '✅ Clean') . "</p>";
echo "<p><strong>TikTok Bot Behavior:</strong> " . ($hasBotBehavior ? '🚨 DETECTED' : '✅ Clean') . "</p>";
echo "<p><strong>Overall Cloaking Decision:</strong> " . ($shouldCloak ? '🚫 CLOAKED' : '✅ ALLOWED') . "</p>";
echo "</div>";

// Current Request Analysis
echo "<div class='warning'>";
echo "<h2>📊 Current Request Analysis</h2>";

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'None';
$requestUri = $_SERVER['REQUEST_URI'] ?? 'None';
$referer = $_SERVER['HTTP_REFERER'] ?? 'None';
$ip = $cloaking->getVisitorIP();

echo "<p><strong>Your IP:</strong> " . htmlspecialchars($ip) . "</p>";
echo "<p><strong>User Agent:</strong> " . htmlspecialchars(substr($userAgent, 0, 100)) . "...</p>";
echo "<p><strong>Request URI:</strong> " . htmlspecialchars($requestUri) . "</p>";
echo "<p><strong>Referer:</strong> " . htmlspecialchars($referer) . "</p>";
echo "</div>";

// Header Analysis
echo "<div class='info'>";
echo "<h2>📋 HTTP Headers Analysis</h2>";

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (empty($headers)) {
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$headerName] = $value;
        }
    }
}

$tiktokHeaders = ['X-Argus', 'X-Ladon', 'X-Gorgon', 'X-Khronos', 'X-Helios', 'X-Medusa', 'X-Tt-Logid', 'X-Ss-Stub'];
$foundTikTokHeaders = [];

foreach ($tiktokHeaders as $header) {
    if (isset($headers[$header])) {
        $foundTikTokHeaders[] = $header . ': ' . substr($headers[$header], 0, 50) . '...';
    }
}

if (!empty($foundTikTokHeaders)) {
    echo "<div class='danger'>";
    echo "<h3>🚨 TikTok Headers Detected!</h3>";
    foreach ($foundTikTokHeaders as $header) {
        echo "<code>" . htmlspecialchars($header) . "</code><br>";
    }
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<p>✅ No TikTok-specific headers detected</p>";
    echo "</div>";
}

echo "<h3>All Headers:</h3>";
echo "<div class='code'>";
foreach ($headers as $name => $value) {
    echo htmlspecialchars($name . ': ' . substr($value, 0, 100)) . "<br>";
}
echo "</div>";
echo "</div>";

// Simulation Tests
echo "<div class='warning'>";
echo "<h2>🧪 Simulation Tests</h2>";
echo "<p>Test verschillende TikTok bot scenarios:</p>";

// Simuleer TikTok User-Agent test
$originalUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
$testScenarios = [
    'TikTok Bot' => 'TikTokBot/1.0 (+https://www.tiktok.com/robots)',
    'ByteSpider' => 'Mozilla/5.0 (compatible; ByteSpider; https://www.bytedance.com/)',
    'Normal Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Headless Chrome' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/120.0.0.0 Safari/537.36'
];

foreach ($testScenarios as $name => $testUA) {
    $_SERVER['HTTP_USER_AGENT'] = $testUA;
    $testResult = $cloaking->isTikTokBot();
    $status = $testResult ? '🚨 BLOCKED' : '✅ ALLOWED';
    $color = $testResult ? 'red' : 'green';
    
    echo "<p><strong>$name:</strong> <span style='color: $color;'>$status</span></p>";
}

// Herstel originele User-Agent
$_SERVER['HTTP_USER_AGENT'] = $originalUA;

echo "</div>";

// Recent TikTok Detection Log
echo "<div class='info'>";
echo "<h2>📝 Recent TikTok Bot Detections</h2>";

$logFile = file_exists('tiktok_bot_detection.json') ? 'tiktok_bot_detection.json' : 'admin/../tiktok_bot_detection.json';

if (file_exists($logFile)) {
    $logs = json_decode(file_get_contents($logFile), true) ?: [];
    $recentLogs = array_slice($logs, -5); // Laatste 5 entries
    
    if (!empty($recentLogs)) {
        echo "<div class='code'>";
        foreach ($recentLogs as $log) {
            echo "<strong>" . ($log['datetime'] ?? 'Unknown time') . "</strong><br>";
            echo "IP: " . htmlspecialchars($log['ip'] ?? 'Unknown') . "<br>";
            echo "Reason: " . htmlspecialchars($log['reason'] ?? 'Unknown') . "<br>";
            echo "UA: " . htmlspecialchars(substr($log['user_agent'] ?? 'Unknown', 0, 80)) . "...<br>";
            echo "---<br>";
        }
        echo "</div>";
    } else {
        echo "<p>Nog geen TikTok bot detecties gelogd.</p>";
    }
} else {
    echo "<p>TikTok detection log bestand nog niet aangemaakt.</p>";
}

echo "</div>";

echo "<div class='success'>";
echo "<h2>✅ Test Instructies</h2>";
echo "<ol>";
echo "<li><strong>Normale Test:</strong> Herlaad deze pagina - zou 'ALLOWED' moeten zijn</li>";
echo "<li><strong>TikTok Crawler Test:</strong> Simuleer TikTok headers via browser dev tools</li>";
echo "<li><strong>Behavioral Test:</strong> Maak snelle opeenvolgende requests (< 1 seconde)</li>";
echo "<li><strong>DevTools Test:</strong> Probeer toegang tot <code>/.well-known/appspecific/</code> endpoints</li>";
echo "</ol>";

echo "<p><strong>Live Monitoring:</strong> Check <code>tiktok_bot_detection.json</code> voor real-time detecties</p>";
echo "</div>";

echo "<p><a href='admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔙 Terug naar Dashboard</a></p>";
echo "<p><a href='test_cloaking.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🧪 Algemene Cloaking Test</a></p>";

?> 