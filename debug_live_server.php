<?php
require_once 'cloaking.php';

echo "<h1>🌐 Live Server Debug</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 5px; } .danger { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #e8f5e8; padding: 15px; margin: 10px 0; border-radius: 5px; } .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-radius: 5px; } .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; overflow-x: auto; }</style>";

$cloaking = new CloakingSystem();
$config = $cloaking->getConfig();

echo "<div class='danger'>";
echo "<h2>🔍 Live Server Analysis</h2>";
echo "<p>Deze pagina debugt waarom cloaking niet werkt op de live server.</p>";
echo "</div>";

// Server informatie
echo "<div class='info'>";
echo "<h2>🖥️ Server Environment</h2>";
echo "<p><strong>Server:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</p>";
echo "<p><strong>Server IP:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'Unknown') . "</p>";
echo "<p><strong>Request Method:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "</p>";
echo "<p><strong>User Agent:</strong> " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</p>";
echo "</div>";

// IP detectie analyse
echo "<div class='warning'>";
echo "<h2>🌐 IP Detection Analysis</h2>";

echo "<h3>Available IP Headers:</h3>";
$ipHeaders = [
    'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'NOT SET',
    'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'NOT SET',
    'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? 'NOT SET', 
    'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'NOT SET',
    'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? 'NOT SET',
    'HTTP_X_CLUSTER_CLIENT_IP' => $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ?? 'NOT SET'
];

echo "<div class='code'>";
foreach ($ipHeaders as $header => $value) {
    $status = ($value !== 'NOT SET') ? '✅' : '❌';
    echo "<p>$status <strong>$header:</strong> $value</p>";
}
echo "</div>";

$detectedIP = $cloaking->getVisitorIP();
echo "<p><strong>Gedetecteerd IP door systeem:</strong> " . htmlspecialchars($detectedIP) . "</p>";
echo "</div>";

// Geolocation test
echo "<div class='info'>";
echo "<h2>🌍 Geolocation API Test</h2>";

$testIP = $detectedIP;
if ($testIP === '127.0.0.1' || strpos($testIP, '192.168.') === 0) {
    $testIP = '8.8.8.8'; // Use Google DNS for testing
    echo "<p><strong>Note:</strong> Using $testIP for testing (local IP detected)</p>";
}

echo "<p><strong>Testing IP:</strong> $testIP</p>";

// Test geolocation API direct
$url = "http://ip-api.com/json/" . $testIP . "?fields=status,country,countryCode,region,city,query";
echo "<p><strong>API URL:</strong> <code>$url</code></p>";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'Mozilla/5.0 (compatible; CloakingSystem/1.0)',
        'method' => 'GET'
    ]
]);

echo "<h3>API Response Test:</h3>";
$startTime = microtime(true);
$response = @file_get_contents($url, false, $context);
$endTime = microtime(true);
$responseTime = round(($endTime - $startTime) * 1000, 2);

if ($response === false) {
    echo "<div class='danger'>";
    echo "<p>❌ <strong>API Call Failed!</strong></p>";
    echo "<p>Response time: {$responseTime}ms</p>";
    
    $error = error_get_last();
    if ($error) {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($error['message']) . "</p>";
    }
    
    echo "<h4>Possible causes:</h4>";
    echo "<ul>";
    echo "<li>Server firewall blocking outbound HTTP requests</li>";
    echo "<li>No internet access from server</li>";
    echo "<li>PHP allow_url_fopen disabled</li>";
    echo "<li>Rate limiting by geolocation service</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<p>✅ <strong>API Call Successful!</strong></p>";
    echo "<p>Response time: {$responseTime}ms</p>";
    echo "<h4>Raw Response:</h4>";
    echo "<div class='code'>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    echo "</div>";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "<h4>Parsed Data:</h4>";
        echo "<ul>";
        echo "<li><strong>Status:</strong> " . ($data['status'] ?? 'unknown') . "</li>";
        echo "<li><strong>Country:</strong> " . ($data['country'] ?? 'unknown') . "</li>";
        echo "<li><strong>Country Code:</strong> " . ($data['countryCode'] ?? 'unknown') . "</li>";
        echo "<li><strong>Region:</strong> " . ($data['region'] ?? 'unknown') . "</li>";
        echo "<li><strong>City:</strong> " . ($data['city'] ?? 'unknown') . "</li>";
        echo "</ul>";
    }
    echo "</div>";
}
echo "</div>";

