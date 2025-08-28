<?php
require_once 'database.php';

echo "Migratie van product slugs gestart...\n";

$db = Database::getInstance();
$result = $db->migrateProductSlugs();

if ($result) {
    echo "✅ Migratie succesvol voltooid! Alle producten hebben nu slugs.\n";
    
    // Toon de gegenereerde slugs
    $products = $db->getAllProducts(false); // Include inactive products
    echo "\nGegenereerde slugs:\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($products as $product) {
        $slug = isset($product['slug']) ? $product['slug'] : 'GEEN SLUG';
        echo "ID: {$product['id']} | {$product['name']} → {$slug}\n";
    }
    
    echo str_repeat("-", 50) . "\n";
    echo "Nieuwe URLs zien er nu zo uit:\n";
    echo "Oud: product.php?id=1\n";
    echo "Nieuw: product.php?slug=pvc-wandpanelen\n";
    echo "\nOude URLs blijven werken dankzij automatische redirects!\n";
    
} else {
    echo "❌ Fout bij migratie van slugs.\n";
}
?> 