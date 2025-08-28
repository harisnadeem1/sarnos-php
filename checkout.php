<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Shopify-Style Checkout Page with Malum Payment Integration
session_start();

require_once 'lang.php';

require_once 'database.php';

// Malum configuration
$MALUM_PUBLIC_KEY = "pub_65b958ac44368e9f585e74cdfb77e821689a29afed466776228826";
$MALUM_SECRET_KEY = "sec_bade412d27ebfe79cc47172d62f9d35f689a29afed46d122799367";
$MALUM_MERCHANT_ID = "689A29AFED437";
$BASE_URL = "https://sklepoll.com/";

// Initialize database
$db = Database::getInstance();

// Get cart data
if (isset($_SESSION['cart_session_id'])) {
    $cartItems = $db->getCartItems($_SESSION['cart_session_id']);
    $cartTotal = $db->getCartTotal($_SESSION['cart_session_id']);
} else {
    $cartItems = [];
    $cartTotal = 0;
}

// Check if cart is empty
if (empty($cartItems) || $cartTotal <= 0) {
    echo "<h2>? Error: Cart is empty or invalid</h2>";
    exit;
}

// Prepare items for metadata or additional context (optional)
$items = [];
foreach ($cartItems as $item) {
    $items[] = [
        'product_id' => $item['product_id'],
        'name' => isset($item['current_name']) ? $item['current_name'] : (isset($item['name']) ? $item['name'] : 'Unknown'),
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'total' => $item['price'] * $item['quantity']
    ];
}

// Store cart details in session for sales tracking after payment
$_SESSION['cart_items'] = $cartItems;
$_SESSION['cart_total'] = $cartTotal;

// Optional: If you have a customer email field in checkout form, store it
if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['customer_email'] = $_POST['email'];
} else {
    $_SESSION['customer_email'] = "unknown@example.com";
}


// Prepare payment data
$payment_data = [
    "amount" => round($cartTotal, 2),
    "currency" => "PLN",
    "customer_email" => "customer@example.com",
    "cancel_url" => $BASE_URL . "/cart.php?malum_cancelled=1",
    "success_url" => $BASE_URL . "/payment-success.php",
    "webhook_url" => $BASE_URL . "/malum_webhook.php",
    "buyer_pays_fees" => false,
    "merchant_pays_gw_fees" => true,
    "metadata" => "ORDER_" . time()
];

// Initialize redirect URL
$redirect_url = '';

$auth_header = "{$MALUM_MERCHANT_ID}:{$MALUM_SECRET_KEY}";
$ch = curl_init("https://malum.co/api/v2/payment/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "MALUM: {$auth_header}"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result && isset($result["status"]) && $result["status"] === "success") {
    $transaction_id = $result["transaction_id"];
    $redirect_url = "https://malum.co/go2payment?payToken={$transaction_id}&payMethod=przelewy24-direct&redirect=1&instant=1";
}

