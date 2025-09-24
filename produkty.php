<?php
session_start();
require_once 'database.php';
require_once 'cloaking.php';

// Check cloaking eerst voordat we verdergaan
checkCloaking();

// Initialize session ID for cart
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = session_id();
}

// Get current settings
$db = Database::getInstance();
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');
$page_title = "Wszystkie Produkty - " . $shop_name;

// Initialize cart data
$cartItemCount = $db->getCartItemCount($_SESSION['cart_session_id']);
$cartItems = $db->getCartItems($_SESSION['cart_session_id']);
$cartTotal = $db->getCartTotal($_SESSION['cart_session_id']);

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$products_per_page = 42;

// Get all products
$all_products = $db->getAllProducts(true);


// Apply filters
$filtered_products = $all_products;

// Category filter
if (!empty($category_filter)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($category_filter) {
        return isset($product['category']) && $product['category'] === $category_filter;
    });
}

// Search filter
if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        $search_lower = strtolower($search_query);
        $name_match = strpos(strtolower($product['name']), $search_lower) !== false;
        
        // Strip HTML van beschrijving voor zoeken
        $clean_description = isset($product['description']) ? strip_tags($product['description']) : '';
        $desc_match = strpos(strtolower($clean_description), $search_lower) !== false;
        
        return $name_match || $desc_match;
    });
}

// Sort products
switch ($sort_by) {
    case 'price-low':
        usort($filtered_products, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        break;
    case 'price-high':
        usort($filtered_products, function($a, $b) {
            return $b['price'] <=> $a['price'];
        });
        break;
    case 'popular':
        // For now, sort by stock quantity as popularity indicator
        usort($filtered_products, function($a, $b) {
            $stock_a = isset($a['stock_quantity']) ? $a['stock_quantity'] : 0;
            $stock_b = isset($b['stock_quantity']) ? $b['stock_quantity'] : 0;
            return $stock_b <=> $stock_a;
        });
        break;
    case 'newest':
    default:
        usort($filtered_products, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });
        break;
}

