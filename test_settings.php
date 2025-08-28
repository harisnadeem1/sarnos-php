<?php
require_once 'database.php';

echo "<h1>🔧 Settings Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .result { padding: 10px; margin: 10px 0; border-radius: 5px; } .success { background: #d4edda; color: #155724; } .error { background: #f8d7da; color: #721c24; } .info { background: #e3f2fd; color: #0d47a1; }</style>";

try {
    $db = Database::getInstance();
    
    echo "<div class='result info'>";
    echo "<h2>📋 Huidige Settings</h2>";
    $currentUrl = $db->getSetting('shopify_shop_url');
    echo "<p><strong>Shopify Shop URL:</strong> " . htmlspecialchars($currentUrl) . "</p>";
    echo "</div>";
    
    // Test update
    echo "<div class='result info'>";
    echo "<h2>🔄 Test Update</h2>";
    $testUrl = 'test-' . time() . '.myshopify.com';
    echo "<p>Probeer nieuwe URL op te slaan: " . htmlspecialchars($testUrl) . "</p>";
    
    $result = $db->updateSetting('shopify_shop_url', $testUrl);
    
    if ($result !== false) {
        echo "<p class='success'>✅ Update succesvol! Bytes geschreven: " . $result . "</p>";
        
        // Verifieer door opnieuw op te halen
        $newUrl = $db->getSetting('shopify_shop_url');
        
        if ($newUrl === $testUrl) {
            echo "<p class='success'>✅ Verificatie succesvol! Nieuwe URL opgehaald: " . htmlspecialchars($newUrl) . "</p>";
        } else {
            echo "<p class='error'>❌ Verificatie gefaald! Verwacht: " . htmlspecialchars($testUrl) . ", Gekregen: " . htmlspecialchars($newUrl) . "</p>";
        }
        
        // Herstel originele URL
        $db->updateSetting('shopify_shop_url', $currentUrl);
        echo "<p class='info'>🔄 Originele URL hersteld</p>";
        
    } else {
        echo "<p class='error'>❌ Update gefaald!</p>";
    }
    echo "</div>";
    
    // Test bestandspermissies
    echo "<div class='result info'>";
    echo "<h2>📁 Bestand Informatie</h2>";
    $settingsFile = dirname(__FILE__) . '/settings.json';
    echo "<p><strong>Bestand pad:</strong> " . htmlspecialchars($settingsFile) . "</p>";
    echo "<p><strong>Bestaat:</strong> " . (file_exists($settingsFile) ? '✅ Ja' : '❌ Nee') . "</p>";
    echo "<p><strong>Is leesbaar:</strong> " . (is_readable($settingsFile) ? '✅ Ja' : '❌ Nee') . "</p>";
    echo "<p><strong>Is schrijfbaar:</strong> " . (is_writable($settingsFile) ? '✅ Ja' : '❌ Nee') . "</p>";
    echo "<p><strong>Bestandsgrootte:</strong> " . filesize($settingsFile) . " bytes</p>";
    echo "<p><strong>Laatste wijziging:</strong> " . date('Y-m-d H:i:s', filemtime($settingsFile)) . "</p>";
    echo "</div>";
    
    // Toon bestandsinhoud
    echo "<div class='result info'>";
    echo "<h2>📄 Bestandsinhoud</h2>";
    $content = file_get_contents($settingsFile);
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($content);
    echo "</pre>";
    
    // Test JSON parsing
    $parsed = json_decode($content, true);
    if ($parsed !== null) {
        echo "<p class='success'>✅ JSON parsing succesvol</p>";
    } else {
        echo "<p class='error'>❌ JSON parsing gefaald! Error: " . json_last_error_msg() . "</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result error'>";
    echo "<h2>❌ Fout</h2>";
    echo "<p><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Bestand:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Regel:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<p><a href='admin/dashboard.php?tab=settings'>🔙 Terug naar Settings</a></p>";
?> 