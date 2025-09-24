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

// // Handle cart actions
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
//     $db = Database::getInstance();

//     switch ($_POST['action']) {
//         case 'add_to_cart':
//             $productId = intval($_POST['product_id']);
//             $quantity = intval($_POST['quantity']);
//             $variantIndex = isset($_POST['variant_index']) && $_POST['variant_index'] !== '' ? intval($_POST['variant_index']) : null;
//             $variantData = null;
//             if ($variantIndex !== null && isset($product['variants'][$variantIndex])) {
//                 $variantData = $product['variants'][$variantIndex];
//             }
//             if ($productId > 0 && $quantity > 0) {
//                 $db->addToCart($_SESSION['cart_session_id'], $productId, $quantity, $variantIndex, $variantData);
//                  $success_message = $texts['product']['success_message'];
//             }
//             break;

//         case 'update_cart':
//             $productId = intval($_POST['product_id']);
//             $quantity = intval($_POST['quantity']);
//             $db->updateCartQuantity($_SESSION['cart_session_id'], $productId, $quantity);
//             break;

//         case 'remove_from_cart':
//             $productId = intval($_POST['product_id']);
//             $db->removeFromCart($_SESSION['cart_session_id'], $productId);
//             $success_message = "Produkt usunięty z koszyka!";
//             break;

//         case 'checkout':
//             $cartItems = $db->getCartItems($_SESSION['cart_session_id']);
//             if (!empty($cartItems)) {
//                 header("Location: checkout_redirect.php");
//                 exit();
//             }
//             break;
//     }
// }

// Get database instance
$db = Database::getInstance();

// Get product from URL (support both slug and ID)
$product = null;

if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    // Probeer eerst product op te halen via slug
    $product = $db->getProductBySlug($_GET['slug']);
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    // Fallback naar ID voor backwards compatibility
    $productId = intval($_GET['id']);
    if ($productId > 0) {
        $product = $db->getProduct($productId);
        // Als gevonden via ID, redirect naar slug-URL voor SEO
        if ($product && isset($product['slug'])) {
            $redirectUrl = 'product.php?slug=' . urlencode($product['slug']);
            header('Location: ' . $redirectUrl, true, 301); // 301 Permanent Redirect
            exit();
        }
    }
}

if (!$product) {
    header('Location: index.php');
    exit();
}

// Set productId voor formulier gebruik
$productId = $product['id'];

// Initialize cart item count properly
$cartItemCount = $db->getCartItemCount($_SESSION['cart_session_id']);
$cartItems = $db->getCartItems($_SESSION['cart_session_id']);
$cartTotal = $db->getCartTotal($_SESSION['cart_session_id']);

