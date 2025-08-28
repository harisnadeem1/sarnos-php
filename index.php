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

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_to_cart':
                $productId = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                if ($productId > 0 && $quantity > 0) {
                    $db->addToCart($_SESSION['cart_session_id'], $productId, $quantity);
                    $success_message = "Product toegevoegd aan winkelwagen!";
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
}

// Get current settings
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');

// Get all active products and limit to 6 for homepage
$allProducts = $db->getAllProducts(true);
$products = array_slice($allProducts, 0, 6); // Show only first 6 products
// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['nl', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'nl';

// Map translations for each product
foreach ($products as &$product) {
    $translation = $product['translations'][$lang] ?? null;
    $product['current_name'] = $translation['name'] ?? $product['name'] ?? '';
    $product['current_description'] = $translation['description'] ?? $product['description'] ?? '';
    $product['current_variants'] = $translation['variants'] ?? $product['variants'] ?? [];
}
unset($product); // break reference

// Get cart information
$cartItems = $db->getCartItems($_SESSION['cart_session_id']);
$cartTotal = $db->getCartTotal($_SESSION['cart_session_id']);
$cartItemCount = $db->getCartItemCount($_SESSION['cart_session_id']);



// READ CATEGORIES FROM SETTINGS.JSON FOR COLLECTIONS
$settingsFile = 'settings.json';
$settings = json_decode(file_get_contents($settingsFile), true);
$categories = $settings['categories'] ?? [];

// Helper functions
function getCategoryImage($db, $category) {
    $products = $db->getProductsByCategory($category, 1);
    if (!empty($products) && $products[0]['image_url']) {
        return $products[0]['image_url'];
    }
    return null;
}

function getCategoryProductCount($db, $category) {
    $products = $db->getProductsByCategory($category);
    return count($products);
}






?>

<!DOCTYPE html>
<html lang="pl">
<head>

<!-- Meta Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');

  fbq('init', '698814149641552'); // <-- Your Pixel ID
  fbq('track', 'PageView');
