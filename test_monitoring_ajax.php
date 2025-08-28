<?php
// Direct test van de monitoring AJAX functionaliteit

require_once 'cloaking.php';

echo "<h1>🧪 Live Monitoring AJAX Test</h1>";

// Simuleer de AJAX request
$_POST['action'] = 'get_monitoring_data';
$_POST['limit'] = '10';
$_POST['page'] = '1';

$cloaking = new CloakingSystem();

$filters = [];

// Apply filters from request
if (!empty($_POST['hours'])) {
    $filters['hours'] = intval($_POST['hours']);
}
if (!empty($_POST['search'])) {
    $search = $_POST['search'];
    if (filter_var($search, FILTER_VALIDATE_IP)) {
        $filters['ip'] = $search;
    } else {
        $filters['country'] = $search;
    }
}
if (!empty($_POST['status'])) {
    $filters['status'] = $_POST['status'];
}

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$offset = ($page - 1) * $limit;

echo "<h2>🔍 Request Parameters:</h2>";
echo "<pre>";
echo "Limit: $limit\n";
echo "Page: $page\n";
echo "Offset: $offset\n";
echo "Filters: " . json_encode($filters, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Get monitoring data
$data = $cloaking->getLiveMonitoringData($limit + $offset, $filters);
$stats = $cloaking->getMonitoringStats();

echo "<h2>📊 Raw Data Results:</h2>";
echo "<pre>";
echo "Data count: " . count($data) . "\n";
echo "Stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Apply pagination
$paginatedData = array_slice($data, $offset, $limit);

echo "<h2>📄 Paginated Data (first 3):</h2>";
echo "<pre>";
echo json_encode(array_slice($paginatedData, 0, 3), JSON_PRETTY_PRINT);
echo "</pre>";

$response = [
    'success' => true,
    'data' => $paginatedData,
    'stats' => $stats,
    'total' => count($data),
    'page' => $page,
    'pages' => ceil(count($data) / $limit),
    'timestamp' => time(),
    'debug' => [
        'filters' => $filters,
        'limit' => $limit,
        'offset' => $offset,
        'raw_data_count' => count($data)
    ]
];

echo "<h2>🚀 Final JSON Response:</h2>";
echo "<pre>";
echo json_encode($response, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h2>📁 File Check:</h2>";
$logFile = 'live_monitoring.json';
echo "<p><strong>Monitoring file exists:</strong> " . (file_exists($logFile) ? '✅ YES' : '❌ NO') . "</p>";
if (file_exists($logFile)) {
    echo "<p><strong>File size:</strong> " . filesize($logFile) . " bytes</p>";
    echo "<p><strong>File readable:</strong> " . (is_readable($logFile) ? '✅ YES' : '❌ NO') . "</p>";
}

echo "<p style='margin-top: 30px;'>";
echo "<a href='admin/dashboard.php?tab=monitoring' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>↩️ Terug naar Dashboard</a>";
echo "</p>";
?> 