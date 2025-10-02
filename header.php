<?php
// Header component
require_once 'lang.php';
require_once 'database.php';

// Get database instance and real products
$db = Database::getInstance();
$realProducts = $db->getAllProducts(true);
$productsForNotifications = array_slice($realProducts, 0, 10); // Take first 10 products

// READ CATEGORIES FROM SETTINGS.JSON
$settingsFile = 'settings.json';
$settings = json_decode(file_get_contents($settingsFile), true);
$categories = $settings['categories'] ?? [];

// Function to create category URLs
function getCategoryUrl($category)
{
    // Always return relative path so it works both locally and in production
    return "produkty.php?category=" . urlencode($category);
}

// Zet een PHP variabele voor het aantal producten in de winkelwagen
$cartHasItems = isset($cartItems) && count($cartItems) > 0;

// Handle translations for cart items
$lang = $_SESSION['lang'] ?? 'nl';


if (!empty($cartItems)) {
    foreach ($cartItems as &$item) {
        $translation = $item['translations'][$lang] ?? null;
        $item['current_name'] = $translation['name'] ?? $item['name'] ?? '';
    }
    unset($item);
}


// Get current query parameters
$params = $_GET;

// Make copies for language switch
$paramsNl = $params;
$paramsFr = $params;

$paramsNl['lang'] = 'nl';
$paramsFr['lang'] = 'fr';

// Build new query strings
$linkNl = $_SERVER['PHP_SELF'] . '?' . http_build_query($paramsNl);
$linkFr = $_SERVER['PHP_SELF'] . '?' . http_build_query($paramsFr);
?>

<script>
    const activeLang = "<?php echo $lang; ?>";
