<?php
/**
 * JSON-based Data Storage
 * Simple file-based storage for easy setup
 */

class Database {
    private static $instance = null;
    private $productsFile;
    private $settingsFile;
    private $cartFile;

    private function __construct() {
        // Set file paths relative to the project root
        $rootDir = dirname(__FILE__);
        $this->productsFile = $rootDir . '/products.json';
        $this->settingsFile = $rootDir . '/settings.json';
        $this->cartFile = $rootDir . '/cart.json';
        
        $this->initializeFiles();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function initializeFiles() {
        // Initialize products file
        if (!file_exists($this->productsFile)) {
            file_put_contents($this->productsFile, json_encode([]));
        }
        
        // Initialize settings file
        if (!file_exists($this->settingsFile)) {
            $defaultSettings = [
                'shop_name' => 'TechShop',
                'shop_description' => 'Najlepsze produkty technologiczne w Polsce',
                'shopify_shop_url' => 'dx1b30-ku.myshopify.com',
                'currency' => 'zł',
                'free_shipping_threshold' => '199',
                'categories' => ['Laptopy', 'Smartfony', 'Słuchawki', 'Akcesoria', 'Gaming']
            ];
            file_put_contents($this->settingsFile, json_encode($defaultSettings));
        }
        
        // Initialize cart file
        if (!file_exists($this->cartFile)) {
            file_put_contents($this->cartFile, json_encode([]));
        }
    }

    // Settings methods
    public function getSetting($key) {
        try {
            if (!file_exists($this->settingsFile)) {
                // Initialiseer settings bestand als het niet bestaat
                $this->initializeFiles();
            }
            
            $content = file_get_contents($this->settingsFile);
            if ($content === false) {
                error_log("Kon settings bestand niet lezen: " . $this->settingsFile);
                return null;
            }
            
            $settings = json_decode($content, true);
            if ($settings === null) {
                error_log("Settings JSON is corrupt in: " . $this->settingsFile);
                // Herinitialiseer als JSON corrupt is
                $this->initializeFiles();
                $content = file_get_contents($this->settingsFile);
                $settings = json_decode($content, true);
            }
            
            return isset($settings[$key]) ? $settings[$key] : null;
            
        } catch (Exception $e) {
            error_log("Database getSetting error: " . $e->getMessage());
            return null;
        }
    }



    // Add this method to your Database class in database.php

public function getProductsByCategory($category, $limit = null) {
    try {
        $products = $this->getAllProducts(true); // Get all active products
        
        // Filter products by category
        $filteredProducts = array_filter($products, function($product) use ($category) {
            return strcasecmp($product['category'], $category) === 0; // Case-insensitive comparison
        });
        
        // Sort by created_at or id (assuming newer products should come first)
        usort($filteredProducts, function($a, $b) {
    // If created_at exists, sort by that, otherwise by id
    if (isset($a['created_at']) && isset($b['created_at'])) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    }
    return $a['id'] - $b['id'];
});
        
        // Apply limit if specified
        if ($limit && $limit > 0) {
            $filteredProducts = array_slice($filteredProducts, 0, $limit);
        }
        
        return array_values($filteredProducts); // Re-index array
        
    } catch (Exception $e) {
        error_log("Error getting products by category: " . $e->getMessage());
        return [];
    }
}

    public function updateSetting($key, $value) {
        try {
            // Controleer of bestand beschrijfbaar is
            if (file_exists($this->settingsFile) && !is_writable($this->settingsFile)) {
                throw new Exception("Settings bestand is niet schrijfbaar: " . $this->settingsFile);
            }
            
            // Lees huidige settings
            $content = file_get_contents($this->settingsFile);
            if ($content === false) {
                throw new Exception("Kon settings bestand niet lezen: " . $this->settingsFile);
            }
            
            $settings = json_decode($content, true);
            if ($settings === null) {
                // Als JSON corrupt is, initialiseer met defaults
                $settings = [
                    'shop_name' => 'TechShop',
                    'shop_description' => 'Najlepsze produkty technologiczne w Polsce',
                    'shopify_shop_url' => 'dx1b30-ku.myshopify.com',
                    'currency' => 'zł',
                    'free_shipping_threshold' => '199',
                    'categories' => ['Laptopy', 'Smartfony', 'Słuchawki', 'Akcesoria', 'Gaming']
                ];
            }
            
            // Update de specifieke setting
            $settings[$key] = $value;
            
            // Sla op met JSON_PRETTY_PRINT voor betere leesbaarheid
            $result = file_put_contents($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT), LOCK_EX);
            
            if ($result === false) {
                throw new Exception("Kon settings niet opslaan naar: " . $this->settingsFile);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database updateSetting error: " . $e->getMessage());
            throw $e;
        }
    }

