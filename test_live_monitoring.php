<?php
require_once 'cloaking.php';

echo "<h1>🔄 Live Monitoring Test</h1>";

// Force een paar test entries
$cloaking = new CloakingSystem();

echo "<h2>📊 Huidige Monitoring Data</h2>";

$monitoringData = $cloaking->getLiveMonitoringData(10);
$stats = $cloaking->getMonitoringStats();

echo "<h3>📈 Statistieken:</h3>";
echo "<ul>";
echo "<li><strong>Totaal bezoeken:</strong> " . $stats['total'] . "</li>";
echo "<li><strong>Laatste 24u:</strong> " . $stats['last_24h'] . "</li>";
echo "<li><strong>Toegelaten:</strong> " . $stats['toegelaten'] . "</li>";
echo "<li><strong>Cloaked:</strong> " . $stats['cloaked'] . "</li>";
echo "<li><strong>Geblokkeerd:</strong> " . $stats['geblokkeerd'] . "</li>";
echo "</ul>";

echo "<h3>🗂️ Recente Activiteit (laatste 10):</h3>";
if (!empty($monitoringData)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Tijd</th><th>IP</th><th>Land</th><th>Status</th><th>Beschrijving</th><th>Pagina</th>";
    echo "</tr>";
    
    foreach ($monitoringData as $entry) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($entry['datetime']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['ip']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['country']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['status']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['description']) . "</td>";
        echo "<td>" . htmlspecialchars($entry['request_uri']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><em>Nog geen monitoring data beschikbaar. Bezoek enkele pagina's om data te genereren.</em></p>";
}

echo "<h3>🧪 Test Links:</h3>";
echo "<p>Klik op deze links om monitoring data te genereren:</p>";
echo "<ul>";
echo "<li><a href='index.php'>🏠 Homepage</a></li>";
echo "<li><a href='produkty.php'>📦 Producten</a></li>";
echo "<li><a href='about.php'>ℹ️ Over Ons</a></li>";
echo "<li><a href='kontakt.php'>📞 Contact</a></li>";
echo "<li><a href='zwroty.php'>↩️ Retourneren</a></li>";
echo "</ul>";

echo "<p style='margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 5px;'>";
echo "💡 <strong>Tip:</strong> Ga naar het admin dashboard → Live Monitoring tab voor de volledige interface met filters, auto-refresh en statistieken!";
echo "</p>";

echo "<p style='margin-top: 20px;'>";
echo "<a href='admin/dashboard.php?tab=monitoring' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Open Live Monitoring Dashboard</a>";
echo "</p>";
?> 