</script>
<style>
    .announcement-bar {
        background: #ff0000;
        color: white;
        padding: 4px 0;
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

    .header {
        background: #000000;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.1);
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .menu-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .header-container {
        max-width: 100%;
        margin: 0 auto;
        padding: 0 20px;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    .logo {
        display: flex;
        align-items: center;
        color: white;
        text-decoration: none;
        z-index: 1001;
        transition: all 0.3s ease;
        position: relative;
    }

    .logo:hover {
        transform: scale(1.05);
    }

    .logo-image {
        height: 50px;
        width: auto;
        object-fit: contain;
    }

    .logo-icon::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transform: rotate(45deg);
        transition: all 0.6s ease;
        opacity: 0;
    }

    .logo:hover .logo-icon::before {
        opacity: 1;
        animation: shine 0.6s ease;
    }

    @keyframes shine {
        0% {
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
        }

        100% {
            transform: translateX(100%) translateY(100%) rotate(45deg);
        }
    }

    .logo-text {
        font-size: 1.8rem;
        font-weight: 700;
        color: white;
        letter-spacing: 1px;
        margin: 0;
        padding: 0;
        text-transform: uppercase;
        position: relative;
        display: inline-block;
    }

    .logo i {
        font-size: 1.8rem;
    }

    .nav-menu {
        display: flex;
        list-style: none;
        gap: 40px;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    .nav-menu a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 6px;
        text-transform: capitalize;
    }

    .nav-menu a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateY(-1px);
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .search-bar {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-bar input {
        width: 300px;
        padding: 12px 16px 12px 45px;
        border: none;
        border-radius: 25px;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
    }

    .search-bar input:focus {
        border-color: #dc3545;
        outline: none;
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
    }

    .search-bar i {
        position: absolute;
        left: 16px;
        color: #666;
    }

    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        max-height: 400px;
        overflow-y: auto;
        z-index: 10001;
        display: none;
        margin-top: 8px;
        border: 1px solid #e1e5e9;
    }

    .search-results.show {
        display: block;
        animation: fadeInDown 0.3s ease;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .search-result-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #f1f3f4;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        color: #333;
    }

    .search-result-item:hover {
        background: #f8f9fa;
        transform: translateX(4px);
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-image {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        margin-right: 12px;
        object-fit: cover;
        background: #f1f3f4;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .search-result-image img {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        object-fit: cover;
    }

    .search-result-image i {
        color: #999;
        font-size: 20px;
    }

    .search-result-info {
        flex: 1;
    }

    .search-result-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
        color: #333;
    }

    .search-result-price {
        color: #dc3545;
        font-weight: 700;
        font-size: 14px;
    }

    .search-result-category {
        color: #666;
        font-size: 12px;
        margin-bottom: 2px;
    }

    .search-no-results {
        padding: 20px;
        text-align: center;
        color: #666;
        font-size: 14px;
    }

    .search-no-results i {
        font-size: 24px;
        margin-bottom: 8px;
        color: #ccc;
    }

    .cart-icon {
        color: white;
        text-decoration: none;
        font-size: 18px;
        position: relative;
        padding: 12px;
        border-radius: 50%;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.1);
    }

    .cart-icon:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .cart-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
    }

    .mobile-menu-toggle {
        display: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 10px;
        border-radius: 6px;
        transition: all 0.3s ease;
        z-index: 100001 !important;
        position: relative !important;
    }

    .mobile-menu-toggle:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .mobile-search-bar {
        display: none;
        background: rgba(255, 255, 255, 0.95);
        padding: 15px 20px;
        border-top: 1px solid rgba(0, 112, 243, 0.1);
        position: relative;
    }

    .mobile-search-bar input {
        width: 100%;
        padding: 12px 16px 12px 45px;
        border: 2px solid #e1e5e9;
        border-radius: 25px;
        font-size: 16px;
        outline: none;
        transition: all 0.3s ease;
    }

    .mobile-search-bar i {
        position: absolute;
        left: 35px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        font-size: 16px;
    }

    .mobile-search-bar input:focus {
        border-color: #dc3545;
        outline: none;
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
    }

    /* Tablet responsive */
    @media (max-width: 1024px) {
        .header-container {
            padding: 0 15px;
        }

        .nav-menu {
            gap: 25px;
        }

        .search-bar input {
            width: 220px;
        }
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .announcement-content {
            flex-direction: row;
            gap: 10px;
            padding: 4px 15px;
            flex-wrap: nowrap;
        }

        .announcement-bar {
            font-size: 12px;
        }

        .header-container {
            padding: 0 15px;
        }

        .header-content {
            height: 60px;
        }

        .logo {
            font-size: 1.3rem;
        }

        .logo-image {
            height: 40px;
            width: auto;
        }

        .nav-menu {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: #111 !important;
            flex-direction: column;
            justify-content: flex-start;
            align-items: stretch;
            padding: 80px 0 40px 0;
            gap: 0;
            transform: translateY(-100%);
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            z-index: 99999;
            overflow-y: auto;
            will-change: transform;
            pointer-events: none;
        }

        .nav-menu.open {
            transform: translateY(0);
            pointer-events: auto !important;
        }

        .nav-menu.open li {
            width: 100%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            pointer-events: auto !important;
        }

        .nav-menu.open a {
            width: 100% !important;
            padding: 20px 30px !important;
            font-size: 16px !important;
            border-radius: 0 !important;
            justify-content: flex-start !important;
            display: block !important;
            position: relative !important;
            z-index: 99999 !important;
            pointer-events: auto !important;
            text-decoration: none !important;
            color: white !important;
            background: transparent !important;
        }

        .nav-menu a:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: none;
        }

        .header-actions {
            gap: 15px;
        }

        .search-bar {
            display: none;
        }

        .mobile-search-bar {
            display: block;
        }

        .mobile-menu-toggle {
            display: block !important;
            z-index: 100001 !important;
            position: relative !important;
        }

        body.menu-open {
            overflow: hidden;
        }

        .logo-text {
            font-size: 1.5rem;
        }
    }

    /* Extra small mobile */
    @media (max-width: 480px) {
        .header-container {
            padding: 0 10px;
        }

        .logo {
            font-size: 1.2rem;
        }

        .logo-image {
            height: 35px;
            width: auto;
        }

        .mobile-search-bar {
            padding: 12px 15px;
        }

        .cart-sidebar {
            width: 100%;
            right: -100vw;
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
        z-index: 10002;
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 700px) {


        .cart-sidebar {
            width: 100%;
            right: -100vw;
        }
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

    .cart-item-pricing {
        margin-bottom: 10px;
    }

    .cart-price-with-discount {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .cart-old-price {
        color: #999;
        font-size: 12px;
        font-weight: 500;
        text-decoration: line-through;
        text-decoration-color: #dc3545;
        text-decoration-thickness: 2px;
    }

    .cart-current-price {
        color: #28a745;
        font-weight: 600;
        font-size: 14px;
    }

    .cart-discount-badge {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 2px 6px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
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

    .cart-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10001;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .cart-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .header-logo-text {
        font-size: 1.2rem;
        font-weight: 700;
        color: white;
        letter-spacing: 1px;
        margin: 0;
        padding: 0;
        text-transform: uppercase;
        position: relative;
        display: inline-block;
    }

    @media (max-width: 768px) {
        .header-logo-text {
            font-size: 1.4rem;
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
        height: 100px;
        overflow: hidden;
        width: 100%;
    }

    .trustpilot-cart-slides {
        display: flex;
        width: 500%;
        height: 100%;
        animation: cartSlide 20s infinite ease-in-out;
        overflow: hidden;
    }

    @keyframes cartSlide {

        0%,
        12% {
            transform: translateX(0);
        }

        16%,
        28% {
            transform: translateX(-20%);
        }

        32%,
        44% {
            transform: translateX(-40%);
        }

        48%,
        60% {
            transform: translateX(-60%);
        }

        64%,
        76% {
            transform: translateX(-80%);
        }

        80%,
        100% {
            transform: translateX(0);
        }
    }

    .trustpilot-cart-slide {
        width: 20%;
        min-width: 20%;
        max-width: 20%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 10px;
        box-sizing: border-box;
    }

    .trustpilot-cart-content {
        text-align: center;
        width: 100%;
    }

    .trustpilot-cart-content .trustpilot-text {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
        line-height: 1.3;
        font-style: italic;
        white-space: normal;
        word-break: break-word;
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

    /* Original Trustpilot Header Section Styling */
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




    /* Categories Dropdown Styles */
    .nav-item {
        position: relative;
    }

    .dropdown-arrow {
        font-size: 12px;
        transition: transform 0.3s ease;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        background: white;
        min-width: 250px;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 10000;
        border: 1px solid #e1e5e9;
        overflow: hidden;
    }

    .nav-item:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .nav-item:hover .dropdown-arrow {
        transform: rotate(180deg);
    }

    .dropdown-item {
        display: block;
        padding: 12px 20px;
        color: #000000ff;
        text-decoration: none;
        transition: all 0.2s ease;
        border-bottom: 1px solid #f1f3f4;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
        color: #272727ff;
        transform: translateX(5px);
    }

    .mobile-categories {
        display: none;
    }

    .mobile-category-link {
        width: 100% !important;
        padding: 15px 30px !important;
        font-size: 14px !important;
        color: rgba(255, 255, 255, 0.8) !important;
        padding-left: 50px !important;
    }

    @media (max-width: 768px) {
        .desktop-categories {
            display: none;
        }

        .mobile-categories {
            display: block;
        }
    }

    .lang-toggle {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 25px;
        padding: 3px;
        gap: 3px;
    }

    .lang-toggle .lang-option {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        color: #fff;
        transition: all 0.3s ease;
    }

    .lang-toggle .lang-option:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .lang-toggle .lang-option.active {
        background: #fff;
        color: #000;
    }
</style>

<div class="announcement-bar">
    <div class="announcement-content">
        <span><?php echo $texts['header']['announcement']['text']; ?></span>
        <div class="timer">
            <span id="countdown">13:00:00</span>
        </div>
    </div>
</div>


<header class="header">
    <div class="header-container">
        <div class="header-content">
            <a href="index.php" class="logo">
                <span class="logo-text">Sarnos</span>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php"><?php echo $texts['header']['nav']['home']; ?></a></li>

                <!-- CATEGORIES DROPDOWN FOR DESKTOP -->
                <li class="nav-item desktop-categories">
                    <a href="produkty.php" class="nav-link">
                        <?php echo $texts['header']['nav']['categories']; ?>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <div class="dropdown-menu">
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo getCategoryUrl($category); ?>" class="dropdown-item"
                                style="color:black !important;">
                                <?php echo htmlspecialchars($category); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <li><a href="produkty.php"><?php echo $texts['header']['nav']['all_products']; ?></a></li>
                <li><a href="about.php"><?php echo $texts['header']['nav']['about']; ?></a></li>
                <li><a href="kontakt.php"><?php echo $texts['header']['nav']['contact']; ?></a></li>

                <!-- MOBILE CATEGORIES -->
                <div class="mobile-categories">
                    <li class="mobile-category-item">
                        <div
                            style="padding: 15px 30px; color: rgba(255, 255, 255, 0.6); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                            <?php echo $texts['header']['nav']['categories']; ?>
                        </div>
                    </li>
                    <?php foreach ($categories as $category): ?>
                        <li class="mobile-category-item">
                            <a href="<?php echo getCategoryUrl($category); ?>" class="mobile-category-link">
                                <?php echo htmlspecialchars($category); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </div>
            </ul>

            <div class="header-actions">

                <!-- Language Toggle
                <div class="lang-toggle">
                    <a href="<?php echo $linkNl; ?>"
                        class="lang-option <?php echo $_SESSION['lang'] === 'nl' ? 'active' : ''; ?>">NL</a>
                    <a href="<?php echo $linkFr; ?>"
                        class="lang-option <?php echo $_SESSION['lang'] === 'fr' ? 'active' : ''; ?>">FR</a>
                </div> -->

                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput"
                        placeholder="<?php echo $texts['header']['nav']['search_placeholder']; ?>" autocomplete="off">
                    <div class="search-results" id="searchResults"></div>
                </div>

                <a href="#" class="cart-icon" onclick="openCart()">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    if (isset($db) && isset($_SESSION['cart_session_id'])) {
                        $cartItemCount = $db->getCartItemCount($_SESSION['cart_session_id']);
                        if ($cartItemCount > 0) {
                            echo '<span class="cart-badge">' . $cartItemCount . '</span>';
                        }
                    }
                    ?>
                </a>

                <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile search bar -->
    <div class="mobile-search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="mobileSearchInput"
            placeholder="<?php echo $texts['header']['nav']['search_placeholder']; ?>" autocomplete="off">
        <div class="search-results" id="mobileSearchResults"></div>
    </div>
</header>


<!-- Mobile menu overlay -->
<div class="menu-overlay" id="menuOverlay" onclick="closeMobileMenu()"></div>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-cart"></i> <?php echo $texts['cart']['header']['title']; ?></h3>
        <button class="cart-close" onclick="closeCart()">
            <i class="fas fa-times"></i>
    </div>

    <!-- RESERVERINGSBALK IN DE WINKELWAGEN -->
    <div id="reservationBar"
        style="display: none; background: #dc3545; color: #fff; text-align: center; font-size: 14px; font-weight: 500; padding: 7px 8px; border-radius: 0; margin: 0; letter-spacing: 0.01em; transition: opacity 0.3s; position: sticky; top: 0; z-index: 20;">
        <span>
            <?php
            // Insert {time} placeholder dynamically
            echo str_replace("{time}", '<span id="reservationTimer">10:00</span>', $texts['cart']['reservation']['text']);
            ?>
        </span>
    </div>

    <!-- EINDE RESERVERINGSBALK -->
    <div class="cart-content">
        <?php
        // Get cart data if available
        if (isset($db) && isset($_SESSION['cart_session_id'])) {
            $cartItems = $db->getCartItems($_SESSION['cart_session_id']);
            $cartTotal = $db->getCartTotal($_SESSION['cart_session_id']);
        } else {
            $cartItems = [];
            $cartTotal = 0;
        }
        ?>

        <?php if (empty($cartItems)): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                <p style="color: #666;"><?php echo $texts['cart']['empty']; ?></p>
            </div>

        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
    <div class="cart-item">
        <div class="cart-item-image">
            <?php if ($item['image_url']): ?>
                <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                     alt="<?php echo htmlspecialchars($item['current_name']); ?>">
            <?php else: ?>
                <i class="fas fa-box" style="color: #ccc;"></i>
            <?php endif; ?>
        </div>

        <div class="cart-item-info" style="flex: 1;">
            <h5><?php echo htmlspecialchars($item['current_name']); ?></h5>
            <div class="cart-item-pricing">
                <?php if (isset($item['old_price']) && $item['old_price'] > 0 && $item['old_price'] > $item['price']): ?>
                    <div class="cart-price-with-discount">
                        <span class="cart-old-price">
                            <?php echo number_format($item['old_price'] * $item['quantity'], 2); ?> €
                        </span>
                        <span class="cart-current-price">
                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> €
                        </span>
                        <span class="cart-discount-badge">
                            -<?php echo number_format($item['old_price'] - $item['price'], 2); ?> €
                        </span>
                    </div>
                <?php else: ?>
                    <div class="cart-item-price">
                        <?php echo number_format($item['price'] * $item['quantity'], 2); ?> €
                    </div>
                <?php endif; ?>
            </div>

            <!-- <div class="cart-item-controls">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="update_cart">
                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99"
                           style="width: 50px; padding: 4px; text-align: center;" onchange="this.form.submit()">
                </form>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="remove_from_cart">
                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                    <button type="submit" style="background: none; border: none; color: #dc3545; cursor: pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div> -->
        </div>
    </div>
<?php endforeach; ?>


            <div class="cart-total">
                <div class="cart-total-price">
                    <?php echo $texts['cart']['total']; ?>: <?php echo number_format($cartTotal, 2); ?> €
                </div>

                <form action="checkout.php" method="GET">
                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; justify-content: center; margin-bottom: 15px; padding: 18px 24px; font-size: 18px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); font-weight: 600;">
                        <i class="fas fa-credit-card" style="margin-right: 8px;"></i>
                        <?php echo $texts['cart']['checkout']; ?>
                    </button>
                </form>

                <!-- Trustpilot Reviews Section -->
                <div class="trustpilot-cart-section">
                    <div class="trustpilot-cart-slider">
                        <div class="trustpilot-cart-slides">
                            <?php foreach ($texts['cart']['reviews'] as $review): ?>
                                <div class="trustpilot-cart-slide">
                                    <div class="trustpilot-cart-content">
                                        <div class="trustpilot-stars">
                                            <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/trustpilot-5-stars-9b53.png?v=1749773724"
                                                alt="5 Trustpilot sterren" style="height: 14px; width: auto;">
                                        </div>
                                        <div class="trustpilot-text"><?php echo $review; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
                        <i class="fas fa-shield-alt"></i>
                        <span><?php echo $texts['cart']['benefits']['secure']; ?></span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-truck"></i>
                        <span><?php echo $texts['cart']['benefits']['shipping']; ?></span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-undo"></i>
                        <span><?php echo $texts['cart']['benefits']['returns']; ?></span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-headset"></i>
                        <span><?php echo $texts['cart']['benefits']['support']; ?></span>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- Cart Overlay -->
<div class="cart-overlay" id="overlay" onclick="closeCart()"></div>

<!-- Recent Sales Notifications -->
<div id="recentSalesContainer" style="
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 9999;
    max-width: 300px;
">
    <!-- Notifications will be added here dynamically -->
</div>


<!-- Trustpilot Header Section -->
<div class="trustpilot-section">
    <div class="trustpilot-slider">
        <div class="trustpilot-slides">

            <!-- Slide 1: Trustpilot -->
            <div class="trustpilot-slide">
                <div class="trustpilot-text"><?php echo $texts['header']['trustpilot']['slides'][0]['text']; ?></div>
                <div class="trustpilot-stars-logo">
                    <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/trustpilot-5-stars-9b53.png?v=1749773724"
                        alt="5 stars Trustpilot" style="height: 14px; width: auto;">
                </div>
                <div class="trustpilot-logo-container">
                    <img src="https://cdn.shopify.com/s/files/1/0762/6231/0225/files/Trustpilot_Logo__2022__svg.png?v=1749773801"
                        alt="Logo Trustpilot" loading="lazy">
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="trustpilot-slide">
                <svg class="green-check" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" stroke="#00b67a" stroke-width="1.5" />
                </svg>
                <div class="slide-text"><?php echo $texts['header']['trustpilot']['slides'][1]['text']; ?></div>
            </div>

            <!-- Slide 3 -->
            <div class="trustpilot-slide">
                <svg class="green-check" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" stroke="#00b67a" stroke-width="1.5" />
                </svg>
                <div class="slide-text"><?php echo $texts['header']['trustpilot']['slides'][2]['text']; ?></div>
            </div>

        </div>
    </div>
</div>


<script>
    function toggleMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const menuToggle = document.querySelector('.mobile-menu-toggle i');
        const overlay = document.getElementById('menuOverlay');

        navMenu.classList.toggle('open');
        document.body.classList.toggle('menu-open');

        // Toggle overlay but disable it for clicks
        if (navMenu.classList.contains('open')) {
            overlay.classList.add('active');
            overlay.style.pointerEvents = 'none'; // Disable overlay clicks
            menuToggle.className = 'fas fa-times';

            // Force menu links to be clickable with more aggressive approach
            const menuLinks = document.querySelectorAll('.nav-menu li a');
            menuLinks.forEach((link, index) => {
                // Only replace navigation links, not toggle buttons
                if (link.href && !link.onclick) {
                    // Remove any existing event listeners
                    const newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);

                    // Add direct onclick
                    newLink.onclick = function (e) {
                        console.log(`Direct click on link ${index}: ${this.href}`);
                        closeMobileMenu(); // Close menu first
                        setTimeout(() => {
                            window.location.href = this.href;
                        }, 100);
                        return false;
                    };

                    // Style it
                    newLink.style.cssText = `
                        pointer-events: auto !important;
                        z-index: 100000 !important;
                        position: relative !important;
                        display: block !important;
                        width: 100% !important;
                        padding: 20px 30px !important;
                        color: white !important;
                        text-decoration: none !important;
                        background: transparent !important;
                        border-radius: 0 !important;
                    `;

                    console.log(`Link ${index}: ${newLink.href} - Completely rebuilt`);
                }
            });
        } else {
            overlay.classList.remove('active');
            overlay.style.pointerEvents = 'auto'; // Re-enable overlay clicks
            menuToggle.className = 'fas fa-bars';
        }
    }

    function closeMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const menuToggle = document.querySelector('.mobile-menu-toggle i');
        const overlay = document.getElementById('menuOverlay');

        navMenu.classList.remove('open');
        document.body.classList.remove('menu-open');
        overlay.classList.remove('active');
        menuToggle.className = 'fas fa-bars';
    }

    // Simple mobile menu link handling
    document.addEventListener('DOMContentLoaded', function () {
        // Just ensure links work on mobile - no complex logic
        const navLinks = document.querySelectorAll('.nav-menu a');

        navLinks.forEach(link => {
            // Force inline styles to make links work
            link.style.pointerEvents = 'auto !important';
            link.style.zIndex = '99999 !important';
            link.style.position = 'relative !important';
            link.style.display = 'block !important';

            // Simple click handler - just close menu on mobile
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    setTimeout(() => closeMobileMenu(), 100);
                }
            });
        });
    });

    function startCountdown() {
        let endTime = localStorage.getItem('dealEndTime');

        if (!endTime) {
            endTime = Date.now() + (13 * 60 * 60 * 1000);
            localStorage.setItem('dealEndTime', endTime);
        }

        function updateTimer() {
            const now = Date.now();
            const timeLeft = endTime - now;

            if (timeLeft <= 0) {
                endTime = Date.now() + (13 * 60 * 60 * 1000);
                localStorage.setItem('dealEndTime', endTime);
            }

            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            document.getElementById('countdown').textContent =
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    document.addEventListener('DOMContentLoaded', startCountdown);

    // Live search functionality
    let searchTimeout;

    function initializeSearch() {
        const searchInput = document.getElementById('searchInput');
        const mobileSearchInput = document.getElementById('mobileSearchInput');
        const searchResults = document.getElementById('searchResults');
        const mobileSearchResults = document.getElementById('mobileSearchResults');

        function performSearch(query, resultsContainer) {
            if (query.length < 2) {
                resultsContainer.classList.remove('show');
                return;
            }

            // Show loading
            resultsContainer.innerHTML = '<div class="search-no-results"><i class="fas fa-spinner fa-spin"></i><br>Szukam...</div>';
            resultsContainer.classList.add('show');

            // Simulate API call (replace with actual search)
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(query, resultsContainer);
            }, 300);
        }

        function searchProducts(query, resultsContainer) {
            // This would normally be an AJAX call to your backend
            // For now, we'll simulate with sample data
            fetch(`search.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(products => {
                    displaySearchResults(products, resultsContainer);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    // Fallback to client-side search if server search fails
                    clientSideSearch(query, resultsContainer);
                });
        }

        function clientSideSearch(query, resultsContainer) {
            // Fallback client-side search (you can populate this with your products)
            const sampleProducts = [
                { id: 1, name: 'iPhone 15 Pro', price: 4999, category: 'Smartfony', image_url: '' },
                { id: 2, name: 'Samsung Galaxy S24', price: 3999, category: 'Smartfony', image_url: '' },
                { id: 3, name: 'MacBook Pro', price: 8999, category: 'Laptopy', image_url: '' },
                { id: 4, name: 'AirPods Pro', price: 999, category: 'Słuchawki', image_url: '' }
            ];

            const filteredProducts = sampleProducts.filter(product =>
                product.name.toLowerCase().includes(query.toLowerCase()) ||
                product.category.toLowerCase().includes(query.toLowerCase())
            );

            displaySearchResults(filteredProducts, resultsContainer);
        }

        function displaySearchResults(products, resultsContainer) {
            if (products.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="search-no-results">
                        <i class="fas fa-search"></i><br>
                        Brak wyników dla "${query}"
                    </div>
                `;
                return;
            }

            const resultsHTML = products.map(product => `
                                        <a href="product.php?slug=${product.slug || 'product-' + product.id}" class="search-result-item">
                    <div class="search-result-image">
                        ${product.image_url ?
                    `<img src="${product.image_url}" alt="${product.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <i class="fas fa-box" style="display: none;"></i>` :
                    `<i class="fas fa-box"></i>`
                }
                    </div>
                    <div class="search-result-info">
                        <div class="search-result-category">${product.category}</div>
                        <div class="search-result-name">${product.name}</div>
                        <div class="search-result-price">${product.price.toFixed(2)} zł</div>
                    </div>
                </a>
            `).join('');

            resultsContainer.innerHTML = resultsHTML;
        }

        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                performSearch(e.target.value, searchResults);
            });

            searchInput.addEventListener('focus', (e) => {
                if (e.target.value.length >= 2) {
                    searchResults.classList.add('show');
                }
            });
        }

        if (mobileSearchInput) {
            mobileSearchInput.addEventListener('input', (e) => {
                performSearch(e.target.value, mobileSearchResults);
            });

            mobileSearchInput.addEventListener('focus', (e) => {
                if (e.target.value.length >= 2) {
                    mobileSearchResults.classList.add('show');
                }
            });
        }

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-bar')) {
                searchResults.classList.remove('show');
                mobileSearchResults.classList.remove('show');
            }
        });
    }

    // Initialize search when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeSearch);

    // Cart functions
    function openCart() {
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('overlay');

        if (cartSidebar) {
            cartSidebar.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        if (cartOverlay) {
            cartOverlay.classList.add('active');
        }
    }

    function closeCart() {
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('overlay');

        if (cartSidebar) {
            cartSidebar.classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        if (cartOverlay) {
            cartOverlay.classList.remove('active');
        }
    }

    // Recent Sales Notification System - REAL PRODUCTS
    function showSalesNotification() {
    console.log('REAL: Showing sales notification...');

    const container = document.getElementById('recentSalesContainer');
    if (!container) {
        console.log('REAL: Container not found!');
        return;
    }

    // Real products from PHP
    const realProducts = <?php echo json_encode($productsForNotifications); ?>;
    let productsToUse;

    if (!realProducts || realProducts.length === 0) {
        console.log('REAL: No products in DB, using fallback test products...');
        productsToUse = [
            { translations: { nl: { name: "iPhone 15 Pro Max" }, fr: { name: "iPhone 15 Pro Max" } }, price: "4.999", image_url: "" },
            { translations: { nl: { name: "Samsung Galaxy S24" }, fr: { name: "Samsung Galaxy S24" } }, price: "3.299", image_url: "" },
            { translations: { nl: { name: "MacBook Air M2" }, fr: { name: "MacBook Air M2" } }, price: "5.499", image_url: "" },
            { translations: { nl: { name: "AirPods Pro" }, fr: { name: "AirPods Pro" } }, price: "899", image_url: "" },
            { translations: { nl: { name: "iPad Air" }, fr: { name: "iPad Air" } }, price: "2.199", image_url: "" }
        ];
    } else {
        console.log('REAL: Found', realProducts.length, 'products in DB');
        productsToUse = realProducts;
    }

    const randomProduct = productsToUse[Math.floor(Math.random() * productsToUse.length)];
    const timeOptions = ["2 min", "5 min", "8 min", "12 min", "15 min", "18 min", "22 min", "25 min"];
    const randomTime = timeOptions[Math.floor(Math.random() * timeOptions.length)];

    // Use current language
    let productName = "";
    if (randomProduct.translations && randomProduct.translations[activeLang]) {
        productName = randomProduct.translations[activeLang].name || "";
    } else {
        productName = randomProduct.name || "Unknown Product";
    }

    const notification = document.createElement('div');
    notification.innerHTML = `
        <div style="
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 250px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            animation: slideIn 0.4s ease-out;
            z-index: 10000;
            position: relative;
            overflow: hidden;
        ">
            <div style="
                width: 36px;
                height: 36px;
                border-radius: 6px;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                flex-shrink: 0;
                border: 2px solid #ffffff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            ">
                ${randomProduct.image_url ?
                    `<img src="${randomProduct.image_url}" alt="${productName}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` :
                    ''
                }
                <div style="
                    display: ${randomProduct.image_url ? 'none' : 'flex'};
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                    width: 100%;
                    height: 100%;
                    color: #dc3545;
                ">
                    🔥
                </div>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="
                    font-size: 10px;
                    color: #ffffff;
                    font-weight: 700;
                    margin-bottom: 3px;
                    text-transform: uppercase;
                    letter-spacing: 0.8px;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                ">
                    ✅ ${activeLang === "nl" ? "VERKOCHT" : "VENDU"} ${randomTime} ${activeLang === "nl" ? "GELEDEN" : "IL Y A"}
                </div>
                <div style="
                    font-size: 12px;
                    font-weight: 700;
                    color: #ffffff;
                    margin-bottom: 2px;
                    line-height: 1.3;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                ">
                    ${productName}
                </div>
                <div style="
                    font-size: 11px;
                    color: #ffffff;
                    font-weight: 600;
                    background: rgba(255, 255, 255, 0.2);
                    padding: 2px 6px;
                    border-radius: 4px;
                    display: inline-block;
                    backdrop-filter: blur(5px);
                ">
                    ${randomProduct.price} €
                </div>
            </div>
            <button onclick="this.parentElement.remove()" style="
                background: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.3);
                color: #ffffff;
                cursor: pointer;
                font-size: 14px;
                padding: 2px;
                border-radius: 4px;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                font-weight: bold;
                backdrop-filter: blur(5px);
            " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                ×
            </button>
        </div>
    `;

    container.appendChild(notification);
    console.log('REAL: Notification added for product:', productName);

    // Remove after 8 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 8000);
}
    // Start notifications - REAL PRODUCTS
    document.addEventListener('DOMContentLoaded', function () {
        console.log('REAL: DOM załadowany, rozpoczynam powiadomienia...'); // Debug log

        // First notification after 3 seconds
        setTimeout(showSalesNotification, 3000);

        // Then every 35 seconds
        setInterval(showSalesNotification, 35000);
    });

    // Add CSS
    const notificationStyle = document.createElement('style');
    notificationStyle.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(notificationStyle);

    // Toon de reserveringsbalk alleen als er producten in de winkelwagen zitten
    const cartHasItems = <?php echo $cartHasItems ? 'true' : 'false'; ?>;

    function showReservationBar() {
        document.getElementById('reservationBar').style.display = 'block';
    }
    function hideReservationBar() {
        document.getElementById('reservationBar').style.display = 'none';
    }

    // Timer logica
    let reservationTimer = null;
    let reservationEnd = null;

    function startReservationTimer(duration) {
        reservationEnd = Date.now() + duration * 1000;
        updateReservationTimer();
        if (reservationTimer) clearInterval(reservationTimer);
        reservationTimer = setInterval(updateReservationTimer, 1000);
    }

    function updateReservationTimer() {
        const now = Date.now();
        const diff = Math.max(0, Math.floor((reservationEnd - now) / 1000));
        const min = String(Math.floor(diff / 60)).padStart(2, '0');
        const sec = String(diff % 60).padStart(2, '0');
        document.getElementById('reservationTimer').textContent = `${min}:${sec}`;
        if (diff <= 0) {
            hideReservationBar();
            clearInterval(reservationTimer);
        }
    }

    // Reset timer bij toevoegen van een product
    function resetReservationOnAddToCart() {
        startReservationTimer(600); // 10 minuten
        showReservationBar();
    }

    // Detecteer toevoegen aan winkelwagen (formulieren)
    document.addEventListener('DOMContentLoaded', function () {
        if (cartHasItems) {
            // Start timer als er al items zijn (bijv. na refresh)
            startReservationTimer(600);
            showReservationBar();
        }
        // Luister naar alle add-to-cart formulieren
        document.querySelectorAll('form[action*="add_to_cart"], .add-to-cart-form').forEach(function (form) {
            form.addEventListener('submit', function () {
                resetReservationOnAddToCart();
            });
        });
    });

    // Verberg de balk als de winkelwagen leeg raakt (AJAX of na verwijderen)
    function checkCartEmpty() {
        // Simpele check: als er geen .cart-item meer is, verberg de balk
        if (document.querySelectorAll('.cart-item').length === 0) {
            hideReservationBar();
            clearInterval(reservationTimer);
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[action*="remove_from_cart"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                setTimeout(checkCartEmpty, 500);
            });
        });
    });
</script>

<?php
// Get current settings
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');
$shop_email = $db->getSetting('shop_email');
$shop_phone = $db->getSetting('shop_phone');
$shop_address = $db->getSetting('shop_address');
$shop_city = $db->getSetting('shop_city');
$shop_postal_code = $db->getSetting('shop_postal_code');
$shop_country = $db->getSetting('shop_country');
$shop_nip = $db->getSetting('shop_nip');
$shop_regon = $db->getSetting('shop_regon');
$shop_krs = $db->getSetting('shop_krs');
$shop_bank_account = $db->getSetting('shop_bank_account');
$shop_bank_name = $db->getSetting('shop_bank_name');
$shop_bank_swift = $db->getSetting('shop_bank_swift');
$shop_bank_iban = $db->getSetting('shop_bank_iban');

// Get real products for notifications
$realProducts = $db->getAllProducts(true);
$productsForNotifications = array_slice($realProducts, 0, 10); // Take first 10 products
?>