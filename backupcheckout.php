<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Shopify-Style Checkout Page with Malum Payment Integration
session_start();
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
    echo "<h2>âŒ Error: Cart is empty or invalid</h2>";
    exit;
}

// Prepare items for metadata or additional context (optional)
$items = [];
foreach ($cartItems as $item) {
    $items[] = [
        'product_id' => $item['product_id'],
        'name' => $item['name'],
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
    $redirect_url = "https://malum.co/go2payment?payToken={$transaction_id}&payMethod=przelewy24-direct&redirect=1";
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizacja zamÃ³wienia - Sklepoll</title>
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
            background:#28a745;
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
            background:#28a745;
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
            .checkout-main, .checkout-sidebar {
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

            .item-name, .item-price {
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
        }

        @media (max-width: 480px) {
            .checkout-container {
                max-width: 100%;
                padding: 0 12px;
                margin-top:20px;
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

    </style>
</head>
<body>
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
                PowrÃ³t do koszyka
            </a>

            <?php if (isset($_GET['malum_cancelled'])): ?>
                <div class="error-alert">
                    <i class="fas fa-times-circle"></i>
                    PÅ‚atnoÅ›Ä‡ zostaÅ‚a anulowana. SprÃ³buj ponownie.
                </div>
            <?php endif; ?>

            <form id="checkoutForm" novalidate>
                <h2 class="section-title">Informacje kontaktowe</h2>
                <div class="form-group">
                    <label for="email">Adres e-mail <span class="required-asterisk">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <h2 class="section-title" style="margin-top: 24px;">Adres dostawy</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">ImiÄ™ <span class="required-asterisk">*</span></label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Nazwisko <span class="required-asterisk">*</span></label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="company">Nazwa firmy (opcjonalnie)</label>
                    <input type="text" id="company" name="company">
                </div>

                <div class="form-group">
                    <label for="address">Adres <span class="required-asterisk">*</span></label>
                    <input type="text" id="address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="apartment">Mieszkanie, apartament, itp. (opcjonalnie)</label>
                    <input type="text" id="apartment" name="apartment">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">Miasto <span class="required-asterisk">*</span></label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Kod pocztowy <span class="required-asterisk">*</span></label>
                        <input type="text" id="postal_code" name="postal_code" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Numer telefonu (opcjonalnie)</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <h2 class="section-title" style="margin-top: 24px;">Dostawa</h2>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 500; color: #1a1a1a;">Darmowa dostawa</div>
                            <div style="color: #6b7280; font-size: 13px;">1-3 dni robocze</div>
                        </div>
                        <div style="font-weight: 600; color: #1a1a1a;">Darmowa</div>
                    </div>
                </div>
            </form>
        </div>

        <div class="checkout-sidebar">
            <div class="order-summary">
                <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-box" style="color: #9ca3af; font-size: 24px;"></i>
                            <?php endif; ?>
                            <div class="item-quantity"><?php echo $item['quantity']; ?></div>
                        </div>
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        </div>
                        <div class="item-price">
                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?> zÅ‚
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <div class="price-line">
                    <span>Suma czÄ™Å›ciowa</span>
                    <span><?php echo number_format($cartTotal, 2); ?> zÅ‚</span>
                </div>
                <div class="price-line shipping">
                    <span>Dostawa</span>
                    <span>Darmowa</span>
                </div>
                <div class="price-line total">
                    <span>Razem</span>
                    <span><?php echo number_format($cartTotal, 2); ?> zÅ‚</span>
                </div>
            </div>

            <div class="payment-section">
                <h2 class="section-title">PÅ‚atnoÅ›Ä‡</h2>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label for="przelewy24" style="display: flex; align-items: center; gap: 10px; margin: 0; cursor: pointer;">
                            <img src="https://polska-sklep.com/wp-content/uploads/2025/08/p24_logo.svg" 
                                 alt="Przelewy24" style="height: 24px;">
                            <span style="font-size: 15px; font-weight: 500;">ZapÅ‚aÄ‡ za pomocÄ… Przelewy24</span>
                        </label>
                    </div>
                </div>

                <button id="payNowButton" class="payment-button" disabled
                        onclick="window.location.href='<?php echo htmlspecialchars($redirect_url); ?>'">
                    <i class="fas fa-lock" style="margin-right: 8px;"></i>
                    ZAPÅAÄ† TERAZ
                </button>
            </div>



             <div class="checkout-benefits">
                    <div class="benefit-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Bezpieczne pÅ‚atnoÅ›ci SSL</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-truck"></i>
                        <span>Zawsze darmowa dostawa</span>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-undo"></i>
                        <span>30 dni na zwrot</span>
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
    </script>
</body>
</html>