// Country detection voor echte bezoeker
echo "<div class='warning'>";
echo "<h2>🏠 Your Country Detection</h2>";
$yourCountry = $cloaking->getCountryFromIP($detectedIP);
echo "<p><strong>Your detected country:</strong> " . htmlspecialchars($yourCountry) . "</p>";
echo "<p><strong>Is country allowed:</strong> " . (in_array($yourCountry, $config['allowed_countries']) ? '✅ YES' : '❌ NO') . "</p>";
echo "</div>";

// Cloaking test
echo "<div class='info'>";
echo "<h2>🧪 Cloaking System Test</h2>";
echo "<p><strong>Config enabled:</strong> " . ($config['enabled'] ? '✅ YES' : '❌ NO') . "</p>";
echo "<p><strong>Allowed countries:</strong> " . implode(', ', $config['allowed_countries']) . "</p>";

if (isset($config['ip_whitelist']) && is_array($config['ip_whitelist'])) {
    echo "<p><strong>IP whitelist:</strong> " . (count($config['ip_whitelist']) > 0 ? implode(', ', $config['ip_whitelist']) : 'Empty') . "</p>";
    $isWhitelisted = $cloaking->isIPWhitelisted($detectedIP);
    echo "<p><strong>Your IP whitelisted:</strong> " . ($isWhitelisted ? '✅ YES' : '❌ NO') . "</p>";
}

$shouldCloak = $cloaking->shouldShowAlternativePage();
echo "<p><strong>Should show cloaking:</strong> " . ($shouldCloak ? '✅ YES' : '❌ NO') . "</p>";
echo "</div>";

// PHP configuration check
echo "<div class='warning'>";
echo "<h2>⚙️ PHP Configuration</h2>";
echo "<p><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? '✅ Enabled' : '❌ Disabled') . "</p>";
echo "<p><strong>openssl:</strong> " . (extension_loaded('openssl') ? '✅ Loaded' : '❌ Not loaded') . "</p>";
echo "<p><strong>curl:</strong> " . (extension_loaded('curl') ? '✅ Loaded' : '❌ Not loaded') . "</p>";
echo "<p><strong>user_agent:</strong> " . htmlspecialchars(ini_get('user_agent')) . "</p>";
echo "<p><strong>default_socket_timeout:</strong> " . ini_get('default_socket_timeout') . " seconds</p>";
echo "</div>";

// Test alternative geolocation methods
echo "<div class='info'>";
echo "<h2>🔄 Alternative Solutions</h2>";
echo "<p>If the main geolocation service fails, here are alternatives:</p>";

// Test met cURL als alternatief
if (extension_loaded('curl')) {
    echo "<h3>Testing with cURL:</h3>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CloakingSystem/1.0)');
    
    $curlStartTime = microtime(true);
    $curlResponse = curl_exec($ch);
    $curlEndTime = microtime(true);
    $curlResponseTime = round(($curlEndTime - $curlStartTime) * 1000, 2);
    
    if (curl_error($ch)) {
        echo "<p>❌ cURL Error: " . curl_error($ch) . "</p>";
    } else {
        echo "<p>✅ cURL Success (Response time: {$curlResponseTime}ms)</p>";
    }
    curl_close($ch);
} else {
    echo "<p>❌ cURL not available</p>";
}

echo "<h3>Manual Country Override:</h3>";
echo "<p>Add this to your config for testing:</p>";
echo "<div class='code'>";
echo '<pre>"manual_country_override": "NL"</pre>';
echo "</div>";
echo "</div>";

echo "<p><a href='admin/dashboard.php?tab=cloaking'>🔙 Admin Dashboard</a> | <a href='test_cloaking.php'>🧪 Basis Test</a></p>";

?> 