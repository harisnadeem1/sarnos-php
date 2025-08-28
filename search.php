<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'database.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::getInstance();
    $products = $db->getAllProducts(true); // Get only active products
    
    // Filter products based on search query
    $filteredProducts = array_filter($products, function($product) use ($query) {
        $searchFields = [
            $product['name'],
            $product['description'],
            $product['category']
        ];
        
        $searchText = strtolower(implode(' ', $searchFields));
        $queryLower = strtolower($query);
        
        return strpos($searchText, $queryLower) !== false;
    });
    
    // Limit results to 8 items for performance
    $filteredProducts = array_slice($filteredProducts, 0, 8);
    
    // Format results for frontend
    $results = array_map(function($product) {
        return [
            'id' => $product['id'],
            'slug' => isset($product['slug']) ? $product['slug'] : null,
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'category' => $product['category'],
            'image_url' => $product['image_url'],
            'description' => substr($product['description'], 0, 100)
        ];
    }, $filteredProducts);
    
    echo json_encode(array_values($results));
    
} catch (Exception $e) {
    error_log('Search error: ' . $e->getMessage());
    echo json_encode([]);
}
?> 