    // Slug generation method
    private function generateSlug($text) {
        // Convert to lowercase
        $slug = strtolower($text);
        
        // Replace Polish characters
        $polish_chars = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
            'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z'
        ];
        $slug = strtr($slug, $polish_chars);
        
        // Replace spaces and special characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        // Ensure slug is not empty
        if (empty($slug)) {
            $slug = 'product-' . time();
        }
        
        return $slug;
    }

    // Check if slug exists and make it unique
    private function ensureUniqueSlug($slug, $excludeId = null) {
        $products = json_decode(file_get_contents($this->productsFile), true);
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $exists = false;
            foreach ($products as $product) {
                if (isset($product['slug']) && $product['slug'] === $slug) {
                    if ($excludeId === null || $product['id'] !== $excludeId) {
                        $exists = true;
                        break;
                    }
                }
            }
            
            if (!$exists) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Product methods
    public function getAllProducts($activeOnly = true) {
        $products = json_decode(file_get_contents($this->productsFile), true);
        if ($activeOnly) {
            $products = array_filter($products, function($product) {
                return isset($product['is_active']) && $product['is_active'] == true;
            });
        }
        // Sort by created_at (newest first)
        usort($products, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        return array_values($products);
    }

    public function getProduct($id) {
        $products = json_decode(file_get_contents($this->productsFile), true);
        foreach ($products as $product) {
            if ($product['id'] == $id) {
                return $product;
            }
        }
        return null;
    }

    public function getProductBySlug($slug) {
        $products = json_decode(file_get_contents($this->productsFile), true);
        foreach ($products as $product) {
            if (isset($product['slug']) && $product['slug'] === $slug) {
                return $product;
            }
        }
        return null;
    }

   public function addProduct($data) {
    $products = json_decode(file_get_contents($this->productsFile), true);
    if (!is_array($products)) {
        $products = [];
    }

    // Generate new ID
    $newId = 1;
    if (!empty($products)) {
        $maxId = max(array_column($products, 'id'));
        $newId = $maxId + 1;
    }

    // Handle multiple images
    $images = [];
    if (!empty($data['image_url'])) {
        $images[] = $data['image_url'];
    }
    if (!empty($data['images'])) {
        foreach ($data['images'] as $img) {
            $img = trim($img);
            if (!empty($img)) {
                $images[] = $img;
            }
        }
    }

    // Use Dutch name for slug generation
    $baseName = $data['translations']['nl']['name'] ?? 'product';
    $slug = $this->generateSlug($baseName);
    $slug = $this->ensureUniqueSlug($slug);

    // Build product entry
    $newProduct = [
        'id' => $newId,
        'slug' => $slug,
        'translations' => $data['translations'], // full nl + fr block
        'price' => floatval($data['price']),
        'old_price' => isset($data['old_price']) ? floatval($data['old_price']) : 0,
        'image_url' => $data['image_url'] ?? '', // keep for compatibility
        'images' => $images,
        'shopify_variant_id' => $data['shopify_variant_id'] ?? '',
        'category' => $data['category'] ?? '',
        'stock_quantity' => intval($data['stock_quantity'] ?? 0),
        'is_active' => $data['is_active'] ?? true,
        'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s')
    ];

    $products[] = $newProduct;

    return file_put_contents(
        $this->productsFile,
        json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}


    public function updateProduct($id, $data) {
        $products = json_decode(file_get_contents($this->productsFile), true);
        
        foreach ($products as &$product) {
            if ($product['id'] == $id) {
                // Handle multiple images
                $images = [];
                if (!empty($data['image_url'])) {
                    $images[] = $data['image_url'];
                }
                if (!empty($data['additional_images'])) {
                    $additionalImages = explode(',', $data['additional_images']);
                    foreach ($additionalImages as $img) {
                        $img = trim($img);
                        if (!empty($img)) {
                            $images[] = $img;
                        }
                    }
                }
                
                // Update slug if name changed
                if ($product['name'] !== $data['name']) {
                    $slug = $this->generateSlug($data['name']);
                    $slug = $this->ensureUniqueSlug($slug, $id);
                    $product['slug'] = $slug;
                }
                
                $product['name'] = $data['name'];
                $product['description'] = $data['description'];
                $product['price'] = floatval($data['price']);
                $product['old_price'] = isset($data['old_price']) ? floatval($data['old_price']) : 0;
                $product['image_url'] = $data['image_url'];
                $product['images'] = $images;
                $product['shopify_variant_id'] = $data['shopify_variant_id'];
                $product['category'] = $data['category'];
                $product['stock_quantity'] = intval($data['stock_quantity']);
                $product['updated_at'] = date('Y-m-d H:i:s');
                $product['variants'] = isset($data['variants']) ? $data['variants'] : [];
                break;
            }
        }
        
        return file_put_contents($this->productsFile, json_encode($products));
    }

    public function deleteProduct($id) {
        $products = json_decode(file_get_contents($this->productsFile), true);
        
        // Remove product completely from array
        $products = array_filter($products, function($product) use ($id) {
            return $product['id'] != $id;
        });
        
        // Reindex array to avoid gaps
        $products = array_values($products);
        
        return file_put_contents($this->productsFile, json_encode($products));
    }

    public function deleteMultipleProducts($ids) {
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $products = json_decode(file_get_contents($this->productsFile), true);
        
        // Remove multiple products from array
        $products = array_filter($products, function($product) use ($ids) {
            return !in_array($product['id'], $ids);
        });
        
        // Reindex array to avoid gaps
        $products = array_values($products);
        
        return file_put_contents($this->productsFile, json_encode($products));
    }

    // Migration method to add slugs to existing products
    public function migrateProductSlugs() {
        $products = json_decode(file_get_contents($this->productsFile), true);
        $updated = false;
        
        foreach ($products as &$product) {
            if (!isset($product['slug']) || empty($product['slug'])) {
                $slug = $this->generateSlug($product['name']);
                $slug = $this->ensureUniqueSlug($slug, $product['id']);
                $product['slug'] = $slug;
                $updated = true;
            }
        }
        
        if ($updated) {
            return file_put_contents($this->productsFile, json_encode($products));
        }
        return true;
    }

    // Cart methods
    public function addToCart($sessionId, $productId, $quantity, $variantIndex = null, $variantData = null) {
        $cartData = json_decode(file_get_contents($this->cartFile), true);
        
        $cartKey = $sessionId . '_' . $productId;
        if ($variantIndex !== null) {
            $cartKey .= '_v' . $variantIndex;
        }
        
        if (isset($cartData[$cartKey])) {
            $cartData[$cartKey]['quantity'] += $quantity;
        } else {
            $cartData[$cartKey] = [
                'session_id' => $sessionId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'created_at' => date('Y-m-d H:i:s'),
                'variant_index' => $variantIndex,
                'variant_data' => $variantData
            ];
        }
        
        return file_put_contents($this->cartFile, json_encode($cartData));
    }

   public function getCartItems($sessionId) {
    $cartData = json_decode(file_get_contents($this->cartFile), true);
    $products = json_decode(file_get_contents($this->productsFile), true);

    $lang = $_SESSION['lang'] ?? 'nl'; // current language

    $cartItems = [];
    foreach ($cartData as $cartItem) {
        if ($cartItem['session_id'] == $sessionId) {
            // Find product details
            foreach ($products as $product) {
                if ($product['id'] == $cartItem['product_id']) {

                    // Pick translation if available
                    $translation = $product['translations'][$lang] ?? [];
                    $currentName = $translation['name'] ?? ($product['name'] ?? '');
                    $currentDescription = $translation['description'] ?? ($product['description'] ?? '');

                    $item = array_merge($cartItem, [
                        'current_name'        => $currentName,
                        'current_description' => $currentDescription,
                        'price'               => $product['price'],
                        'old_price'           => $product['old_price'] ?? 0,
                        'image_url'           => $product['image_url'] ?? '',
                        'shopify_variant_id'  => $product['shopify_variant_id'] ?? ''
                    ]);

                    // Override name/image if variant selected
                    if (!empty($cartItem['variant_data'])) {
                        if (!empty($cartItem['variant_data']['name'])) {
                            $item['current_name'] .= ' (' . $cartItem['variant_data']['name'] . ')';
                        }
                        if (!empty($cartItem['variant_data']['image'])) {
                            $item['image_url'] = $cartItem['variant_data']['image'];
                        }
                    }

                    $cartItems[] = $item;
                    break;
                }
            }
        }
    }

    return $cartItems;
}


    public function updateCartQuantity($sessionId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($sessionId, $productId);
        }
        
        $cartData = json_decode(file_get_contents($this->cartFile), true);
        $cartKey = $sessionId . '_' . $productId;
        
        if (isset($cartData[$cartKey])) {
            $cartData[$cartKey]['quantity'] = $quantity;
        }
        
        return file_put_contents($this->cartFile, json_encode($cartData));
    }

    public function removeFromCart($sessionId, $productId) {
        $cartData = json_decode(file_get_contents($this->cartFile), true);
        $cartKey = $sessionId . '_' . $productId;
        
        if (isset($cartData[$cartKey])) {
            unset($cartData[$cartKey]);
        }
        
        return file_put_contents($this->cartFile, json_encode($cartData));
    }

    public function clearCart($sessionId) {
        $cartData = json_decode(file_get_contents($this->cartFile), true);
        
        foreach ($cartData as $key => $cartItem) {
            if ($cartItem['session_id'] == $sessionId) {
                unset($cartData[$key]);
            }
        }
        
        return file_put_contents($this->cartFile, json_encode($cartData));
    }

    public function getCartTotal($sessionId) {
        $cartItems = $this->getCartItems($sessionId);
        $total = 0;
        
        foreach ($cartItems as $item) {
            $total += $item['quantity'] * $item['price'];
        }
        
        return $total;
    }

    public function getCartItemCount($sessionId) {
        $cartItems = $this->getCartItems($sessionId);
        $count = 0;
        
        foreach ($cartItems as $item) {
            $count += $item['quantity'];
        }
        
        return $count;
    }

    // Category methods
    public function getCategories() {
        $categories = $this->getSetting('categories');
        return $categories ? $categories : ['Laptopy', 'Smartfony', 'Słuchawki', 'Akcesoria', 'Gaming', 'Artykuły ogrodowe'];
    }

    public function addCategory($categoryName) {
        $categories = $this->getCategories();
        if (!in_array($categoryName, $categories)) {
            $categories[] = $categoryName;
            return $this->updateSetting('categories', $categories);
        }
        return true;
    }

    public function deleteCategory($categoryName) {
        $categories = $this->getCategories();
        $categories = array_filter($categories, function($cat) use ($categoryName) {
            return $cat !== $categoryName;
        });
        return $this->updateSetting('categories', array_values($categories));
    }

    // Helper function to generate product URL
    public static function getProductUrl($product) {
        if (isset($product['slug']) && !empty($product['slug'])) {
            return 'product.php?slug=' . urlencode($product['slug']);
        }
        // Fallback to ID if no slug
        return 'product.php?id=' . $product['id'];
    }

    // Generate unique ID for products
    private function generateUniqueId() {
        $products = json_decode(file_get_contents($this->productsFile), true);
        $newId = 1;
        if (!empty($products)) {
            $maxId = max(array_column($products, 'id'));
            $newId = $maxId + 1;
        }
        return $newId;
    }

    // PAGE MANAGEMENT FUNCTIONS
    
    // Get all pages
    public function getAllPages() {
        $pagesFile = 'pages.json';
        if (!file_exists($pagesFile)) {
            return [];
        }
        $pages = json_decode(file_get_contents($pagesFile), true);
        return $pages ? $pages : [];
    }

    // Add new page
    public function addPage($name, $category, $content = '', $slug = '') {
        $pagesFile = 'pages.json';
        $pages = $this->getAllPages();
        
        // Generate slug if not provided
        if (empty($slug)) {
            $slug = $this->generatePageSlug($name);
        }
        
        // Check if slug already exists
        foreach ($pages as $page) {
            if ($page['slug'] === $slug) {
                return false; // Slug already exists
            }
        }
        
        $newPage = [
            'id' => $this->generateUniquePageId(),
            'name' => $name,
            'category' => $category,
            'content' => $content,
            'slug' => $slug,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'active' => true
        ];
        
        $pages[] = $newPage;
        
        // Save pages
        $result = file_put_contents($pagesFile, json_encode($pages, JSON_PRETTY_PRINT));
        
        if ($result !== false) {
            // Create the actual PHP file
            $this->createPageFile($newPage);
            return true;
        }
        return false;
    }

    // Generate unique page ID
    private function generateUniquePageId() {
        $pages = $this->getAllPages();
        $newId = 1;
        if (!empty($pages)) {
            $maxId = max(array_column($pages, 'id'));
            $newId = $maxId + 1;
        }
        return $newId;
    }

    // Generate slug from name for pages
    private function generatePageSlug($name) {
        $slug = strtolower($name);
        $slug = str_replace(['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'], 
                           ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'], $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    // Create physical page file
    private function createPageFile($page) {
        $content = $this->getPageTemplate($page);
        $filename = $page['slug'] . '.php';
        file_put_contents($filename, $content);
    }

    // Get page template
    private function getPageTemplate($page) {
        return '<?php
session_start();
require_once \'database.php\';

// Initialize session ID for cart
if (!isset($_SESSION[\'cart_session_id\'])) {
    $_SESSION[\'cart_session_id\'] = session_id();
}

$db = new Database();

// Get cart information
$cartItems = $db->getCartItems($_SESSION[\'cart_session_id\']);
$cartTotal = $db->getCartTotal($_SESSION[\'cart_session_id\']);
$cartItemCount = $db->getCartItemCount($_SESSION[\'cart_session_id\']);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($page['name']) . ' - Polski Sklepi</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: \'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .content-wrapper {
            min-height: calc(100vh - 200px);
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 60px 20px;
            background: linear-gradient(135deg, #87CEEB 0%, #B0E0E6 100%);
            color: #2c3e50;
            border-radius: 15px;
        }

        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .page-content {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .page-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include \'header.php\'; ?>

    <div class="content-wrapper">
        <div class="page-header">
            <h1>' . htmlspecialchars($page['name']) . '</h1>
        </div>

        <div class="page-content">
            ' . $page['content'] . '
        </div>
    </div>

    <?php include \'footer.php\'; ?>
</body>
</html>';
    }

    // Update page
    public function updatePage($id, $name, $category, $content, $slug = '') {
        $pagesFile = 'pages.json';
        $pages = $this->getAllPages();
        
        foreach ($pages as &$page) {
            if ($page['id'] == $id) {
                $oldSlug = $page['slug'];
                
                if (empty($slug)) {
                    $slug = $this->generatePageSlug($name);
                }
                
                $page['name'] = $name;
                $page['category'] = $category;
                $page['content'] = $content;
                $page['slug'] = $slug;
                $page['updated_at'] = date('Y-m-d H:i:s');
                
                // Save pages
                file_put_contents($pagesFile, json_encode($pages, JSON_PRETTY_PRINT));
                
                // Rename file if slug changed
                if ($oldSlug !== $slug) {
                    if (file_exists($oldSlug . '.php')) {
                        rename($oldSlug . '.php', $slug . '.php');
                    }
                }
                
                // Update page file
                $this->createPageFile($page);
                return true;
            }
        }
        return false;
    }

    // Delete page
    public function deletePage($id) {
        $pagesFile = 'pages.json';
        $pages = $this->getAllPages();
        
        foreach ($pages as $index => $page) {
            if ($page['id'] == $id) {
                // Delete physical file
                if (file_exists($page['slug'] . '.php')) {
                    unlink($page['slug'] . '.php');
                }
                
                // Remove from array
                unset($pages[$index]);
                $pages = array_values($pages); // Reindex
                
                // Save pages
                return file_put_contents($pagesFile, json_encode($pages, JSON_PRETTY_PRINT)) !== false;
            }
        }
        return false;
    }

    // Get page by slug
    public function getPageBySlug($slug) {
        $pages = $this->getAllPages();
        foreach ($pages as $page) {
            if ($page['slug'] === $slug && $page['active']) {
                return $page;
            }
        }
        return null;
    }

    // Get page categories
    public function getPageCategories() {
        $pages = $this->getAllPages();
        $categories = [];
        foreach ($pages as $page) {
            if (!in_array($page['category'], $categories)) {
                $categories[] = $page['category'];
            }
        }
        sort($categories);
        return $categories;
    }

    // Export all products with images
    public function exportProducts() {
        $products = json_decode(file_get_contents($this->productsFile), true);
        $exportData = [
            'export_date' => date('Y-m-d H:i:s'),
            'total_products' => count($products),
            'products' => $products,
            'categories' => $this->getCategories(),
            'settings' => [
                'shop_name' => $this->getSetting('shop_name'),
                'shop_description' => $this->getSetting('shop_description'),
                'shopify_shop_url' => $this->getSetting('shopify_shop_url')
            ]
        ];
        
        return $exportData;
    }

    // Import products from export data
    public function importProducts($importData, $overwrite = false) {
        if (!isset($importData['products']) || !is_array($importData['products'])) {
            throw new Exception('Ongeldige import data: geen producten gevonden');
        }

        $existingProducts = json_decode(file_get_contents($this->productsFile), true);
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($importData['products'] as $product) {
            // Check if product already exists (by Shopify variant ID)
            $exists = false;
            foreach ($existingProducts as $existingProduct) {
                if ($existingProduct['shopify_variant_id'] === $product['shopify_variant_id']) {
                    $exists = true;
                    if ($overwrite) {
                        // Update existing product
                        $product['id'] = $existingProduct['id'];
                        $product['created_at'] = $existingProduct['created_at'];
                        $product['updated_at'] = date('Y-m-d H:i:s');
                        $existingProducts = array_map(function($p) use ($product) {
                            return ($p['id'] == $product['id']) ? $product : $p;
                        }, $existingProducts);
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }
                    break;
                }
            }

            if (!$exists) {
                // Add new product
                $product['id'] = $this->generateUniqueId();
                $product['created_at'] = date('Y-m-d H:i:s');
                $product['updated_at'] = date('Y-m-d H:i:s');
                $existingProducts[] = $product;
                $importedCount++;
            }
        }

        // Save updated products
        if (file_put_contents($this->productsFile, json_encode($existingProducts))) {
            return [
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'total' => count($importData['products'])
            ];
        } else {
            throw new Exception('Fout bij opslaan van geïmporteerde producten');
        }
    }

    // Download image and save locally
    public function downloadImage($imageUrl, $productId) {
        if (empty($imageUrl)) {
            return null;
        }

        // Create uploads directory if it doesn't exist
        $uploadsDir = '../uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg'; // Default extension
        }
        $filename = 'product_' . $productId . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadsDir . $filename;

        // Download image
        $imageContent = file_get_contents($imageUrl);
        if ($imageContent === false) {
            return null; // Could not download image
        }

        // Save image locally
        if (file_put_contents($filepath, $imageContent)) {
            return 'uploads/' . $filename;
        }

        return null;
    }

    // Process all images for a product
    public function processProductImages($product) {
        $processedProduct = $product;
        
        // Process main image
        if (!empty($product['image_url']) && !str_starts_with($product['image_url'], 'uploads/')) {
            $localImage = $this->downloadImage($product['image_url'], $product['id']);
            if ($localImage) {
                $processedProduct['image_url'] = $localImage;
            }
        }

        // Process additional images
        if (!empty($product['additional_images'])) {
            $additionalImages = is_array($product['additional_images']) 
                ? $product['additional_images'] 
                : explode(',', $product['additional_images']);
            
            $processedAdditionalImages = [];
            foreach ($additionalImages as $imageUrl) {
                $imageUrl = trim($imageUrl);
                if (!empty($imageUrl) && !str_starts_with($imageUrl, 'uploads/')) {
                    $localImage = $this->downloadImage($imageUrl, $product['id']);
                    if ($localImage) {
                        $processedAdditionalImages[] = $localImage;
                    } else {
                        $processedAdditionalImages[] = $imageUrl; // Keep original if download fails
                    }
                } else {
                    $processedAdditionalImages[] = $imageUrl;
                }
            }
            $processedProduct['additional_images'] = $processedAdditionalImages;
        }

        // Process variant images
        if (!empty($product['variants']) && is_array($product['variants'])) {
            foreach ($product['variants'] as &$variant) {
                if (!empty($variant['image']) && !str_starts_with($variant['image'], 'uploads/')) {
                    $localImage = $this->downloadImage($variant['image'], $product['id']);
                    if ($localImage) {
                        $variant['image'] = $localImage;
                    }
                }
            }
            $processedProduct['variants'] = $product['variants'];
        }

        return $processedProduct;
    }
}

// Initialize database instance
$db = Database::getInstance();
?> 