?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizacja zam�wienia - Sklepoll</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #ffffffff;
            line-height: 1.6;
            color: #1a1a1a;
        }

        .header {
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 16px 24px;
            /* position: sticky; */
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            height: 100px;
            object-fit: contain;
        }

        .progress-bar {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin: 24px 0;
            font-size: 14px;
            color: #6b7280;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-step.active {
            color: #1a1a1a;
            font-weight: 600;
        }

        .progress-step i {
            font-size: 16px;
        }

        .checkout-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 500px;
            gap: 24px;
            padding: 24px;
            margin-top: 30px;
        }

        .checkout-main {
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            /* box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); */
        }

        .checkout-sidebar {
            background: #ffffffff;
            padding: 32px;
            border-radius: 12px;
            /* box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); */
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
        }

        .required-asterisk {
            color: #dc2626;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #000000ff;
            box-shadow: 0 0 0 3px rgba(99, 99, 99, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-size: 13px;
            line-height: 1.4;
            cursor: pointer;
        }

        .order-summary {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 64px;
            height: 64px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            position: relative;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-quantity {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #4b5563;
            color: #ffffff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .item-price {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
        }

        .price-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            font-size: 14px;
        }

        .price-line.subtotal {
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .price-line.shipping {
            color: #6b7280;
        }

        .price-line.total {
            background: #ffffffff;
            font-weight: 600;
            font-size: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .payment-button {
            width: 100%;
            background: #28a745;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 24px;
            touch-action: manipulation;
        }

        .payment-button:hover:not(:disabled) {
            background: #28a745;
        }

        .payment-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .security-badges {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .security-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            font-size: 12px;
        }

        .back-to-cart {
            color: black;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 24px;
        }

        .back-to-cart:hover {
            text-decoration: underline;
        }

        .error-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkout-benefits {
            margin-top: 20px;
            padding: 20px 15px;
            background: white;
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

        /* LOADING SCREEN STYLES - ONLY ADDITION */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.active {
            display: flex;
            opacity: 1;
        }

        .loading-content {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 90%;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e5e7eb;
            border-left-color: #28a745;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 12px;
        }

        .loading-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .loading-steps {
            text-align: left;
            margin: 20px 0;
        }

        .loading-step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            font-size: 14px;
            color: #6b7280;
            transition: color 0.3s ease;
        }

        .loading-step.active {
            color: #28a745;
            font-weight: 500;
        }

        .loading-step i {
            width: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .loading-step.active i {
            transform: scale(1.1);
        }

        .loading-security {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6b7280;
            font-size: 13px;
        }

        .loading-bank-logo {
            margin: 20px 0;
            opacity: 0.7;
        }

        .loading-bank-logo img {
            height: 32px;
        }

        .loading-progress {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin: 20px 0;
            overflow: hidden;
        }

        .loading-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            width: 0%;
            border-radius: 2px;
            animation: progressFill 3s ease-in-out infinite;
        }

        @keyframes progressFill {
            0% {
                width: 0%;
            }

            50% {
                width: 70%;
            }

            100% {
                width: 100%;
            }
        }

        @media (max-width: 1000px) {
            .checkout-container {
                grid-template-columns: 1fr;
                max-width: 600px;
                padding: 16px;
            }

            .checkout-main {
                order: 1;
                padding: 24px;
            }

            .checkout-sidebar {
                order: 2;
                padding: 24px;
            }

            .header-container {
                padding: 0 16px;
            }
        }

        @media (max-width: 768px) {

            .checkout-main,
            .checkout-sidebar {
                padding: 16px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            input {
                padding: 12px 16px;
                font-size: 16px;
                min-height: 48px;
            }

            .payment-button {
                padding: 14px;
                font-size: 16px;
                min-height: 52px;
            }

            .section-title {
                font-size: 18px;
            }

            .progress-bar {
                font-size: 13px;
            }

            .back-to-cart {
                font-size: 13px;
            }

            .item-image {
                width: 56px;
                height: 56px;
            }

            .item-quantity {
                width: 20px;
                height: 20px;
                font-size: 11px;
            }

            .item-name,
            .item-price {
                font-size: 13px;
            }

            .price-line {
                font-size: 13px;
            }

            .price-line.total {
                font-size: 19px;
            }

            .security-badge {
                font-size: 11px;
            }

            .loading-content {
                padding: 30px 20px;
            }

            .loading-title {
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .checkout-container {
                max-width: 100%;
                padding: 0 12px;
                margin-top: 20px;
            }

            input {
                font-size: 15px;
                min-height: 46px;
            }

            .payment-button {
                font-size: 15px;
                min-height: 48px;
            }

            .header-container {
                padding: 0 12px;
            }

            .logo img {
                height: 100px;
            }

            .loading-content {
                padding: 25px 15px;
            }
        }
    </style>
</head>

<body>

    <!-- LOADING OVERLAY - ONLY ADDITION -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>

            <div class="loading-title">
                <?php echo $texts['checkout']['loading']['title']; ?>
            </div>

            <div class="loading-subtitle">
                <?php echo $texts['checkout']['loading']['subtitle']; ?>
            </div>

            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>

            <div class="loading-steps">
                <div class="loading-step active" id="step1">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $texts['checkout']['loading']['steps']['step1']; ?></span>
                </div>
                <div class="loading-step" id="step2">
                    <i class="fas fa-shield-alt"></i>
                    <span><?php echo $texts['checkout']['loading']['steps']['step2']; ?></span>
                </div>
                <div class="loading-step" id="step3">
                    <i class="fas fa-credit-card"></i>
                    <span><?php echo $texts['checkout']['loading']['steps']['step3']; ?></span>
                </div>
            </div>

            <div class="loading-bank-logo">
                <img src="https://polska-sklep.com/wp-content/uploads/2025/08/p24_logo.svg" alt="Przelewy24">
            </div>

            <div class="loading-security">
                <i class="fas fa-lock"></i>
                <span><?php echo $texts['checkout']['loading']['security']; ?></span>
            </div>
        </div>
    </div>


    <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="logo.jpeg" alt="Sklepoll Logo">
                </a>
            </div>
        </div>
    </header>

    <div class="checkout-container">
        <div class="checkout-main">
            <a href="index.php" class="back-to-cart">
                <i class="fas fa-chevron-left"></i>
                <?php echo $texts['checkout']['main']['back_to_cart']; ?>
            </a>

            <?php if (isset($_GET['malum_cancelled'])): ?>
                <div class="error-alert">
                    <i class="fas fa-times-circle"></i>
                    <?php echo $texts['checkout']['main']['cancelled']; ?>
                </div>
            <?php endif; ?>

            <form id="checkoutForm" novalidate>
                <h2 class="section-title">
                    <?php echo $texts['checkout']['main']['contact_info']; ?>
                </h2>

                <div class="form-group">
                    <label for="email">
                        <?php echo $texts['checkout']['main']['fields']['email']; ?>
                        <span class="required-asterisk"><?php echo $texts['checkout']['main']['required']; ?></span>
                    </label>
                    <input type="email" id="email" name="email" required>
                </div>

                <h2 class="section-title" style="margin-top: 24px;">
                    <?php echo $texts['checkout']['main']['delivery_address']; ?>
                </h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">
                            <?php echo $texts['checkout']['main']['fields']['first_name']; ?>
                            <span class="required-asterisk"><?php echo $texts['checkout']['main']['required']; ?></span>
                        </label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">
                            <?php echo $texts['checkout']['main']['fields']['last_name']; ?>
                            <span class="required-asterisk"><?php echo $texts['checkout']['main']['required']; ?></span>
                        </label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="company">
                        <?php echo $texts['checkout']['main']['fields']['company']; ?>
                    </label>
                    <input type="text" id="company" name="company">
                </div>

                <div class="form-group">
                    <label for="address">
                        <?php echo $texts['checkout']['main']['fields']['address']; ?>
                        <span class="required-asterisk"><?php echo $texts['checkout']['main']['required']; ?></span>
                    </label>
                    <input type="text" id="address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="apartment">
                        <?php echo $texts['checkout']['main']['fields']['apartment']; ?>
                    </label>
                    <input type="text" id="apartment" name="apartment">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">
                            <?php echo $texts['checkout']['main']['fields']['city']; ?>
                            <span class="required-asterisk"><?php echo $texts['checkout']['main']['required']; ?></span>
                        </label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">
                            <?php echo $texts['checkout']['main']['fields']['postal_code']; ?>
                            <span class="required-asterisk"><?php echo $texts['checkout']['main']['required']; ?></span>
                        </label>
                        <input type="text" id="postal_code" name="postal_code" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">
                        <?php echo $texts['checkout']['main']['fields']['phone']; ?>
                    </label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <h2 class="section-title" style="margin-top: 24px;">
                    <?php echo $texts['checkout']['main']['shipping']['title']; ?>
                </h2>

                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 500; color: #1a1a1a;">
                                <?php echo $texts['checkout']['main']['shipping']['free_delivery']; ?>
                            </div>
                            <div style="color: #6b7280; font-size: 13px;">
                                <?php echo $texts['checkout']['main']['shipping']['days']; ?>
                            </div>
                        </div>
                        <div style="font-weight: 600; color: #1a1a1a;">
                            <?php echo $texts['checkout']['main']['shipping']['price']; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <div class="checkout-sidebar">
    <div class="order-summary">
        <?php foreach ($cartItems as $item): ?>
            <div class="order-item">
                <div class="item-image">
                    <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($item['current_name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-box" style="color: #9ca3af; font-size: 24px;"></i>
                    <?php endif; ?>
                    <div class="item-quantity"><?php echo $item['quantity']; ?></div>
                </div>
                <div class="item-details">
                    <div class="item-name"><?php echo htmlspecialchars($item['current_name']); ?></div>
                </div>
                <div class="item-price">
                    <?php echo number_format($item['price'] * $item['quantity'], 2); ?> zł
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="order-summary">
        <div class="price-line">
            <span><?php echo $texts['checkout']['summary']['subtotal']; ?></span>
            <span><?php echo number_format($cartTotal, 2); ?> €</span>
        </div>
        <div class="price-line shipping">
            <span><?php echo $texts['checkout']['summary']['shipping']; ?></span>
            <span><?php echo $texts['checkout']['summary']['free']; ?></span>
        </div>
        <div class="price-line total">
            <span><?php echo $texts['checkout']['summary']['total']; ?></span>
            <span><?php echo number_format($cartTotal, 2); ?> €</span>
        </div>
    </div>

    <div class="payment-section">
        <h2 class="section-title">
            <?php echo $texts['checkout']['payment']['title']; ?>
        </h2>
        <div
            style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="przelewy24"
                       style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer;">
                    <img src="https://polska-sklep.com/wp-content/uploads/2025/08/p24_logo.svg" alt="Przelewy24"
                         style="height: 24px;">
                    <span style="font-size: 15px; font-weight: 500;">
                        <?php echo $texts['checkout']['payment']['method']; ?>
                    </span>
                </label>
            </div>
        </div>

        <button id="payNowButton" class="payment-button" disabled
                onclick="showLoadingScreen(); setTimeout(() => { window.location.href='<?php echo htmlspecialchars($redirect_url); ?>'; }, 200);">
            <i class="fas fa-lock" style="margin-right: 8px;"></i>
            <?php echo $texts['checkout']['payment']['button']; ?>
        </button>
    </div>

    <div class="checkout-benefits">
        <div class="benefit-item">
            <i class="fas fa-shield-alt"></i>
            <span><?php echo $texts['checkout']['benefits']['secure']; ?></span>
        </div>
        <div class="benefit-item">
            <i class="fas fa-truck"></i>
            <span><?php echo $texts['checkout']['benefits']['shipping']; ?></span>
        </div>
        <div class="benefit-item">
            <i class="fas fa-undo"></i>
            <span><?php echo $texts['checkout']['benefits']['returns']; ?></span>
        </div>
    </div>
</div>

    </div>

    <script>
        // Fast function to show loading screen and redirect quickly
        function showLoadingScreen() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');

            // Show loading overlay immediately
            loadingOverlay.classList.add('active');

            // Much faster animations - redirect in 500ms total
            setTimeout(() => {
                step1.classList.remove('active');
                step2.classList.add('active');
            }, 25);

            setTimeout(() => {
                step2.classList.remove('active');
                step3.classList.add('active');
            }, 50);
        }

        // Your original functions remain unchanged
        function checkRequiredFields() {
            const requiredFields = ['email', 'first_name', 'last_name', 'address', 'city', 'postal_code'];
            const payNowButton = document.getElementById('payNowButton');
            const allFilled = requiredFields.every(field => {
                const input = document.getElementById(field);
                return input.value.trim() !== '';
            });
            payNowButton.disabled = !allFilled;
        }

        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('input', checkRequiredFields);
        });

        // Initial check
        checkRequiredFields();
    </script>
</body>

</html>