</script>
<noscript>
  <img height="1" width="1" style="display:none"
       src="https://www.facebook.com/tr?id=698814149641552&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->










    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($shop_name); ?> - <?php echo htmlspecialchars($shop_description); ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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

        /* ===== HERO SECTION ===== */
        .hero-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 120px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(ellipse 800px 200px at 50% 20%, rgba(220, 53, 69, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse 600px 150px at 80% 80%, rgba(220, 53, 69, 0.1) 0%, transparent 50%);
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 25px;
            line-height: 1.1;
            position: relative;
            z-index: 3;
            color: white;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .hero-content p {
            font-size: 1.4rem;
            margin-bottom: 50px;
            opacity: 0.95;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            position: relative;
            z-index: 3;
            font-weight: 400;
            letter-spacing: 0.3px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #667eea;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255, 255, 255, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e9ecef 100%);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(255, 255, 255, 0.3);
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
            min-height: auto;
            line-height: 1.2;
        }

        .btn-outline {
            background: #000000;
            border: 1px solid #000000;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-outline:hover {
            background: #333333;
            border-color: #333333;
            color: white;
            transform: translateY(-1px);
        }

        /* ===== PRODUCTS SECTION ===== */
        .products {
            padding: 80px 20px;
            background: white;
        }

        .products-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 60px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 2px solid white;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #0070f3;
        }

        .product-image {
            height: 250px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 3rem;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-image:hover img {
            transform: scale(1.05);
        }

        .product-image .fallback-icon {
            font-size: 4rem;
            opacity: 0.7;
        }

        .product-info {
            padding: 24px;
        }

        .product-category {
            font-size: 12px;
            color: #0070f3;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .product-info h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .product-price {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0070f3;
            margin-bottom: 12px;
        }

        .product-info p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 8px;
        }

        .quantity-input {
            width: 50px;
            padding: 6px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            text-align: center;
            font-size: 12px;
        }

        /* ===== PRIJS STYLING VOOR HOMEPAGE ===== */
        .product-price-container {
            margin-bottom: 12px;
        }

        .price-with-discount {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .old-price {
            color: #999;
            font-size: 1.3rem;
            font-weight: 600;
            text-decoration: line-through;
            text-decoration-color: #dc3545;
            text-decoration-thickness: 3px;
            opacity: 0.8;
        }

        .current-price {
            color: #0070f3;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .discount-badge {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.8px;
            box-shadow: 0 3px 12px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
            text-transform: uppercase;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Mobile responsiveness voor prijzen */
        @media (max-width: 768px) {
            .price-with-discount {
                gap: 8px;
            }
            
            .old-price {
                font-size: 1rem;
            }
            
            .current-price {
                font-size: 1.4rem;
            }
            
            .discount-badge {
                padding: 4px 8px;
                font-size: 11px;
            }
        }

        /* ===== CART SIDEBAR ===== */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 2000;
            overflow-y: auto;
        }

        .cart-sidebar.open {
            right: 0;
        }

        .cart-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .cart-content {
            padding: 20px;
        }

        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        .cart-item-info h5 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .cart-item-price {
            color: #0070f3;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-total {
            border-top: 2px solid #e9ecef;
            padding-top: 20px;
            margin-top: 20px;
        }

        .cart-total-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0070f3;
            margin-bottom: 20px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* ===== OVERLAY ===== */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1500;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .search-bar input {
                width: 200px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 0 10px;
            }

            .product-card {
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                border: 1px solid #f0f0f0;
                transition: all 0.2s ease;
            }

            .product-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
                border-color: #0070f3;
            }

            .product-image {
                height: 140px;
                border-radius: 6px 6px 0 0;
            }

            .product-info {
                padding: 12px;
            }

            .product-category {
                font-size: 10px;
                margin-bottom: 4px;
            }

            .product-info h4 {
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 6px;
                line-height: 1.3;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .product-price {
                font-size: 14px;
                font-weight: 700;
                margin-bottom: 8px;
            }

            .product-info p {
                display: none;
            }

            .product-actions {
                margin-top: 6px;
            }

            .btn-small {
                padding: 4px 8px;
                font-size: 10px;
                border-radius: 3px;
                min-height: auto;
                line-height: 1.1;
            }

            .btn-outline {
                border-width: 1px;
                gap: 4px;
            }

            .price-with-discount {
                gap: 4px;
                flex-direction: column;
                align-items: flex-start;
            }

            .old-price {
                font-size: 11px;
            }

            .current-price {
                font-size: 14px;
            }

            .discount-badge {
                padding: 2px 6px;
                font-size: 9px;
                border-radius: 12px;
            }

            .cart-sidebar {
                width: 100%;
                right: -100%;
            }
        }

        @media (max-width: 480px) {
            .header-content {
                padding: 0 10px;
            }

            .search-bar {
                display: none;
            }

            .hero {
                padding: 60px 20px;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .products-grid {
                gap: 8px;
                padding: 0 8px;
            }

            .product-info {
                padding: 8px;
            }

            .product-info h4 {
                font-size: 12px;
            }

            .product-price {
                font-size: 13px;
            }

            .btn-small {
                padding: 5px 8px;
                font-size: 10px;
            }
        }

        .hero-banner {
            background: linear-gradient(135deg, #87CEEB 0%, #B0E0E6 100%);
            color: #2c3e50;
            padding: 100px 20px;
            text-align: center;
            margin-bottom: 0;
            position: relative;
            overflow: hidden;
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                linear-gradient(45deg, rgba(255, 255, 255, 0.05) 25%, transparent 25%, transparent 75%, rgba(255, 255, 255, 0.05) 75%);
            background-size: 100px 100px, 150px 150px, 200px 200px, 40px 40px;
            pointer-events: none;
            opacity: 0.6;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(to top, rgba(248, 249, 250, 0.3), transparent);
            pointer-events: none;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            line-height: 1.2;
            letter-spacing: -0.01em;
            color: #2c3e50;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.3);
        }

        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            line-height: 1.6;
            opacity: 0.8;
            font-weight: 400;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            color: #34495e;
        }

        .hero-buttons {
            display: flex;
            gap: 24px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-primary, .btn-secondary {
            padding: 18px 36px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f5f8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.7);
            color: #2c3e50;
            border: 2px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
            color: #1a252f;
        }

        @media (max-width: 768px) {
            .hero-banner {
                padding: 80px 20px;
                min-height: 80vh;
            }
            
            .hero-content h1 {
                font-size: 2.8rem;
                margin-bottom: 20px;
            }
            
            .hero-content p {
                font-size: 1.1rem;
                margin-bottom: 32px;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
                padding: 16px 32px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 2.2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
        }

        /* Product card buttons - make them black */
        .product-actions .btn-primary {
            background: #111 !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(17, 17, 17, 0.3) !important;
        }

        .product-actions .btn-primary:hover {
            background: #333 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(17, 17, 17, 0.4) !important;
        }

        /* Product prices - make them red */
        .products .product-price {
            color: #dc3545 !important;
        }

        .products .current-price {
            color: #dc3545 !important;
        }

        .products .product-category {
            color: #dc3545 !important;
        }

        /* Hero buttons - one black, one red */
        .hero-buttons .btn-primary {
            background: #111 !important;
            color: white !important;
            border: 2px solid #111 !important;
            box-shadow: 0 8px 25px rgba(17, 17, 17, 0.3) !important;
        }

        .hero-buttons .btn-primary:hover {
            background: #333 !important;
            border-color: #333 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 35px rgba(17, 17, 17, 0.4) !important;
        }

        .hero-buttons .btn-secondary {
            background: #dc3545 !important;
            color: white !important;
            border: 2px solid #dc3545 !important;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3) !important;
        }

        .hero-buttons .btn-secondary:hover {
            background: #c82333 !important;
            border-color: #c82333 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 35px rgba(220, 53, 69, 0.4) !important;
        }




        .collections {
    padding: 80px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.collections-container {
    max-width: 1200px;
    margin: 0 auto;
}

.collections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 50px;
}

.collection-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    position: relative;
    cursor: pointer;
}

.collection-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    border-color: #dc3545;
}

