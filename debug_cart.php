<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Cart Debug Script</h1>";

// Check if files exist and are writable
$files = ['database.json', 'cart.json', 'products.json'];
foreach ($files as $file) {
    echo "<p><strong>$file:</strong> ";
    if (file_exists($file)) {
        echo "EXISTS";
        if (is_readable($file)) {
            echo " - READABLE";
        } else {
            echo " - NOT READABLE";
        }
        if (is_writable($file)) {
            echo " - WRITABLE";
        } else {
            echo " - NOT WRITABLE";
        }
        echo " (Size: " . filesize($file) . " bytes)";
    } else {
        echo "DOES NOT EXIST";
    }
    echo "</p>";
}

// Test database connection
try {
    require_once 'database.php';
    echo "<p><strong>Database class:</strong> LOADED SUCCESSFULLY</p>";
    
    $db = Database::getInstance();
    echo "<p><strong>Database instance:</strong> CREATED SUCCESSFULLY</p>";
    
    // Start session for testing
    session_start();
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id();
    }
    echo "<p><strong>Session ID:</strong> " . $_SESSION['cart_session_id'] . "</p>";
    
    // Test adding to cart
    $testProductId = 1;
    $testQuantity = 1;
    
    echo "<h2>Testing addToCart function...</h2>";
    $result = $db->addToCart($_SESSION['cart_session_id'], $testProductId, $testQuantity);
    
    if ($result === false) {
        echo "<p style='color: red;'><strong>ERROR:</strong> addToCart returned FALSE</p>";
    } else {
        echo "<p style='color: green;'><strong>SUCCESS:</strong> addToCart returned: " . $result . "</p>";
    }
    
    // Check cart contents
    echo "<h2>Cart Contents:</h2>";
    $cartItems = $db->getCartItems($_SESSION['cart_session_id']);
    echo "<pre>" . print_r($cartItems, true) . "</pre>";
    
    // Check cart count
    $cartCount = $db->getCartItemCount($_SESSION['cart_session_id']);
    echo "<p><strong>Cart Item Count:</strong> " . $cartCount . "</p>";
    
    // Show raw cart.json content
    echo "<h2>Raw cart.json content:</h2>";
    if (file_exists('cart.json')) {
        $cartContent = file_get_contents('cart.json');
        echo "<pre>" . htmlspecialchars($cartContent) . "</pre>";
    } else {
        echo "<p>cart.json does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
}

// Check PHP version and settings
echo "<h2>PHP Information:</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>JSON Extension:</strong> " . (extension_loaded('json') ? 'LOADED' : 'NOT LOADED') . "</p>";
echo "<p><strong>Session Extension:</strong> " . (extension_loaded('session') ? 'LOADED' : 'NOT LOADED') . "</p>";

// Test JSON operations
echo "<h2>JSON Test:</h2>";
$testArray = ['test' => 'value', 'number' => 123];
$jsonString = json_encode($testArray);
echo "<p><strong>JSON Encode:</strong> " . $jsonString . "</p>";
$decodedArray = json_decode($jsonString, true);
echo "<p><strong>JSON Decode:</strong> " . print_r($decodedArray, true) . "</p>";

echo "<p><em>Debug script completed at " . date('Y-m-d H:i:s') . "</em></p>";
?> 