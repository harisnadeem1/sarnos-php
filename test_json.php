<?php
// Test JSON file writing
echo "Testing JSON file operations...<br>";

$testData = ['test' => 'value', 'number' => 123];
$result = file_put_contents('test.json', json_encode($testData));

if ($result !== false) {
    echo "✓ JSON write successful<br>";
    
    $readData = json_decode(file_get_contents('test.json'), true);
    if ($readData && $readData['test'] === 'value') {
        echo "✓ JSON read successful<br>";
    } else {
        echo "✗ JSON read failed<br>";
    }
    
    unlink('test.json'); // Clean up
} else {
    echo "✗ JSON write failed<br>";
}

// Test database
require_once 'database.php';
$db = Database::getInstance();

echo "<br>Testing database...<br>";
$testProduct = [
    'name' => 'Test Product',
    'description' => 'Test Description',
    'price' => 99.99,
    'image_url' => 'https://example.com/image.jpg',
    'shopify_variant_id' => '12345',
    'category' => 'Test',
    'stock_quantity' => 10
];

try {
    $result = $db->addProduct($testProduct);
    if ($result) {
        echo "✓ Database addProduct successful<br>";
        
        $products = $db->getAllProducts();
        echo "Products found: " . count($products) . "<br>";
        
        if (file_exists('products.json')) {
            echo "✓ products.json exists<br>";
            $content = file_get_contents('products.json');
            echo "File content length: " . strlen($content) . "<br>";
        } else {
            echo "✗ products.json does not exist<br>";
        }
    } else {
        echo "✗ Database addProduct failed<br>";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}
?> 