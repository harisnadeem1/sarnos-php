<?php
echo "<h1>🔧 Schrijfrechten Test</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .result { padding: 15px; margin: 10px 0; border-radius: 5px; } .success { background: #d4edda; color: #155724; } .error { background: #f8d7da; color: #721c24; } .info { background: #e3f2fd; color: #0d47a1; }</style>";

$configFile = 'cloaking_config.json';

echo "<div class='info'>";
echo "<h2>📍 Bestand Informatie</h2>";
echo "<p><strong>Bestand pad:</strong> " . realpath($configFile) . "</p>";
echo "<p><strong>Bestaat:</strong> " . (file_exists($configFile) ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>Is leesbaar:</strong> " . (is_readable($configFile) ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>Is schrijfbaar:</strong> " . (is_writable($configFile) ? '✅ Ja' : '❌ Nee') . "</p>";
echo "<p><strong>Eigenaar:</strong> " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($configFile))['name'] : 'Niet beschikbaar op Windows') . "</p>";
echo "<p><strong>Groep:</strong> " . (function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($configFile))['name'] : 'Niet beschikbaar op Windows') . "</p>";
echo "<p><strong>Permissies:</strong> " . substr(sprintf('%o', fileperms($configFile)), -4) . "</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>🖥️ PHP Process Informatie</h2>";
echo "<p><strong>PHP Gebruiker:</strong> " . (function_exists('get_current_user') ? get_current_user() : 'Niet beschikbaar') . "</p>";
echo "<p><strong>Werkdirectory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Temp directory:</strong> " . sys_get_temp_dir() . "</p>";
echo "</div>";

// Test 1: Lees het huidige bestand
echo "<div class='info'>";
echo "<h2>📖 Test 1: Bestand Lezen</h2>";
try {
    $currentContent = file_get_contents($configFile);
    echo "<div class='result success'>✅ Succesvol gelezen. Bestandsgrootte: " . strlen($currentContent) . " bytes</div>";
    echo "<pre>" . htmlspecialchars($currentContent) . "</pre>";
} catch (Exception $e) {
    echo "<div class='result error'>❌ Fout bij lezen: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Probeer te schrijven naar het bestand
echo "<div class='info'>";
echo "<h2>✏️ Test 2: Bestand Schrijven</h2>";
try {
    // Maak een backup van de huidige inhoud
    $originalContent = file_get_contents($configFile);
    $testConfig = json_decode($originalContent, true);
    $testConfig['test_timestamp'] = date('Y-m-d H:i:s');
    
    $result = file_put_contents($configFile, json_encode($testConfig, JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        echo "<div class='result success'>✅ Succesvol geschreven! Bytes geschreven: " . $result . "</div>";
        
        // Herstel de originele inhoud
        file_put_contents($configFile, $originalContent);
        echo "<div class='result success'>✅ Originele inhoud hersteld</div>";
    } else {
        echo "<div class='result error'>❌ Schrijven gefaald - file_put_contents retourneerde false</div>";
    }
} catch (Exception $e) {
    echo "<div class='result error'>❌ Fout bij schrijven: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 3: Test directory permissies
echo "<div class='info'>";
echo "<h2>📁 Test 3: Directory Permissies</h2>";
$configDir = dirname($configFile);
echo "<p><strong>Directory:</strong> " . realpath($configDir) . "</p>";
echo "<p><strong>Is schrijfbaar:</strong> " . (is_writable($configDir) ? '✅ Ja' : '❌ Nee') . "</p>";

// Probeer een test bestand te maken in de directory
$testFile = $configDir . '/test_write_' . uniqid() . '.tmp';
try {
    $result = file_put_contents($testFile, 'test');
    if ($result !== false) {
        echo "<div class='result success'>✅ Kan bestanden maken in directory</div>";
        unlink($testFile); // Verwijder test bestand
    } else {
        echo "<div class='result error'>❌ Kan geen bestanden maken in directory</div>";
    }
} catch (Exception $e) {
    echo "<div class='result error'>❌ Fout bij maken test bestand: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Probeer CloakingSystem class
echo "<div class='info'>";
echo "<h2>🔧 Test 4: CloakingSystem Class</h2>";
try {
    require_once 'cloaking.php';
    $cloaking = new CloakingSystem();
    echo "<div class='result success'>✅ CloakingSystem class succesvol geladen</div>";
    
    $config = $cloaking->getConfig();
    echo "<p><strong>Huidige configuratie:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT)) . "</pre>";
    
    // Test saveConfig met een kleine wijziging
    $testConfig = $config;
    $testConfig['test_write_check'] = time();
    
    $cloaking->saveConfig($testConfig);
    echo "<div class='result success'>✅ saveConfig methode werkt correct!</div>";
    
    // Herstel originele configuratie
    unset($testConfig['test_write_check']);
    $cloaking->saveConfig($testConfig);
    echo "<div class='result success'>✅ Originele configuratie hersteld</div>";
    
} catch (Exception $e) {
    echo "<div class='result error'>❌ Fout met CloakingSystem: " . $e->getMessage() . "</div>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

echo "<h2>🎯 Conclusie</h2>";
echo "<p>Als alle tests slagen, dan zou het systeem moeten werken. Als er fouten zijn, controleer dan de permissies in je webserver configuratie.</p>";
?> 