.collection-image {
    height: 220px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.collection-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.collection-card:hover .collection-image img {
    transform: scale(1.1);
}

.collection-image .fallback-icon {
    font-size: 4rem;
    color: #dc3545;
    opacity: 0.6;
}

.collection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(220, 53, 69, 0.8) 0%, rgba(200, 35, 51, 0.9) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
}

.collection-card:hover .collection-overlay {
    opacity: 1;
}

.collection-info {
    padding: 25px;
    text-align: center;
}

.collection-info h3 {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 10px;
    color: #2c3e50;
    text-transform: capitalize;
}

.collection-count {
    color: #dc3545;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.collection-description {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 20px;
}

.collection-cta {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.collection-cta:hover {
    background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
    color: white;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .collections {
        padding: 60px 20px;
    }
    
    .collections-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .collection-card {
        border-radius: 12px;
    }
    
    .collection-image {
        height: 140px;
    }
    
    .collection-info {
        padding: 15px;
    }
    
    .collection-info h3 {
        font-size: 1.1rem;
        margin-bottom: 8px;
    }
    
    .collection-count {
        font-size: 0.85rem;
        margin-bottom: 10px;
    }
    
    .collection-description {
        font-size: 0.8rem;
        margin-bottom: 15px;
        display: none; /* Hide on mobile to save space */
    }
    
    .collection-cta {
        padding: 8px 16px;
        font-size: 0.8rem;
        border-radius: 20px;
    }
}

@media (max-width: 480px) {
    .collections-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .collection-image {
        height: 180px;
    }
    
    .collection-description {
        display: block;
    }
}
    </style>
</head>
<body>
    <!-- ===== OVERLAY ===== -->
    <div class="overlay" id="overlay" onclick="closeCart()"></div>

    <?php include 'header.php'; ?>

    <!-- ===== SUCCESS ALERT ===== -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

<!-- ===== HERO SECTION ===== -->
<div class="hero-banner">
    <div class="hero-content">
        <h1><?php echo $texts['home']['hero']['title']; ?></h1>
        <p><?php echo $texts['home']['hero']['subtitle']; ?></p>
        <div class="hero-buttons">
            <a href="produkty.php" class="btn-primary">
                <?php echo $texts['home']['hero']['button_primary']; ?>
            </a>
            <a href="kontakt.php" class="btn-secondary">
                <?php echo $texts['home']['hero']['button_secondary']; ?>
            </a>
        </div>
    </div>
</div>




  <section class="collections" id="collections">
  <div class="collections-container">
    <h2 class="section-title"><?php echo $texts['home']['collections']['title']; ?></h2>
    <p class="section-subtitle">
      <?php 
        echo str_replace("{count}", count($categories), $texts['home']['collections']['subtitle']); 
      ?>
    </p>

    <div class="collections-grid">
      <?php foreach ($categories as $category): ?>
        <?php 
        $categoryImage = getCategoryImage($db, $category);
        $productCount = getCategoryProductCount($db, $category);

        // Choose pluralization
        if ($productCount == 1) {
            $productLabel = $texts['home']['collections']['product_single'];
        } elseif ($productCount < 5) {
            $productLabel = $texts['home']['collections']['product_few'];
        } else {
            $productLabel = $texts['home']['collections']['product_many'];
        }

        // Category description
        $descriptions = $texts['home']['collections']['custom_descriptions'];
        $categoryKey = strtolower($category);
        $description = $descriptions[$categoryKey] ?? $texts['home']['collections']['default_description'];

        // Category URL (now relative)
        $categoryUrl = getCategoryUrl($category);
        ?>
        
        <div class="collection-card" onclick="window.location.href='<?php echo $categoryUrl; ?>'">
          <div class="collection-image">
            <?php if ($categoryImage): ?>
              <img src="<?php echo htmlspecialchars($categoryImage); ?>" 
                   alt="<?php echo htmlspecialchars($category); ?>">
            <?php else: ?>
              <i class="fas fa-th-large fallback-icon"></i>
            <?php endif; ?>

            <div class="collection-overlay">
              <span><i class="fas fa-arrow-right"></i> <?php echo $texts['home']['collections']['view_collection']; ?></span>
            </div>
          </div>

          <div class="collection-info">
            <h3><?php echo htmlspecialchars($category); ?></h3>
            
            <div class="collection-count">
              <i class="fas fa-box"></i>
              <?php echo $productCount . " " . $productLabel; ?>
            </div>
            
            <p class="collection-description"><?php echo $description; ?></p>
            
            <a href="<?php echo $categoryUrl; ?>" class="collection-cta">
              <i class="fas fa-shopping-bag"></i>
              <?php echo $texts['home']['collections']['cta']; ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


    <!-- ===== PRODUCTS SECTION ===== -->
   <section class="products" id="products">
  <div class="products-container">
    <h2 class="section-title"><?php echo $texts['home']['products']['title']; ?></h2>
    <p class="section-subtitle">
      <?php if (count($allProducts) > 0): ?>
        <?php echo str_replace("{count}", count($allProducts), $texts['home']['products']['subtitle_with_count']); ?>
      <?php else: ?>
        <?php echo $texts['home']['products']['subtitle_empty']; ?>
      <?php endif; ?>
    </p>
    
    <?php if (empty($products)): ?>
      <div style="text-align: center; padding: 60px 20px;">
        <i class="fas fa-box-open" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
        <h3 style="color: #666; margin-bottom: 10px;"><?php echo $texts['home']['products']['empty_title']; ?></h3>
        <p style="color: #999;"><?php echo $texts['home']['products']['empty_message']; ?></p>
      </div>
    <?php else: ?>
      <div class="products-grid">
        <?php foreach ($products as $product): ?>
          <div class="product-card">
            <div class="product-image" onclick="window.location.href='<?php echo Database::getProductUrl($product); ?>'">
              <?php if ($product['image_url']): ?>
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['current_name']); ?>">
              <?php else: ?>
                <i class="fas fa-box fallback-icon"></i>
              <?php endif; ?>
            </div>
            
            <div class="product-info">
              <?php if (!empty($product['category'])): ?>
                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
              <?php endif; ?>
              
              <h4><?php echo htmlspecialchars($product['current_name']); ?></h4>
              
              <div class="product-price-container">
                <?php if (isset($product['old_price']) && $product['old_price'] > 0 && $product['old_price'] > $product['price']): ?>
                  <div class="price-with-discount">
                    <span class="old-price"><?php echo number_format($product['old_price'], 2); ?> €</span>
                    <span class="current-price"><?php echo number_format($product['price'], 2); ?> €</span>
                    <span class="discount-badge">
                      -<?php echo number_format($product['old_price'] - $product['price'], 2); ?> €
                    </span>
                  </div>
                <?php else: ?>
                  <div class="product-price"><?php echo number_format($product['price'], 2); ?> €</div>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($product['current_description'])): ?>
                <p>
                  <?php echo htmlspecialchars(substr(strip_tags($product['current_description']), 0, 100)); ?>
                  <?php echo strlen(strip_tags($product['current_description'])) > 100 ? '...' : ''; ?>
                </p>
              <?php endif; ?>
              
              <div class="product-actions">
                <a href="<?php echo Database::getProductUrl($product); ?>" class="btn btn-outline btn-small">
                  <i class="fas fa-eye"></i>
                  <?php echo $texts['home']['products']['view_product']; ?>
                </a>
              </div>
              
              <?php if ($product['stock_quantity'] > 0): ?>
                <small style="color: #28a745; margin-top: 10px; display: block;">
                  <i class="fas fa-check-circle"></i> 
                  <?php echo str_replace("{count}", $product['stock_quantity'], $texts['home']['products']['in_stock']); ?>
                </small>
              <?php else: ?>
                <small style="color: #dc3545; margin-top: 10px; display: block;">
                  <i class="fas fa-exclamation-circle"></i> 
                  <?php echo $texts['home']['products']['out_of_stock']; ?>
                </small>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <?php if (count($allProducts) > 6): ?>
        <div style="text-align: center; margin-top: 30px;">
          <a href="produkty.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 14px; background: #28a745; border-color: #28a745;">
            <i class="fas fa-th-large"></i>
            <?php echo str_replace("{count}", count($allProducts), $texts['home']['products']['see_all']); ?>
          </a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>




    <!-- ===== WHY CHOOSE US SECTION ===== -->
  <section class="why-choose-us">
  <div class="container">
    <div class="section-header">
      <h2><?php echo $texts['home']['why_choose_us']['title']; ?></h2>
      <p><?php echo $texts['home']['why_choose_us']['subtitle']; ?></p>
    </div>
    
    <div class="features-grid">
      <?php foreach ($texts['home']['why_choose_us']['features'] as $feature): ?>
        <div class="feature-card">
          <div class="feature-icon">
            <i class="<?php echo $feature['icon']; ?>"></i>
          </div>
          <h3><?php echo $feature['title']; ?></h3>
          <p><?php echo $feature['description']; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


    <!-- ===== TESTIMONIALS SECTION ===== -->
   <section class="testimonials">
  <div class="container">
    <div class="section-header">
      <h2><?php echo $texts['home']['testimonials']['title']; ?></h2>
      <p><?php echo $texts['home']['testimonials']['subtitle']; ?></p>
    </div>
    
    <div class="testimonials-grid">
      <?php foreach ($texts['home']['testimonials']['items'] as $testimonial): ?>
        <div class="testimonial-card">
          <div class="testimonial-content">
            <div class="stars">
              <?php for ($i = 0; $i < $testimonial['stars']; $i++): ?>
                <i class="fas fa-star"></i>
              <?php endfor; ?>
            </div>
            <p>"<?php echo $testimonial['text']; ?>"</p>
            <div class="testimonial-author">
              <strong><?php echo $testimonial['author']; ?></strong>
              <span><?php echo $testimonial['location']; ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>




    <style>
        /* Why Choose Us Section */
        .why-choose-us {
            padding: 80px 20px;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #2c3e50;
        }

        .section-header p {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc3545, #ff8e53);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Testimonials Section */
        .testimonials {
            padding: 80px 20px;
            background: white;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            position: relative;
        }

        .testimonial-content {
            text-align: center;
        }

        .stars {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .testimonial-card p {
            font-style: italic;
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .testimonial-author strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .testimonial-author span {
            color: #666;
            font-size: 0.9rem;
        }



        /* Responsive */
        @media (max-width: 768px) {
            .section-header h2 {
                font-size: 2rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
            

        }
    </style>

    <!-- ===== CART SIDEBAR ===== -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart"></i> Twój koszyk</h3>
            <button class="cart-close" onclick="closeCart()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="cart-content">
            <?php if (empty($cartItems)): ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                    <p style="color: #666;">Je winkelwagen is leeg</p>
                </div>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-box" style="color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cart-item-info" style="flex: 1;">
                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            <div class="cart-item-price"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €</div>
                            
                            <div class="cart-item-controls">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="99" style="width: 50px; padding: 4px; text-align: center;"
                                           onchange="this.form.submit()">
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_from_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="cart-total">
                    <div class="cart-total-price">
                        Totaal: <?php echo number_format($cartTotal, 2); ?> €
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-credit-card"></i>
                            Afrekenen via Shopify
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        // Smooth scroll to products
        document.querySelector('a[href="#products"]').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('products').scrollIntoView({
                behavior: 'smooth'
            });
        });

        // Auto-hide success messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 3000);
    </script>

    <?php include 'footer.php'; ?>
</body>
</html> 