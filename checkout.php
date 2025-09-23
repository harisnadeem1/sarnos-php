<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Shopify-Style Checkout Page with Bunq Payment Integration
session_start();

require_once 'lang.php';
require_once 'database.php';

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

// Store cart details in session for sales tracking after payment
$_SESSION['cart_items'] = $cartItems;
$_SESSION['cart_total'] = $cartTotal;

// Handle form submission and redirect to Bunq
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store customer information in session
    $_SESSION['customer_info'] = [
        'email' => $_POST['email'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'company' => $_POST['company'] ?? '',
        'address' => $_POST['address'] ?? '',
        'apartment' => $_POST['apartment'] ?? '',
        'city' => $_POST['city'] ?? '',
        'postal_code' => $_POST['postal_code'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];
    
    // Optional: Save order to database with pending status
    // You can add order creation logic here
    
    // Create Bunq payment URL
    $bunq_amount = number_format($cartTotal, 2, '.', '');
    $bunq_url = "https://bunq.me/NTaflan/" . $bunq_amount;
    
    // Redirect to Bunq payment
    header("Location: " . $bunq_url);
    exit;
}

?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizacja zamówienia - Sklepoll</title>
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
        }

        .checkout-sidebar {
            background: #ffffffff;
            padding: 32px;
            border-radius: 12px;
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
            background: #218838;
        }

        .payment-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
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

        .bunq-payment-info {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .bunq-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .bunq-logo img {
            height: 24px;
        }

        .bunq-info-text {
            font-size: 13px;
            color: #0369a1;
            line-height: 1.4;
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
        }
    </style>
</head>

<body>
   <header class="header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="<?php echo $texts['checkout']['header']['logo']; ?>" alt="<?php echo $texts['checkout']['header']['logo_alt']; ?>">
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

            <form id="checkoutForm" method="POST" novalidate>
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
                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> €
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
                
                <div class="bunq-payment-info">
                    <div class="bunq-logo">
                        <i class="fas fa-university" style="color: #0ea5e9;"></i>
                        <span style="font-weight: 600; color: #0369a1;"><?php echo $texts['checkout']['payment']['title']; ?></span>
                    </div>
                    <div class="bunq-info-text">
                        <?php echo $texts['checkout']['payment']['method']; ?>
                    </div>
                </div>

                <button id="payNowButton" class="payment-button" type="submit" disabled>
                    <i class="fas fa-lock" style="margin-right: 8px;"></i>
                    <?php echo $texts['checkout']['payment']['button']; ?> €<?php echo number_format($cartTotal, 2); ?>
                </button>
            </div>
            </form>

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

        // Handle form submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            // Form will be submitted normally to redirect to Bunq
            // You can add additional validation here if needed
        });
    </script>
</body>

</html>