// Pagination
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $products_per_page);
$offset = ($page - 1) * $products_per_page;
$products = array_slice($filtered_products, $offset, $products_per_page);

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['nl', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
// Handle translations for each product
$lang = $_SESSION['lang'] ?? 'nl';

foreach ($products as &$product) {
    $translation = $product['translations'][$lang] ?? null;
    $product['current_name'] = $translation['name'] ?? $product['name'] ?? '';
    $product['current_description'] = $translation['description'] ?? $product['description'] ?? '';
}
unset($product);

// Get all categories for filter
$categories = $db->getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_to_cart':
            $productId = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            if ($productId > 0 && $quantity > 0) {
                $db->addToCart($_SESSION['cart_session_id'], $productId, $quantity);
                $success_message = "Produkt dodany do koszyka!";
            }
            break;
            
        case 'update_cart':
            $productId = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $db->updateCartQuantity($_SESSION['cart_session_id'], $productId, $quantity);
            break;
            
        case 'remove_from_cart':
            $productId = intval($_POST['product_id']);
            $db->removeFromCart($_SESSION['cart_session_id'], $productId);
            $success_message = "Produkt usunięty z koszyka!";
            break;
            
        case 'checkout':
            $cartItems = $db->getCartItems($_SESSION['cart_session_id']);
            if (!empty($cartItems)) {
                header("Location: checkout_redirect.php");
                exit();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<style>
/* ===== RESET & BASE STYLES ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f8f9fa;
}

.content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 80px 20px;
}

/* RED TEXT BLOCK AT TOP */
.closure-notice {
    background-color: #fff;
    border: 2px solid #dc3545;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 40px;
    text-align: center;
}

.closure-notice h2 {
    color: #dc3545;
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.closure-notice p {
    color: #dc3545;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 15px;
    font-weight: 500;
}

.closure-notice p:last-child {
    margin-bottom: 0;
}

.page-header {
    text-align: center;
    margin-bottom: 60px;
}

.page-header h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    color: #333;
}

.page-header p {
    font-size: 1.3rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.filters-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 40px;
}

.filters-form {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    font-weight: 500;
    color: #333;
}

.filter-group select,
.filter-group input {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
}

.filter-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.product-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.product-image {
    position: relative;
    overflow: hidden;
    height: 200px; /* Keep fixed height for desktop */
    background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px; /* Add padding to prevent image from touching edges */
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* Changed from cover to contain */
    padding: 0; /* Remove padding from img itself */
    background: transparent; /* Remove white background */
}

.product-image i {
    font-size: 3rem;
    color: #ccc;
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #dc3545;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.delivery-badge {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.product-info {
    padding: 20px;
}

.product-category {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.product-description {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 15px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.price-current {
    font-size: 1.4rem;
    font-weight: 700;
    color: #28a745;
}

.price-old {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
}

.product-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.delivery-info {
    color: #28a745;
    font-size: 0.9rem;
    text-align: left;
    margin-top: 5px;
    padding-left: 5px;
}

.btn-add-cart {
    flex: 1;
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add-cart:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.btn-wishlist {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-wishlist:hover {
    background: #e9ecef;
}

.no-products {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-products i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #ddd;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 40px;
}

.pagination a,
.pagination span {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.pagination a:hover,
.pagination .current {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 40px 15px;
    }
    
    /* Mobile: Show red text notice with smaller text */
    .closure-notice {
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .closure-notice h2 {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    
    .closure-notice p {
        font-size: 0.95rem;
        margin-bottom: 12px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .page-header p {
        font-size: 1.1rem;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    /* MOBILE: 2 COLUMNS LAYOUT WITH EQUAL HEIGHT */
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        align-items: stretch;
    }
    
    /* Ensure all product cards have same height */
    .product-card {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    /* Fixed height for product image */
    .product-image {
        height: 140px;
        flex-shrink: 0;
    }
    
    /* Make product info flex and fill remaining space */
    .product-info {
        padding: 12px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    
    .product-category {
        margin-bottom: 4px;
        font-size: 0.7rem;
        letter-spacing: 0.3px;
    }
    
    .product-title {
        font-size: 0.85rem;
        margin-bottom: 6px;
        line-height: 1.2;
        min-height: 2.04em; /* Reserve space for 2 lines */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-weight: 600;
        height

    }
    
    .product-description {
        font-size: 0.7rem;
        margin-bottom: 8px;
        -webkit-line-clamp: 1;
        min-height: 1em; /* Reserve space for 1 line only */
        flex-grow: 1;
        line-height: 1.3;
    }
    
    .product-price {
        margin-bottom: 8px;
        margin-top: auto; /* Push price to bottom area */
        gap: 4px; /* Reduce gap between current and old price */
    }
    
    .price-current {
        font-size: 0.9rem;
        font-weight: 700;
    }
    
    .price-old {
        font-size: 0.7rem;
    }
    
    .product-actions {
        margin-bottom: 6px;
        gap: 6px;
    }
    
    .btn-add-cart {
        padding: 8px 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .btn-wishlist {
        padding: 8px;
        width: 36px;
        flex-shrink: 0;
    }
    
    .btn-wishlist i {
        font-size: 0.9rem;
    }
    
    .delivery-info {
        font-size: 0.65rem;
        margin-top: auto; /* Always at bottom */
        line-height: 1.2;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .content-wrapper {
        padding: 30px 10px;
    }
    
    .closure-notice {
        padding: 15px;
    }
    
    .closure-notice h2 {
        font-size: 1.3rem;
    }
    
    .closure-notice p {
        font-size: 0.9rem;
    }
    
    /* Very small mobile: Keep 2 columns but smaller gap */
    .products-grid {
        gap: 10px;
    }
    
    .product-image {
        height: 120px;
    }
    
    .product-info {
        padding: 12px;
    }
    
    .product-title {
        font-size: 0.9rem;
    }
    
    .product-description {
        font-size: 0.8rem;
    }
    
    .price-current {
        font-size: 1.1rem;
    }
    
    .btn-add-cart {
        padding: 8px 6px;
        font-size: 0.85rem;
    }
}








@media (max-width: 768px) {
    .content-wrapper {
        padding: 40px 15px;
        overflow-x: hidden; /* Prevent horizontal scroll */
    }
    
    /* MOBILE: 2 COLUMNS LAYOUT WITH STRICT CONSTRAINTS */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    
    /* Ensure all product cards have same height and don't overflow */
    .product-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 320px; /* Set minimum height */
        max-height: 380px; /* Set maximum height to prevent expansion */
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow: hidden; /* Hide any content that overflows */
    }
    
    /* Fixed height for product image - CRITICAL FIX */
    .product-image {
        height: 140px;
        min-height: 140px;
        max-height: 140px;
        flex-shrink: 0;
        overflow: hidden;
        position: relative;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Changed from cover to contain to prevent stretching */
        object-position: center;
    }
    
    /* Make product info flex and fill remaining space - STRICT CONSTRAINTS */
    .product-info {
        padding: 12px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        height: calc(100% - 140px); /* Remaining height after image */
        max-height: calc(100% - 140px);
        overflow: hidden;
        box-sizing: border-box;
    }
    
    .product-category {
        margin-bottom: 4px;
        font-size: 0.7rem;
        letter-spacing: 0.3px;
        height: auto;
        min-height: 0.7em;
        max-height: 1.4em; /* Max 2 lines */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .product-title {
        font-size: 0.85rem;
        margin-bottom: 6px;
        line-height: 1.2;
        height: 2.04em; /* FIXED HEIGHT for exactly 2 lines */
        min-height: 2.04em;
        max-height: 2.04em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-weight: 600;
        word-wrap: break-word;
        hyphens: auto;
    }
    
    .product-description {
        font-size: 0.7rem;
        margin-bottom: 8px;
        line-height: 1.3;
        height: 1.3em; /* FIXED HEIGHT for exactly 1 line */
        min-height: 1.3em;
        max-height: 1.3em;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    /* Price section with fixed positioning */
    .product-price {
        margin-bottom: 8px;
        margin-top: auto; /* Push to bottom area */
        gap: 4px;
        height: auto;
        min-height: 1.4em;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        flex-wrap: nowrap; /* Prevent wrapping */
    }
    
    .price-current {
        font-size: 0.9rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .price-old {
        font-size: 0.7rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .product-actions {
        margin-bottom: 6px;
        gap: 6px;
        flex-shrink: 0;
        height: 32px; /* Fixed height */
        min-height: 32px;
        max-height: 32px;
    }
    
    .btn-add-cart {
        padding: 8px 6px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        height: 32px;
        line-height: 1;
    }
    
    .btn-wishlist {
        padding: 8px;
        width: 36px;
        height: 32px;
        flex-shrink: 0;
    }
    
    .btn-wishlist i {
        font-size: 0.9rem;
    }
    
    .delivery-info {
        font-size: 0.65rem;
        line-height: 1.2;
        height: auto;
        min-height: 1.2em;
        max-height: 2.4em; /* Max 2 lines */
        overflow: hidden;
        flex-shrink: 0;
        margin-top: auto; /* Always at bottom */
    }
}

@media (max-width: 480px) {
    .content-wrapper {
        padding: 30px 10px;
        overflow-x: hidden;
    }
    
    /* Very small mobile: Keep 2 columns but with tighter constraints */
    .products-grid {
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr)); /* minmax prevents overflow */
    }
    
    .product-card {
        min-height: 300px;
        max-height: 360px;
    }
    
    .product-image {
        height: 120px;
        min-height: 120px;
        max-height: 120px;
    }
    
    .product-info {
        padding: 10px;
        height: calc(100% - 120px);
        max-height: calc(100% - 120px);
    }
    
    .product-title {
        font-size: 0.8rem;
        height: 1.92em; /* Slightly smaller for very small screens */
        min-height: 1.92em;
        max-height: 1.92em;
    }
    
    .product-description {
        font-size: 0.65rem;
        height: 1.2em;
        min-height: 1.2em;
        max-height: 1.2em;
    }
    
    .price-current {
        font-size: 0.85rem;
    }
    
    .btn-add-cart {
        font-size: 0.7rem;
        padding: 6px 4px;
    }
    
    .delivery-info {
        font-size: 0.6rem;
    }
}

/* Additional safety measures */
.products-grid * {
    box-sizing: border-box;
}

/* Prevent any element from causing horizontal scroll */
.content-wrapper, 
.products-grid, 
.product-card, 
.product-card * {
    max-width: 100%;
    overflow-wrap: break-word;
    word-wrap: break-word;
}





@media (max-width: 480px) {
    .product-title {
        font-size: 0.8rem;
        height: 2.5em;
        min-height: 1.92em;
        max-height: 3.92em;
    }
}





/* ===== FIXED PRODUCT IMAGE STYLES ===== */

/* Desktop/Default styles */
.product-image {
    position: relative;
    overflow: hidden;
    height: 200px;
    background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* This ensures no cropping */
    object-position: center;
    background: transparent;
}

.product-image i {
    font-size: 3rem;
    color: #ccc;
}

/* Mobile styles - FIXED TO PREVENT CROPPING */
@media (max-width: 768px) {
    .product-image {
        height: 140px;
        min-height: 140px;
        max-height: 140px;
        flex-shrink: 0;
        overflow: hidden;
        position: relative;
        padding: 8px; /* Add small padding for better appearance */
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* CHANGED FROM COVER TO CONTAIN */
        object-position: center;
        background: transparent;
    }
}

@media (max-width: 480px) {
    .product-image {
        height: 120px;
        min-height: 120px;
        max-height: 120px;
        padding: 6px; /* Slightly smaller padding for very small screens */
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Consistent with larger mobile screens */
        object-position: center;
    }
}

/* Optional: Add subtle border/background for better image definition */
.product-image img {
    border-radius: 4px;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.02); /* Subtle zoom effect on hover */
}



</style>

<div class="content-wrapper">
    <!-- RED CLOSURE NOTICE -->
    <div class="closure-notice">
        <h2><?php echo $texts['collection']['notice_title']; ?></h2>
        <p><?php echo $texts['collection']['notice_line1']; ?></p>
        <p><?php echo $texts['collection']['notice_line2']; ?></p>
    </div>
</div>


    <!-- <div class="page-header">
        <h1>Wszystkie Produkty</h1>
        <p>Odkryj naszą pełną kolekcję produktów z najlepszymi cenami (<?php echo $total_products; ?> produktów)</p>
    </div>

    <div class="filters-section">
        <form class="filters-form" method="GET">
            <div class="filter-group">
                <label for="category">Kategoria:</label>
                <select id="category" name="category">
                    <option value="">Wszystkie</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" 
                                <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort">Sortuj:</label>
                <select id="sort" name="sort">
                    <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Najnowsze</option>
                    <option value="price-low" <?php echo $sort_by === 'price-low' ? 'selected' : ''; ?>>Cena: od najniższej</option>
                    <option value="price-high" <?php echo $sort_by === 'price-high' ? 'selected' : ''; ?>>Cena: od najwyższej</option>
                    <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Najpopularniejsze</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Szukaj:</label>
                <input type="text" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       placeholder="Wpisz nazwę produktu...">
            </div>
            
            <button type="submit" class="filter-btn">Filtruj</button>
        </form>
    </div> -->

    <?php if (empty($products)): ?>
    <div class="no-products">
        <i class="fas fa-search"></i>
        <h3>Nie znaleziono produktów</h3>
        <p>Spróbuj zmienić filtry lub wyszukaj inne produkty.</p>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <a href="<?php echo Database::getProductUrl($product); ?>" class="product-card-link">
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['current_name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-box"></i>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                            <?php $discount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>
                            <div class="product-badge">-<?php echo $discount; ?>%</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <?php if (!empty($product['category'])): ?>
                            <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        <?php endif; ?>
                        
                        <h3 class="product-title"><?php echo htmlspecialchars($product['current_name']); ?></h3>
                        
                        <?php if (!empty($product['current_description'])): ?>
                            <?php
                            $clean_description = strip_tags($product['current_description']);
                            $short_description = strlen($clean_description) > 120 
                                ? substr($clean_description, 0, 120) . '...' 
                                : $clean_description;
                            ?>
                            <p class="product-description"><?php echo htmlspecialchars($short_description); ?></p>
                        <?php endif; ?>
                        
                        <div class="product-price">
                            <span class="price-current"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</span>
                            <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                                <span class="price-old"><?php echo number_format($product['old_price'], 2, ',', ' '); ?> €</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn-add-cart" onclick="event.preventDefault(); event.stopPropagation(); window.location.href='<?php echo Database::getProductUrl($product); ?>';">
                                <i class="fas fa-eye"></i> <?php echo $texts['collection']['view_button']; ?>
                            </button>
                            <button class="btn-wishlist" onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $product['id']; ?>)">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        <div class="delivery-info">
                            <i class="fas fa-truck"></i> <?php echo $texts['collection']['delivery_info']; ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Poprzednia</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Następna</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

</div>

<script>
// Cart functions
function openCart() {
    document.getElementById('cartSidebar').classList.add('open');
    document.getElementById('overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCart() {
    document.getElementById('cartSidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Auto-open cart when product is added (if success message exists)
<?php if (isset($success_message) && strpos($success_message, 'dodany') !== false): ?>
    setTimeout(function() {
        openCart();
    }, 500);
<?php endif; ?>

// Auto-open cart when add-to-cart form is submitted
document.addEventListener('DOMContentLoaded', function() {
    const addToCartForms = document.querySelectorAll('form[action*="add_to_cart"], .product-actions form');
    addToCartForms.forEach(function(form) {
        form.addEventListener('submit', function() {
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';
                submitBtn.disabled = true;
                
                // Auto-open cart after a short delay
                setTimeout(function() {
                    openCart();
                    // Reset button after 2 seconds
                    setTimeout(function() {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 2000);
                }, 300);
            }
        });
    });
});

function addToCart(productId) {
    // Hier zou je AJAX code kunnen toevoegen om het product aan de winkelwagen toe te voegen
    alert('Product toegevoegd aan winkelwagen! (ID: ' + productId + ')');
}

function toggleWishlist(productId) {
    // Hier zou je AJAX code kunnen toevoegen om het product aan de verlanglijst toe te voegen
    alert('Product toegevoegd aan verlanglijst! (ID: ' + productId + ')');
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>