// Get related products (exclude current product)
$relatedProducts = array_filter($db->getAllProducts(true), function ($prod) use ($product) {
    return $prod['id'] != $product['id'];
});
// Shuffle and take 4-5 products (or all if less available)
shuffle($relatedProducts);
$maxProducts = min(4, count($relatedProducts)); // Max 4 or all available
$relatedProducts = array_slice($relatedProducts, 0, $maxProducts);

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['nl', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Get active language
$lang = $_SESSION['lang'] ?? 'nl';

// Use translations only
if (!empty($product['translations'][$lang])) {
    $translation = $product['translations'][$lang];
    $currentName = $translation['name'] ?? '';
    $currentDescription = $translation['description'] ?? '';
    $currentVariants = $translation['variants'] ?? [];
} else {
    // If no translations exist at all
    $currentName = '';
    $currentDescription = '';
    $currentVariants = [];
}


// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = Database::getInstance();

    switch ($_POST['action']) {
        case 'add_to_cart':
            $productId = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $variantIndex = isset($_POST['variant_index']) && $_POST['variant_index'] !== '' ? intval($_POST['variant_index']) : null;
            $variantData = null;
            if ($variantIndex !== null && isset($product['variants'][$variantIndex])) {
                $variantData = $product['variants'][$variantIndex];
            }
            if ($productId > 0 && $quantity > 0) {
                $db->addToCart($_SESSION['cart_session_id'], $productId, $quantity, $variantIndex, $variantData);
                  // Hardcoded translations
                if ($lang === 'fr') {
                    $success_message = "Produit ajouté au panier!";
                } elseif ($lang === 'nl') {
                    $success_message = "Product toegevoegd aan winkelwagen!";
                } else {
                    $success_message = "Produkt dodany do koszyka!";
                }
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
              // Hardcoded translations
            if ($lang === 'fr') {
                $success_message = "Produit supprimé du panier!";
            } elseif ($lang === 'nl') {
                $success_message = "Product verwijderd uit winkelwagen!";
            } else {
                $success_message = "Produkt usunięty z koszyka!";
            }
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

$shop_name = $db->getSetting('shop_name') ?: 'TechShop';
?><!DOCTYPE html>
<html lang="pl">

<head>


    <!-- Facebook Pixel Base Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
            n.queue = []; t = b.createElement(e); t.async = !0;
            t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', '698814149641552');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=698814149641552&ev=PageView&noscript=1" />
    </noscript>
    <!-- End Facebook Pixel Base Code -->










    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentName); ?> - <?php echo htmlspecialchars($shop_name); ?></title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #fafafa;
            margin: 0;
            color: #333;
            width: 100%;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            /* Herstel de max-width voor desktop */
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }

        .product-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-height: calc(100vh - 60px);
            width: 100%;
            /* Zorgt ervoor dat sectie niet breder wordt dan scherm */
        }

        .product-gallery {
            background: #fafafa;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .main-image {
            width: 100%;
            max-width: 600px;
            height: 450px;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            padding: 20px;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-width: 100%;
            max-height: 100%;
        }

        .image-thumbnails {
            display: flex;
            gap: 12px;
            max-width: 100%;
            /* Zorg dat het de container niet overschrijdt */
            flex-wrap: nowrap;
            justify-content: flex-start;
            margin-top: 15px;
            overflow-x: auto;
            padding: 4px 15px 10px 15px;
            /* Padding voor mobiel */
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #ccc #f1f1f1;
        }

        /* Aangepaste scrollbar voor WebKit (Chrome, Safari) */
        .image-thumbnails::-webkit-scrollbar {
            height: 6px;
        }

        .image-thumbnails::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .image-thumbnails::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .image-thumbnails::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        .thumbnail {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            overflow: visible;
            /* Zorgt dat schaduw niet wordt afgesneden */
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            opacity: 0.6;
            transform: scale(0.9);
            flex-shrink: 0;
            /* Voorkomt dat afbeeldingen krimpen */
        }

        .thumbnail:hover {
            opacity: 1;
            transform: scale(1);
            border-color: rgba(0, 112, 243, 0.5);
        }

        .thumbnail.active {
            opacity: 1;
            transform: scale(1.05);
            border-color: #0070f3;
            box-shadow: 0 5px 15px rgba(0, 112, 243, 0.2);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            /* Iets kleinere radius dan de container */
        }

        @media (max-width: 600px) {
            .image-thumbnails {
                gap: 10px;
                padding: 0 15px 15px 15px;
                margin-bottom: 0;
            }

            .thumbnail {
                width: 65px;
                height: 65px;
                border-radius: 10px;
            }

            .thumbnail img {
                border-radius: 8px;
            }

            .main-image {
                max-width: 100%;
                width: 100%;
                height: 75vw;
                min-height: 280px;
                max-height: 450px;
                border-radius: 0;
                padding: 0;
                margin-bottom: 15px;
                box-shadow: none;
            }

            .main-image img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }

            .product-info {
                padding: 20px 15px;
            }
        }

        .no-image {
            color: #999;
            font-size: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .no-image i {
            font-size: 40px;
            opacity: 0.3;
        }

        .product-info {
            padding: 30px;
            background: white;
            overflow-y: auto;
        }

        .product-category {
            font-size: 12px;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            background: #0070f3;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .product-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .product-price {
            font-size: 1.6rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 12px;
        }

        .price-comparison {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .old-price {
            font-size: 20px;
            color: #999;
            text-decoration: line-through;
            font-weight: 400;
        }

        .current-price {
            font-size: 28px;
            color: #28a745;
            font-weight: 700;
        }

        .discount-badge {
            background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
            color: white;
            padding: 6px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(255, 71, 87, 0.3);
        }

        /* Pulse Animation voor MEGA SALE badge */
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(255, 71, 87, 0.5);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
            }
        }

        /* Live Viewers Widget Animations */
        @keyframes blink {

            0%,
            50% {
                opacity: 1;
            }

            51%,
            100% {
                opacity: 0.3;
            }
        }

        /* Cart Sidebar Styles */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 20px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1001;
            display: flex;
            flex-direction: column;
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
            background: #f8f9fa;
        }

        .cart-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background 0.2s ease;
        }

        .cart-close:hover {
            background: #e9ecef;
        }

        .cart-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .cart-item:last-child {
            margin-bottom: 0;
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-info h5 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .cart-item-price {
            color: #28a745;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }

        .cart-total-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin-bottom: 15px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .checkout-benefits {
            margin-top: 20px;
            padding: 20px 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            font-size: 13px;
            color: #495057;
            font-weight: 500;
            position: relative;
            transition: all 0.2s ease;
        }

        .benefit-item:not(:last-child) {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .benefit-item:hover {
            color: #28a745;
            transform: translateX(3px);
        }

        .benefit-item i {
            width: 20px;
            height: 20px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .benefit-item:hover i {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .benefit-item span {
            flex: 1;
            line-height: 1.4;
        }

        /* Specific icon colors */
        .benefit-item:nth-child(1) i {
            color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }

        .benefit-item:nth-child(2) i {
            color: #17a2b8;
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        }

        .benefit-item:nth-child(3) i {
            color: #ffc107;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }

        .benefit-item:nth-child(4) i {
            color: #6f42c1;
            background: linear-gradient(135deg, #e2d9f3 0%, #d1c4e9 100%);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #dc3545;
            color: white;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.5);
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .cart-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 480px) {
            .cart-sidebar {
                width: 100%;
                right: -100vw;
            }
        }

        .product-description-section {
            margin-bottom: 20px;
        }

        .description-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .product-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .product-description img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 15px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: block;
        }

        .product-description p {
            margin-bottom: 15px;
        }

        .product-description ul,
        .product-description ol {
            margin: 15px 0;
            padding-left: 20px;
        }

        .product-description h1,
        .product-description h2,
        .product-description h3 {
            margin: 20px 0 10px 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .product-description strong {
            font-weight: 600;
            color: #333;
        }

        .product-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .details-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #000;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            pointer-events: none;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 12px;
        }

        .in-stock {
            background: #d4edda;
            color: #155724;
        }

        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }

        .action-section {
            background: white;
            padding: 30px;
            border-top: 1px solid #e0e0e0;
            position: relative;
            z-index: 1;
            margin-top: 20px;
        }

        .add-to-cart-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            width: fit-content;
            min-width: 120px;
        }

        .quantity-btn {
            background: #f8f9fa;
            border: none;
            padding: 10px 12px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            transition: all 0.2s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background: #e9ecef;
            color: #333;
        }

        .quantity-input {
            border: none;
            padding: 10px 12px;
            text-align: center;
            font-size: 14px;
            width: 60px;
            height: 40px;
            background: white;
            color: #333;
            outline: none;
        }

        .add-to-cart-btn {
            padding: 16px 24px;
            font-size: 16px;
            width: 100%;
            border-radius: 10px;
            background: #28a745;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3), 0 2px 8px rgba(0, 0, 0, 0.2);
            border: none;
            color: white;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            min-height: 55px;
            position: relative;
            z-index: 10;
            margin-bottom: 20px;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4), 0 4px 12px rgba(0, 0, 0, 0.3);
            background: #218838;
        }

        .add-to-cart-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* Trust Features Styling */
        .trust-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fc;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
        }

        .trust-icon {
            width: 40px;
            height: 40px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
        }

        .trust-icon i {
            color: white;
            font-size: 16px;
        }

        .trust-text {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            letter-spacing: 0.2px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }

        .feature i {
            font-size: 16px;
            color: #666;
            width: 20px;
            text-align: center;
        }

        .feature-text {
            font-size: 12px;
            color: #666;
            font-weight: 400;
        }

        @media (max-width: 1024px) {
            .product-section {
                grid-template-columns: 1fr;
            }

            .product-gallery {
                min-height: 50vh;
                padding: 20px;
            }

            .main-image {
                max-width: 100%;
                height: 350px;
                padding: 15px;
            }

            .product-info,
            .action-section {
                padding: 40px 30px;
            }

            .product-title {
                font-size: 2.5rem;
            }

            .product-price {
                font-size: 3rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .add-to-cart-form {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
                margin-bottom: 30px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .search-bar {
                display: none;
            }

            .header-actions {
                gap: 10px;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .product-info,
            .action-section {
                padding: 0;
            }

            .product-info {
                padding-bottom: 0;
                position: relative;
                z-index: 5;
                overflow: visible;
            }

            .product-title {
                font-size: 1.8rem;
            }

            .product-price {
                font-size: 2rem;
            }

            .quantity-input {
                font-size: 12px;
                padding: 6px 10px;
                width: 50px;
            }

            .quantity-btn {
                width: 35px;
                height: 35px;
                font-size: 14px;
                padding: 0;
            }

            .add-to-cart-btn {
                padding: 16px 24px;
                font-size: 16px;
                width: 100%;
                border-radius: 10px;
                background: #28a745;
                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3), 0 2px 8px rgba(0, 0, 0, 0.2);
                border: none;
                color: white;
                font-weight: 600;
                text-transform: none;
                letter-spacing: 0.3px;
                transition: all 0.3s ease;
                min-height: 55px;
                position: relative;
                z-index: 10;
                margin-bottom: 20px;
            }

            .add-to-cart-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4), 0 4px 12px rgba(0, 0, 0, 0.3);
                background: #218838;
            }

            .add-to-cart-btn:active {
                transform: translateY(0);
                box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
            }

            /* Mobile Trust Features */
            .trust-features {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-top: 20px;
                padding: 0;
                margin-bottom: 40px;
                position: relative;
                z-index: 1;
            }

            .trust-item {
                justify-content: center;
                text-align: center;
                flex-direction: column;
                gap: 8px;
            }

            .trust-icon {
                width: 35px;
                height: 35px;
            }

            .trust-icon i {
                font-size: 14px;
            }

            .trust-text {
                font-size: 12px;
            }

            .action-section {
                margin-top: 50px;
                position: relative;
                z-index: 20;
                clear: both;
                background: white !important;
                border-top: 2px solid #e0e0e0 !important;
            }

            .product-section {
                display: block;
                max-height: none;
            }

            .product-info {
                min-height: auto;
            }

            .product-gallery {
                padding: 0;
            }

            .main-image {
                height: 280px;
                padding: 0;
            }

            .main-image img {
                border-radius: 8px;
            }

            .image-thumbnails {
                gap: 8px;
                padding: 0;
                margin-bottom: 10px;
            }

            .thumbnail {
                width: 60px;
                height: 60px;
                border-radius: 8px;
            }
        }

        /* ===== GERELATEERDE PRODUCTEN STYLING (SAME AS INDEX) ===== */
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
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            background: #0070f3;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
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
            color: #28a745;
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
            gap: 10px;
            align-items: center;
        }

        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            text-align: center;
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
            background: #dc3545;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 112, 243, 0.3);
            background: #0051cc;
        }

        .btn-small {
            padding: 10px 20px;
            font-size: 14px;
        }

        /* ===== PRIJS STYLING VOOR GERELATEERDE PRODUCTEN ===== */
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
            color: #28a745;
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
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
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

        /* ===== FOOTER STYLING ===== */
        .site-footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 50px 0 20px;
            margin-top: 60px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h4 {
            color: #ecf0f1;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
        }

        .footer-column h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 30px;
            height: 3px;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 3px;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .footer-logo i {
            color: #28a745;
            font-size: 2rem;
        }

        .footer-description {
            color: #bdc3c7;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-link:hover {
            background: #28a745;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            padding: 5px 0;
        }

        .footer-links a:hover {
            color: #28a745;
            padding-left: 10px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
            color: #bdc3c7;
            line-height: 1.5;
        }

        .contact-item i {
            color: #28a745;
            font-size: 1.1rem;
            margin-top: 2px;
            min-width: 18px;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 25px;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-bottom-content p {
            margin: 0;
            color: #95a5a6;
            font-size: 14px;
        }

        .payment-methods {
            display: flex;
            gap: 15px;
        }

        .payment-methods i {
            font-size: 2rem;
            color: #bdc3c7;
            transition: all 0.3s ease;
        }

        .payment-methods i:hover {
            color: #28a745;
            transform: scale(1.1);
        }

        .blik-payment {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }

        .blik-payment:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }

        /* ===== MOBILE RESPONSIVENESS ===== */
        @media (max-width: 768px) {
            .section-title {
                font-size: 1.8rem;
                margin-bottom: 30px;
            }

            .related-products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }

            .related-product-image {
                height: 180px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .payment-methods {
                justify-content: center;
            }
        }

        .trustpilot-section {
            width: 100%;
            background: #f7f8fc;
            padding: 0.4rem 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .trustpilot-slider {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 30px;
            position: relative;
            overflow: hidden;
        }

        .trustpilot-slides {
            display: flex;
            width: 300%;
            height: 100%;
            animation: slide 9s infinite;
        }

        @keyframes slide {

            0%,
            30% {
                transform: translateX(0);
            }

            33.33%,
            63.33% {
                transform: translateX(-33.33%);
            }

            66.66%,
            96.66% {
                transform: translateX(-66.66%);
            }

            100% {
                transform: translateX(0);
            }
        }

        .trustpilot-slide {
            width: 33.33%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 100%;
        }

        .trustpilot-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            white-space: nowrap;
        }

        .trustpilot-stars-logo {
            display: flex;
            align-items: center;
        }

        .trustpilot-stars-logo img {
            height: 20px;
            width: auto;
        }

        .trustpilot-logo-container {
            display: flex;
            align-items: center;
        }

        .trustpilot-logo-container img {
            height: 18px;
            width: auto;
        }

        .green-check {
            width: 20px;
            height: 20px;
            fill: #00b67a;
            flex-shrink: 0;
            stroke: #00b67a;
            stroke-width: 1px;
            font-weight: bold;
        }

        .slide-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .trustpilot-slider {
                height: 28px;
            }

            .trustpilot-text,
            .slide-text {
                font-size: 1rem;
            }

            .trustpilot-stars-logo img {
                height: 18px;
            }

            .trustpilot-logo-container img {
                height: 16px;
            }

            .green-check {
                width: 18px;
                height: 18px;
            }
        }

        @media (max-width: 480px) {
            .trustpilot-slider {
                padding: 0 1rem;
            }

            .trustpilot-slide {
                gap: 0.3rem;
            }

            .trustpilot-text,
            .slide-text {
                font-size: 0.9rem;
            }

            .trustpilot-stars-logo img {
                height: 16px;
            }

            .trustpilot-logo-container img {
                height: 14px;
            }

            .green-check {
                width: 16px;
                height: 16px;
            }
        }

        .announcement-bar {
            background: #ff6b00;
            color: white;
            padding: 8px 0;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1000;
        }

        .announcement-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .timer {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .timer span {
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .announcement-bar {
                font-size: 12px;
            }

            .announcement-content {
                flex-wrap: wrap;
                gap: 8px;
            }
        }

        @media (max-width: 992px) {
            .product-section {
                grid-template-columns: 1fr;
                max-height: none;
            }

            .product-gallery,
            .product-info {
                max-height: none;
                overflow-y: visible;
                padding: 15px;
            }
        }

        @media (max-width: 600px) {
            .product-section {
                padding-top: 0;
            }

            .product-gallery {
                padding: 0;
            }

            .product-info {
                padding: 20px 15px;
                /* Meer verticale padding */
            }

            .main-image {
                max-width: 100vw;
                width: 100vw;
                height: 75vw;
                /* Flink groter gemaakt */
                min-height: 280px;
                /* Min-height aangepast */
                max-height: 450px;
                /* Max-height aangepast */
                border-radius: 0;
                padding: 0;
                margin-bottom: 15px;
                /* Meer ruimte onder de foto */
                box-shadow: none;
                /* Schaduw weghalen op mobiel voor strakke look */
            }

            .main-image img {
                max-width: 100%;
                max-height: 100%;
                object-fit: contain;
            }

            .image-thumbnails {
                gap: 10px;
                padding: 0 15px 15px 15px;
                /* Padding aangepast */
                margin-bottom: 0;
            }

            .thumbnail {
                width: 65px;
                /* Iets groter */
                height: 65px;
                /* Iets groter */
                border-radius: 10px;
            }

            .thumbnail img {
                border-radius: 8px;
            }
        }

        /* Trustpilot Product Reviews Styling */
        .trustpilot-product-section {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-top: 275px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .trustpilot-product-slider {
            position: relative;
            height: 90px;
            overflow: hidden;
        }

        .trustpilot-product-slides {
            display: flex;
            width: 400%;
            height: 100%;
            animation: productSlide 16s infinite ease-in-out;
        }

        @keyframes productSlide {

            0%,
            22% {
                transform: translateX(0);
            }

            25%,
            47% {
                transform: translateX(-25%);
            }

            50%,
            72% {
                transform: translateX(-50%);
            }

            75%,
            97% {
                transform: translateX(-75%);
            }

            100% {
                transform: translateX(0);
            }
        }

        .trustpilot-product-slide {
            width: 25%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
        }

        .trustpilot-product-content {
            text-align: center;
            width: 100%;
        }

        .trustpilot-product-content .trustpilot-stars {
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            gap: 2px;
        }

        .trustpilot-product-content .trustpilot-text {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            line-height: 1.4;
            font-style: italic;
        }

        .trustpilot-product-content .trustpilot-author {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .trustpilot-product-section .trustpilot-logo {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* Hover effect to pause animation */
        .trustpilot-product-section:hover .trustpilot-product-slides {
            animation-play-state: paused;
        }

        /* Mobile responsive styling for product reviews */
        @media (max-width: 768px) {
            .trustpilot-product-section {
                padding: 15px;
                margin: 20px 15px;
            }

            .trustpilot-product-slider {
                height: 80px;
            }

            .trustpilot-product-content .trustpilot-text {
                font-size: 13px;
            }

            .trustpilot-product-content .trustpilot-author {
                font-size: 11px;
            }

            .trustpilot-product-content .trustpilot-stars img {
                height: 14px;
            }
        }

        @media (max-width: 480px) {
            .trustpilot-product-section {
                padding: 12px;
                margin: 15px 15px;
            }

            .trustpilot-product-slider {
                height: 75px;
            }

            .trustpilot-product-content .trustpilot-text {
                font-size: 12px;
            }

            .trustpilot-product-content .trustpilot-author {
                font-size: 10px;
            }

            .trustpilot-product-section .trustpilot-logo img {
                height: 16px;
            }
        }

        /* Trustpilot Cart Reviews Styling */
        .trustpilot-cart-section {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .trustpilot-cart-slider {
            position: relative;
            height: 80px;
            overflow: hidden;
        }

        .trustpilot-cart-slides {
            display: flex;
            width: 300%;
            height: 100%;
            animation: cartSlide 12s infinite ease-in-out;
        }

        @keyframes cartSlide {

            0%,
            28% {
                transform: translateX(0);
            }

            33.33%,
            61.33% {
                transform: translateX(-33.33%);
            }

            66.66%,
            94.66% {
                transform: translateX(-66.66%);
            }

            100% {
                transform: translateX(0);
            }
        }

        .trustpilot-cart-slide {
            width: 33.33%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
        }

        .trustpilot-cart-content {
            text-align: center;
            width: 100%;
        }

        .trustpilot-stars {
            margin-bottom: 8px;
            display: flex;
            justify-content: center;
            gap: 2px;
        }

        .trustpilot-stars i {
            font-size: 14px;
        }

        .trustpilot-cart-content .trustpilot-text {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            line-height: 1.3;
            font-style: italic;
        }

        .trustpilot-cart-content .trustpilot-author {
            font-size: 11px;
            color: #666;
            font-weight: 500;
        }

        .trustpilot-logo {
            text-align: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
        }

        .trustpilot-logo img {
            height: 14px;
            width: auto;
            opacity: 0.7;
        }

        /* Hover effect to pause animation */
        .trustpilot-cart-section:hover .trustpilot-cart-slides {
            animation-play-state: paused;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .trustpilot-cart-section {
                padding: 12px;
            }

            .trustpilot-cart-slider {
                height: 70px;
            }

            .trustpilot-cart-content .trustpilot-text {
                font-size: 12px;
            }

            .trustpilot-cart-content .trustpilot-author {
                font-size: 10px;
            }

            .trustpilot-stars i {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .trustpilot-cart-section {
                padding: 10px;
            }

            .trustpilot-cart-slider {
                height: 65px;
            }

            .trustpilot-cart-content .trustpilot-text {
                font-size: 11px;
            }

            .trustpilot-cart-content .trustpilot-author {
                font-size: 9px;
            }

            .trustpilot-stars i {
                font-size: 11px;
            }

            .trustpilot-logo img {
                height: 12px;
            }
        }

        input:focus {
            border-color: #dc3545;
            outline: none;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
        }



        .product-variants {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .variant-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .variant-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .variant-option {
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .variant-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .variant-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            border: 3px solid transparent;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .variant-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .variant-option input[type="radio"]:checked+.variant-image {
            border-color: #dc3545;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .variant-option:hover .variant-image {
            transform: scale(1.02);
            border-color: rgba(220, 53, 69, 0.5);
        }

        .variant-name {
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            color: #666;
            margin-top: 5px;
            text-transform: capitalize;
        }

        .variant-option input[type="radio"]:checked+.variant-image+.variant-name {
            color: #dc3545;
            font-weight: 600;
        }

        /* Selected indicator */
        .variant-image::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            width: 20px;
            height: 20px;
            background: #dc3545;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .variant-option input[type="radio"]:checked+.variant-image::after {
            transform: translate(-50%, -50%) scale(1);
        }

        .variant-image::before {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            color: white;
            font-weight: bold;
            font-size: 12px;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .variant-option input[type="radio"]:checked+.variant-image::before {
            transform: translate(-50%, -50%) scale(1);
        }

        @media (max-width: 768px) {
            .variant-options {
                gap: 8px;
            }

            .variant-image {
                width: 50px;
                height: 50px;
            }

            .variant-name {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <!-- SUCCESS ALERT -->
    <?php if (isset($success_message)): ?>
        <div
            style="background: #d4edda; color: #155724; padding: 15px; margin: 20px auto; max-width: 1200px; border-radius: 6px; border: 1px solid #c3e6cb; display: flex; align-items: center; gap: 10px; position: relative; z-index: 999;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="product-section">
            <div class="product-gallery">
                <div class="main-image" id="mainImageContainer" style="position:relative;">
                    <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                        <button id="prevImageBtn"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.7);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;z-index:2;cursor:pointer;font-size:20px;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    <?php endif; ?>

                    <?php if (!empty($product['images']) && count($product['images']) > 0): ?>
                        <img id="mainImage" src="<?php echo htmlspecialchars($product['images'][0]); ?>"
                            alt="<?php echo htmlspecialchars($currentName); ?>" loading="lazy">
                    <?php elseif (!empty($product['image_url'])): ?>
                        <img id="mainImage" src="<?php echo htmlspecialchars($product['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($currentName); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                            <span>Brak zdjęcia produktu</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                        <button id="nextImageBtn"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.7);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;z-index:2;cursor:pointer;font-size:20px;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="image-thumbnails">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                data-index="<?php echo $index; ?>">
                                <img src="<?php echo htmlspecialchars($image); ?>"
                                    alt="<?php echo htmlspecialchars($currentName); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>


            <div class="product-info">
                <div id="trustpilot-widget"
                    style="display: inline-flex; align-items: center; background-color: white; padding: 4px 0; border-radius: 4px; margin-bottom: 15px; margin-left: 0;">
                    <style>
                        @media screen and (min-width: 768px) {
                            #trustpilot-widget {
                                position: relative;
                                top: 0;
                                margin-left: 0;
                            }

                            .product-single__header {
                                position: relative;
                            }
                        }

                        @media screen and (max-width: 767px) {
                            #trustpilot-widget {
                                margin-left: 0;
                                justify-content: flex-start;
                                padding-left: 0;
                            }
                        }
                    </style>

                    <div style="display: flex; align-items: center; height: 20px;">
                        <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/stars-5.svg?v=1733090086"
                            alt="5.0 z 5 gwiazdek" style="height: 20px; width: 100px; vertical-align: middle;">
                    </div>

                    <span
                        style="margin-left: 8px; color: #333333; font-size: 13px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; letter-spacing: -0.2px;">
                        <?php
                        $rating = "5.0";
                        $count = "123";
                        echo str_replace(
                            ["{rating}", "{count}"],
                            [$rating, $count],
                            $texts['product']['rating_text']
                        );
                        ?>
                    </span>

                </div>

                <h1 class="product-title">
                    <?php echo htmlspecialchars($currentName); ?>
                </h1>

                <div class="product-price">
                    <?php if (isset($product['old_price']) && $product['old_price'] > 0 && $product['old_price'] > $product['price']): ?>
                        <div class="price-comparison">
                            <span class="old-price"><?php echo number_format($product['old_price'], 2); ?> €</span>
                            <span class="current-price"><?php echo number_format($product['price'], 2); ?> €</span>
                            <span class="discount-badge" style="
    background: linear-gradient(135deg, #ff6b35, #ff8c42);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
    animation: pulse 2s infinite;
    margin-left: 8px;
">
                                <i class="fas fa-fire" style="margin-right: 4px;"></i>
                                <?php
                                $savings = number_format($product['old_price'] - $product['price'], 2);
                                echo str_replace("{amount}", $savings, $texts['product']['save_text']);
                                ?>
                            </span>

                        </div>
                        <div class="campaign-info" style="
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    border: 1px solid #28a745;
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 8px;
    margin-bottom: 24px;
    font-size: 12px;
    color: #155724;
    text-align: left;
    font-weight: 500;
    display: inline-block;
    box-sizing: border-box;
">
                            <i class="fas fa-leaf" style="margin-right: 6px; color: #28a745;"></i>
                            <?php echo $texts['product']['campaign_info']; ?>
                        </div>

                    <?php else: ?>
                        <?php echo number_format($product['price'], 2); ?> €
                    <?php endif; ?>
                </div>



                <?php if (!empty($currentVariants) && is_array($currentVariants)): ?>
                    <div class="product-variants">
                        <div class="variant-title">
                            <?php echo $texts['product']['variant_title']; ?>:
                        </div>

                        <div class="variant-options">
                            <?php foreach ($currentVariants as $index => $variant): ?>
                                <label class="variant-option" for="variant_<?php echo $index; ?>">
                                    <input type="radio" id="variant_<?php echo $index; ?>" name="product_variant"
                                        value="<?php echo $index; ?>"
                                        data-variant-name="<?php echo htmlspecialchars($variant['name']); ?>"
                                        data-variant-image="<?php echo htmlspecialchars($variant['image']); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>
                                        onchange="selectVariant(<?php echo $index; ?>, '<?php echo htmlspecialchars($variant['name']); ?>', '<?php echo htmlspecialchars($variant['image']); ?>')">
                                    <div class="variant-image">
                                        <img src="<?php echo htmlspecialchars($variant['image']); ?>"
                                            alt="<?php echo htmlspecialchars($variant['name']); ?>" loading="lazy">
                                    </div>
                                    <div class="variant-name"><?php echo htmlspecialchars($variant['name']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <div class="product-details">
                    <div class="details-title"><?php echo $texts['product']['details_title']; ?></div>

                    <div class="detail-row">
                        <span class="detail-label"><?php echo $texts['product']['availability']; ?></span>
                        <div
                            class="stock-status <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <i class="fas <?php echo $product['stock_quantity'] > 0 ? 'fa-check' : 'fa-times'; ?>"></i>
                            <?php
                            if ($product['stock_quantity'] > 0) {
                                echo str_replace("{count}", $product['stock_quantity'], $texts['product']['in_stock']);
                            } else {
                                echo $texts['product']['out_of_stock'];
                            }
                            ?>
                        </div>
                    </div>

                    <?php if (!empty($product['shopify_variant_id'])): ?>
                        <div class="detail-row">
                            <span class="detail-label"><?php echo $texts['product']['sku']; ?></span>
                            <div class="detail-value"
                                style="color: #000 !important; font-weight: 700; font-size: 14px; text-decoration: none;">
                                <?php echo htmlspecialchars($product['shopify_variant_id']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Live Viewers Widget -->
                    <div class="live-viewers-widget"
                        style="background: rgba(255, 107, 107, 0.1); color: #666; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; display: flex; align-items: center; gap: 6px; border: 1px solid rgba(255, 107, 107, 0.2); font-size: 12px;">
                        <div
                            style="width: 6px; height: 6px; background: #ff6b6b; border-radius: 50%; animation: blink 2s infinite;">
                        </div>
                        <span style="font-weight: 500;">
                            <?php echo str_replace("{count}", rand(4, 12), $texts['product']['viewers']); ?>
                        </span>
                    </div>

                    <form method="POST" class="add-to-cart-form" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="variant_index" id="variantIndexInput" value="">

                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" onclick="decreaseQuantity()"><i
                                    class="fas fa-minus"></i></button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1"
                                max="<?php echo max(1, $product['stock_quantity']); ?>" class="quantity-input">
                            <button type="button" class="quantity-btn" onclick="increaseQuantity()"><i
                                    class="fas fa-plus"></i></button>
                        </div>

                        <button type="submit" class="add-to-cart-btn" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus"></i>
                            <?php echo $product['stock_quantity'] > 0 ? $texts['product']['add_to_cart'] : $texts['product']['sold_out']; ?>
                        </button>
                    </form>

                    <!-- Delivery Info -->
                    <div class="delivery-info"
                        style="background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%); border: 2px solid #28a745; border-radius: 8px; padding: 12px 16px; margin-top: 15px; display: block; width: 100%; box-sizing: border-box; font-size: 14px; color: #155724;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-truck" style="color: #28a745; font-size: 16px;"></i>
                            <span style="font-weight: 600;">
                                <?php echo $texts['product']['delivery_time']; ?> <span id="deliveryDate"
                                    style="color: #28a745; font-weight: 700;"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Trust Features -->
                    <div class="trust-features">
                        <div class="trust-item">
                            <div class="trust-icon"><i class="fas fa-shipping-fast"></i></div>
                            <span class="trust-text"><?php echo $texts['product']['trust']['fast_shipping']; ?></span>
                        </div>
                        <div class="trust-item">
                            <div class="trust-icon"><i class="fas fa-undo-alt"></i></div>
                            <span class="trust-text"><?php echo $texts['product']['trust']['returns']; ?></span>
                        </div>
                        <div class="trust-item">
                            <div class="trust-icon"><i class="fas fa-shield-alt"></i></div>
                            <span class="trust-text"><?php echo $texts['product']['trust']['warranty']; ?></span>
                        </div>
                        <div class="trust-item">
                            <div class="trust-icon"><i class="fas fa-headset"></i></div>
                            <span class="trust-text"><?php echo $texts['product']['trust']['support']; ?></span>
                        </div>
                    </div>
                </div>



            </div>
        </div>

        <!-- Trustpilot Reviews Section - Apart element -->
        <div class="trustpilot-product-section">
            <div class="trustpilot-product-slider">
                <div class="trustpilot-product-slides">
                    <?php if (!empty($texts['product']['trustpilot']['reviews'])): ?>
                        <?php foreach ($texts['product']['trustpilot']['reviews'] as $review): ?>
                            <div class="trustpilot-product-slide">
                                <div class="trustpilot-product-content">
                                    <div class="trustpilot-stars">
                                        <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/trustpilot-5-stars-9b53.png?v=1749773724"
                                            alt="<?php echo htmlspecialchars($texts['product']['trustpilot']['alt']); ?>"
                                            style="height: 16px; width: auto;">
                                    </div>
                                    <div class="trustpilot-text">
                                        "<?php echo htmlspecialchars($review['text']); ?>"
                                    </div>
                                    <div class="trustpilot-author">
                                        <?php echo htmlspecialchars($review['author']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="trustpilot-logo">
                <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/Trustpilot_Logo__2022__svg.png?v=1749773801"
                    alt="<?php echo htmlspecialchars($texts['product']['trustpilot']['logo_alt']); ?>"
                    style="height: 18px; width: auto; opacity: 0.8;">
            </div>
        </div>


        <?php if (!empty($currentDescription)): ?>
            <div class="action-section">
                <div
                    style="padding: 20px; border-top: 2px solid #e0e0e0; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 0 0 12px 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div class="description-title"
                        style="font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; border-bottom: 3px solid #28a745; padding-bottom: 10px;">
                        <i class="fas fa-info-circle" style="color: #28a745; font-size: 28px;"></i>
                        <?php echo $texts['product']['description_title']; ?>
                    </div>

                    <div class="product-description"
                        style="color: #444; font-size: 16px; line-height: 1.8; background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <?php
                        if (strip_tags($currentDescription) !== $currentDescription) {
                            echo $currentDescription;
                        } else {
                            echo nl2br(htmlspecialchars($currentDescription));
                        }
                        ?>
                    </div>
                </div>

                <!-- Waarom zo goedkoop sectie -->
                <div
                    style="padding: 30px; background: linear-gradient(135deg, #e8f5e8 0%, #c8f7c5 100%); border-radius: 12px; margin-top: 30px; border: 2px solid #28a745; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.15);">

                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <div
                            style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a745 0%, #dc3545 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">
                            <i class="fas fa-fire" style="color: #fff; font-size: 20px;"></i>
                        </div>
                        <h3
                            style="margin: 0; font-size: 24px; font-weight: 700; color: #111; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php echo $texts['product']['why_low_prices_title']; ?>
                        </h3>
                    </div>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px;">

                        <!-- Bulk -->
                        <div
                            style="background: #fff; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745; box-shadow: 0 2px 8px rgba(0,0,0,0.08); color: #111;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <i class="fas fa-warehouse" style="color: #28a745; font-size: 18px;"></i>
                                <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #111;">
                                    <?php echo $texts['product']['why_low_prices']['bulk_title']; ?>
                                </h4>
                            </div>
                            <p style="margin: 0; color: #444; font-size: 14px; line-height: 1.5;">
                                <?php echo $texts['product']['why_low_prices']['bulk_text']; ?>
                            </p>
                        </div>

                        <!-- Mega Sales -->
                        <div
                            style="background: #fff; padding: 20px; border-radius: 10px; border-left: 4px solid #dc3545; box-shadow: 0 2px 8px rgba(0,0,0,0.08); color: #111;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <i class="fas fa-percentage" style="color: #dc3545; font-size: 18px;"></i>
                                <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #111;">
                                    <?php echo $texts['product']['why_low_prices']['mega_title']; ?>
                                </h4>
                            </div>
                            <p style="margin: 0; color: #444; font-size: 14px; line-height: 1.5;">
                                <?php echo $texts['product']['why_low_prices']['mega_text']; ?>
                            </p>
                        </div>

                        <!-- Warranty -->
                        <div
                            style="background: #fff; padding: 20px; border-radius: 10px; border-left: 4px solid #111; box-shadow: 0 2px 8px rgba(0,0,0,0.08); color: #111;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <i class="fas fa-certificate" style="color: #111; font-size: 18px;"></i>
                                <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #111;">
                                    <?php echo $texts['product']['why_low_prices']['warranty_title']; ?>
                                </h4>
                            </div>
                            <p style="margin: 0; color: #444; font-size: 14px; line-height: 1.5;">
                                <?php echo $texts['product']['why_low_prices']['warranty_text']; ?>
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <!-- ===== GERELATEERDE PRODUCTEN SECTIE =====
    <?php if (!empty($relatedProducts)): ?>
<section class="products" style="padding: 60px 20px; background: #f8f9fa;">
    <div class="products-container">
        <h2 class="section-title"><?php echo $texts['product']['related']['title']; ?></h2>
        <p class="section-subtitle"><?php echo $texts['product']['related']['subtitle']; ?></p>
        
        <div class="products-grid">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
                <?php
                    // Handle translations safely
                    $lang = $_SESSION['lang'] ?? 'nl';
                    $translation = $relatedProduct['translations'][$lang] ?? null;
                    $relatedName = $translation['name'] ?? $relatedProduct['name'] ?? '---';
                    $relatedDescription = $translation['description'] ?? $relatedProduct['description'] ?? '';
                ?>
                <div class="product-card">
                    <div class="product-image" onclick="window.location.href='product.php?slug=<?php echo urlencode($relatedProduct['slug']); ?>'">
                        <?php if (!empty($relatedProduct['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($relatedProduct['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($relatedName); ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <i class="fas fa-image fallback-icon" style="display: none;"></i>
                        <?php else: ?>
                            <i class="fas fa-box fallback-icon"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <?php if (!empty($relatedProduct['category'])): ?>
                            <div class="product-category" style="background: #000000; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block;">
                                <?php echo htmlspecialchars($relatedProduct['category']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h4><?php echo htmlspecialchars($relatedName); ?></h4>
                        
                        <div class="product-price-container">
                            <?php if (!empty($relatedProduct['old_price']) && $relatedProduct['old_price'] > $relatedProduct['price']): ?>
                                <div class="price-with-discount">
                                    <span class="old-price" style="color: #666;"><?php echo number_format($relatedProduct['old_price'], 2); ?> €</span>
                                    <span class="current-price" style="color: #dc3545;"><?php echo number_format($relatedProduct['price'], 2); ?> €</span>
                                    <span class="discount-badge" style="background: #000000; color: white;">
                                        -<?php echo round((($relatedProduct['old_price'] - $relatedProduct['price']) / $relatedProduct['old_price']) * 100); ?>%
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="product-price" style="color: #dc3545;"><?php echo number_format($relatedProduct['price'], 2); ?> €</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($relatedDescription)): ?>
                            <p><?php echo htmlspecialchars(substr($relatedDescription, 0, 100)); ?><?php echo strlen($relatedDescription) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        
                        <form method="POST" class="product-actions">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="product_id" value="<?php echo $relatedProduct['id']; ?>">
                            
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo max(1, $relatedProduct['stock_quantity']); ?>" 
                                   class="quantity-input" title="Ilość">
                                   
                            <button type="submit" class="btn btn-primary btn-small" style="background: #28a745; color: white; border: none;">
                                <i class="fas fa-cart-plus"></i>
                                <?php echo $texts['product']['related']['add_to_cart']; ?>
                            </button>
                        </form>
                        
                        <?php if (!empty($relatedProduct['stock_quantity'])): ?>
                            <small style="color: #28a745; margin-top: 10px; display: block;">
                                <i class="fas fa-check-circle"></i> 
                                <?php echo str_replace("{count}", $relatedProduct['stock_quantity'], $texts['product']['related']['in_stock']); ?>
                            </small>
                        <?php else: ?>
                            <small style="color: #dc3545; margin-top: 10px; display: block;">
                                <i class="fas fa-exclamation-circle"></i> 
                                <?php echo $texts['product']['related']['out_of_stock']; ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

-->
    <!-- ===== BANNER SECTIE ===== -->
    <section style="padding: 50px 20px; background: linear-gradient(135deg, #2c3e50, #34495e);">
        <div style="max-width: 1100px; margin: 0 auto; text-align: center; color: white;">
            <h2
                style="font-size: 2rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 15px;">
                <img src="netherlands-flag.svg" alt="Polska"
                    style="width: 36px; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                <?php echo $texts['product']['promo_banner']['title']; ?>
            </h2>
            <p style="font-size: 1.1rem; max-width: 600px; margin: 0 auto 25px auto; opacity: 0.9;">
                <?php echo $texts['product']['promo_banner']['subtitle']; ?>
            </p>
            <a href="produkty.php"
                style="background: #ff4757; color: white; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s;">
                <?php echo $texts['product']['promo_banner']['button']; ?>
            </a>
        </div>
    </section>


    <!-- ===== FOOTER ===== -->
    <?php include 'footer.php'; ?>

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
                    <p style="color: #666;">Twój koszyk jest pusty</p>
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
                            <div class="cart-item-price"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> €
                            </div>

                            <div class="cart-item-controls">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1"
                                        max="99" style="width: 50px; padding: 4px; text-align: center;"
                                        onchange="this.form.submit()">
                                </form>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_from_cart">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit"
                                        style="background: none; border: none; color: #dc3545; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-total">
                    <div class="cart-total-price">
                        Razem: <?php echo number_format($cartTotal, 2); ?> €
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-primary"
                            style="width: 100%; justify-content: center; margin-bottom: 15px; padding: 18px 24px; font-size: 18px;">
                            <i class="fas fa-credit-card" style="margin-right: 8px;"></i>
                            Przejdź do kasy
                        </button>
                    </form>

                    <!-- Trustpilot Reviews Section -->
                    <div class="trustpilot-cart-section">
                        <div class="trustpilot-cart-slider">
                            <div class="trustpilot-cart-slides">
                                <!-- Review 1 -->
                                <div class="trustpilot-cart-slide">
                                    <div class="trustpilot-cart-content">
                                        <div class="trustpilot-stars">
                                            <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/trustpilot-5-stars-9b53.png?v=1749773724"
                                                alt="5 sterren Trustpilot" style="height: 14px; width: auto;">
                                        </div>
                                        <div class="trustpilot-text">"Świetna obsługa, szybka dostawa!"</div>
                                        <div class="trustpilot-author">- Anna K.</div>
                                    </div>
                                </div>

                                <!-- Review 2 -->
                                <div class="trustpilot-cart-slide">
                                    <div class="trustpilot-cart-content">
                                        <div class="trustpilot-stars">
                                            <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/trustpilot-5-stars-9b53.png?v=1749773724"
                                                alt="5 sterren Trustpilot" style="height: 14px; width: auto;">
                                        </div>
                                        <div class="trustpilot-text">"Polecam wszystkim, jakość na najwyższym poziomie"
                                        </div>
                                        <div class="trustpilot-author">- Marek S.</div>
                                    </div>
                                </div>

                                <!-- Review 3 -->
                                <div class="trustpilot-cart-slide">
                                    <div class="trustpilot-cart-content">
                                        <div class="trustpilot-stars">
                                            <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/trustpilot-5-stars-9b53.png?v=1749773724"
                                                alt="5 sterren Trustpilot" style="height: 14px; width: auto;">
                                        </div>
                                        <div class="trustpilot-text">"Najlepszy sklep online, zawsze zadowolony"</div>
                                        <div class="trustpilot-author">- Piotr W.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="trustpilot-logo">
                            <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/Trustpilot_Logo__2022__svg.png?v=1749773801"
                                alt="Trustpilot" style="height: 16px; width: auto;">
                        </div>
                    </div>

                    <!-- Trust elements -->
                    <div class="checkout-benefits">
                        <div class="benefit-item">
                            <i class="fas fa-shield-alt" style="color: #28a745;"></i>
                            <span>Bezpieczne płatności SSL</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-truck" style="color: #17a2b8;"></i>
                            <span>Zawsze darmowa dostawa</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-undo" style="color: #ffc107;"></i>
                            <span>30 dni na zwrot</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-headset" style="color: #6f42c1;"></i>
                            <span>Wsparcie 24/7</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cart Overlay -->
    <div class="cart-overlay" id="overlay" onclick="closeCart()"></div>

    <script>
        <?php if (isset($success_message) && (strpos($success_message, 'toegevoegd') !== false || strpos($success_message, 'dodany') !== false || strpos($success_message, 'ajouté') !== false)): ?>
        // Wait for page to fully load, then open cart
        document.addEventListener('DOMContentLoaded', function() {
            // Small delay to ensure smooth animation
            setTimeout(function() {
                openCart();
            }, 300);
        });
    <?php endif; ?>
        function increaseQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value);

            if (current < max) {
                input.value = current + 1;
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            const min = parseInt(input.getAttribute('min'));
            const current = parseInt(input.value);

            if (current > min) {
                input.value = current - 1;
            }
        }

        // SWIPE/CAROUSEL LOGICA
        (function () {
            const images = <?php echo json_encode(!empty($product['images']) ? array_values($product['images']) : (!empty($product['image_url']) ? [$product['image_url']] : [])); ?>;
            let currentIndex = 0;
            const mainImage = document.getElementById('mainImage');
            const thumbnails = document.querySelectorAll('.image-thumbnails .thumbnail');
            const prevBtn = document.getElementById('prevImageBtn');
            const nextBtn = document.getElementById('nextImageBtn');
            const mainImageContainer = document.getElementById('mainImageContainer');

            function showImage(index) {
                if (!images.length) return;
                currentIndex = (index + images.length) % images.length;
                mainImage.src = images[currentIndex];
                // Thumbnails highlight
                if (thumbnails.length) {
                    thumbnails.forEach((thumb, i) => {
                        if (i === currentIndex) {
                            thumb.classList.add('active');
                            // Scroll thumbnail into view
                            thumb.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest',
                                inline: 'center'
                            });
                        } else {
                            thumb.classList.remove('active');
                        }
                    });
                }
            }

            if (prevBtn) prevBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                showImage(currentIndex - 1);
            });
            if (nextBtn) nextBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                showImage(currentIndex + 1);
            });
            if (thumbnails.length) {
                thumbnails.forEach((thumb, i) => {
                    thumb.addEventListener('click', function () {
                        showImage(i);
                    });
                });
            }
            // Touch/swipe events
            if (mainImageContainer && images.length > 1) {
                let startX = 0;
                let endX = 0;
                mainImageContainer.addEventListener('touchstart', function (e) {
                    startX = e.touches[0].clientX;
                });
                mainImageContainer.addEventListener('touchmove', function (e) {
                    endX = e.touches[0].clientX;
                });
                mainImageContainer.addEventListener('touchend', function (e) {
                    if (startX && endX) {
                        if (startX - endX > 40) {
                            // swipe left
                            showImage(currentIndex + 1);
                        } else if (endX - startX > 40) {
                            // swipe right
                            showImage(currentIndex - 1);
                        }
                    }
                    startX = 0;
                    endX = 0;
                });
            }
            // Init
            showImage(0);
        })();

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

        // Auto-hide success messages
        setTimeout(function () {
            const alerts = document.querySelectorAll('[style*="background: #d4edda"]');
            alerts.forEach(function (alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease';
                setTimeout(function () {
                    alert.remove();
                }, 300);
            });
        }, 4000);

        // Update cart badge after adding item
        <?php if (isset($success_message) && strpos($success_message, 'dodany') !== false): ?>
            // Update cart badge
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = '<?php echo $cartItemCount; ?>';
            } else if (<?php echo $cartItemCount; ?> > 0) {
                // Create badge if it doesn't exist
                const cartIcon = document.querySelector('.cart-icon');
                if (cartIcon) {
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.textContent = '<?php echo $cartItemCount; ?>';
                    cartIcon.appendChild(badge);
                }
            }

            // Auto-open cart when product is added
            setTimeout(function () {
                openCart();
            }, 500);
        <?php endif; ?>

        // Auto-open cart when add-to-cart form is submitted
        document.addEventListener('DOMContentLoaded', function () {
            const addToCartForm = document.querySelector('.add-to-cart-form');
            if (addToCartForm) {
                addToCartForm.addEventListener('submit', function () {
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';
                    submitBtn.disabled = true;

                    // Auto-open cart after a short delay
                    setTimeout(function () {
                        // openCart();
                        // Reset button after 2 seconds
                        setTimeout(function () {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 2000);
                    }, 300);
                });
            }

            // Live viewers counter animation
            const viewerCount = document.getElementById('viewerCount');
            if (viewerCount) {
                let currentCount = parseInt(viewerCount.textContent);

                function updateViewerCount() {
                    // Realistische verandering: +1, -1, of +2, -2 (maximaal)
                    const change = Math.random() > 0.5 ?
                        (Math.random() > 0.5 ? 1 : -1) :
                        (Math.random() > 0.5 ? 2 : -2);

                    const newCount = Math.max(4, Math.min(12, currentCount + change));

                    // Alleen updaten als er echt een verandering is
                    if (newCount !== currentCount) {
                        currentCount = newCount;
                        viewerCount.style.opacity = '0.7';

                        setTimeout(() => {
                            viewerCount.textContent = newCount;
                            viewerCount.style.opacity = '1';
                        }, 150);
                    }
                }

                // Update elke 8-15 seconden (veel langzamer)
                setInterval(updateViewerCount, Math.random() * 7000 + 8000);
            }
        });

        // Smooth animations
        document.addEventListener('DOMContentLoaded', function () {
            const elements = document.querySelectorAll('.product-info > *');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    el.style.transition = 'all 0.6s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Calculate and display delivery date
            calculateDeliveryDate();
        });


        

        // Function to calculate delivery date (current date + 3 days)
        const currentLang = "<?php echo $lang; ?>"; 
       function calculateDeliveryDate() {
    const today = new Date();
    let deliveryDate = new Date(today);

    // Add 3 days (including weekends)
    deliveryDate.setDate(deliveryDate.getDate() + 3);

    // Hardcoded day and month names for each language
    const dayNames = {
        nl: ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
        fr: ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']
    };

    const monthNames = {
        nl: ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
        fr: ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']
    };

    // Pick based on current language or fallback to Dutch
    const lang = currentLang in dayNames ? currentLang : 'nl';

    const dayName = dayNames[lang][deliveryDate.getDay()];
    const day = deliveryDate.getDate();
    const month = monthNames[lang][deliveryDate.getMonth()];

    const formattedDate = `${dayName}, ${day} ${month}`;

    // Update the delivery date element
    const deliveryDateElement = document.getElementById('deliveryDate');
    if (deliveryDateElement) {
        deliveryDateElement.textContent = formattedDate;
    }
}

// Run the function
calculateDeliveryDate();

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const atcBtn = document.querySelector('.add-to-cart-btn');

            if (atcBtn) {
                atcBtn.addEventListener('click', function (e) {
                    e.preventDefault(); // stop the instant reload

                    const form = atcBtn.closest('form');

                    const productId = "<?php echo $product['id']; ?>";
                    const productName = "<?php echo htmlspecialchars($product['name']); ?>";
                    const price = parseFloat("<?php echo $product['price']; ?>");
                    const currency = "PLN";

                    // Track with FB Pixel
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'AddToCart', {
                            content_ids: [productId],
                            content_name: productName,
                            value: price,
                            currency: currency
                        });
                    }

                    // Optional: send to CAPI
                    fetch('/track-add-to-cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            product_id: productId,
                            product_name: productName,
                            price: price,
                            currency: currency
                        })
                    }).catch(err => console.error('CAPI ATC failed', err));

                    // Submit the form after short delay so tracking sends
                    setTimeout(() => {
                        form.submit();
                    }, 350); // 0.35s delay
                });
            }
        });
    </script>





</body>

</html>