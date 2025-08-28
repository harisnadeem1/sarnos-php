<?php
session_start();

// ----- FACEBOOK CAPI CONFIG -----
$pixel_id = '698814149641552';
$access_token = 'EAARZApiZBr5JkBPPuozQA0ZBCeuSmlhrAbrPKno8BMNDpXD3o1MeZA6HZCvkksgxtuIZAx2MUR8bJbZCdHEgGM4VMmbHAHKHMU5AGaW5QNeFvenIaj57vZCAenTul1pOnzFZAxcsPf6HIuHiZCHSu599Wi7jWzPxleZAZCdNH9Pk55FX1pwRRMT9frww6FHSfDOptwZDZD';

// ----- STORE CART DATA BEFORE CLEARING -----
$cartTotal   = isset($_SESSION['cart_total']) ? $_SESSION['cart_total'] : 0;
$currency    = 'PLN';
$cartItems   = isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : [];
$user_email  = isset($_SESSION['customer_email']) ? $_SESSION['customer_email'] : null;
$user_ip     = $_SERVER['REMOTE_ADDR'];
$hashed_email = $user_email ? hash('sha256', strtolower(trim($user_email))) : null;

// ----- LOG SALE TO sales.json -----
$salesFile = __DIR__ . '/sales.json';
$sales     = file_exists($salesFile) ? json_decode(file_get_contents($salesFile), true) : [];

foreach ($cartItems as $item) {
    $productName = $item['name'] 
        ?? ($item['current_name'] ?? 'Unknown Product');

    $sales[] = [
        'product_id' => $item['product_id'] ?? 0,
        'name'       => $productName,
        'price'      => $item['price'] ?? 0,
        'quantity'   => $item['quantity'] ?? 0,
        'total'      => ($item['price'] ?? 0) * ($item['quantity'] ?? 0),
        'date'       => date('Y-m-d H:i:s')
    ];
}

file_put_contents($salesFile, json_encode($sales, JSON_PRETTY_PRINT));

// ----- PREPARE EVENT DATA -----
$event_data = [
    'data' => [[
        'event_name' => 'Purchase',
        'event_time' => time(),
        'event_source_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'action_source' => 'website',
        'user_data' => [
            'em' => $hashed_email ? [$hashed_email] : [],
            'client_ip_address' => $user_ip
        ],
        'custom_data' => [
            'currency' => $currency,
            'value'    => $cartTotal
        ]
    ]]
];

// ----- SEND TO FACEBOOK CAPI -----
$url = "https://graph.facebook.com/v18.0/{$pixel_id}/events?access_token={$access_token}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    file_put_contents(__DIR__ . '/fb_capi_log.txt', date('c') . " - CURL ERROR: " . $error . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/fb_capi_log.txt', date('c') . " - " . $response . PHP_EOL, FILE_APPEND);
}

curl_close($ch);

// ----- CLEAR CART AFTER SUCCESSFUL PURCHASE -----
if (isset($_SESSION['cart_session_id'])) {
    require_once 'database.php';
    $db = Database::getInstance();
    $db->clearCart($_SESSION['cart_session_id']); // Clears cart from cart.json
    unset($_SESSION['cart_session_id']);
}

unset($_SESSION['cart_items']);
unset($_SESSION['cart_total']);
unset($_SESSION['customer_email']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Payment Successful - Thank You!</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Segoe UI', Roboto, sans-serif;
        background: #f4f6f8;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .success-container {
        background: white;
        padding: 40px;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .success-container img {
        height: 80px;
        margin-bottom: 20px;
        max-width: 100%;
    }
    h1 {
        color: #28a745;
        font-size: 28px;
        margin-bottom: 10px;
    }
    p {
        font-size: 16px;
        color: #555;
        margin-bottom: 25px;
    }
    .order-amount {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 25px;
        color: #333;
    }
    .back-btn {
        display: inline-block;
        padding: 12px 25px;
        background: #007bff;
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-size: 16px;
        transition: background 0.2s ease;
    }
    .back-btn:hover {
        background: #0056b3;
    }
    /* Mobile responsiveness */
    @media (max-width: 480px) {
        .success-container {
            padding: 20px;
        }
        h1 {
            font-size: 22px;
        }
        p {
            font-size: 14px;
        }
        .order-amount {
            font-size: 16px;
        }
        .back-btn {
            font-size: 14px;
            padding: 10px 20px;
        }
    }
</style>
</head>
<body>

<div class="success-container">
    <img src="logo.jpeg" alt="Logo">
    <h1>✅ Thank You for Your Purchase!</h1>
    <p>Your order has been successfully received and is now being processed.</p>
    <div class="order-amount">
        Order Total: <?= number_format($cartTotal, 2) ?> <?= $currency ?>
    </div>
    <a href="index.php" class="back-btn">Continue Shopping</a>
</div>

<!-- Facebook Pixel Base Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod ?
n.callMethod.apply(n,arguments) : n.queue.push(arguments)};
if(!f._fbq)f._fbq=n; n.push=n; n.loaded=!0; n.version='2.0';
n.queue=[]; t=b.createElement(e); t.async=!0;
t.src=v; s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');

fbq('init', '<?php echo $pixel_id; ?>');
fbq('track', 'Purchase', {
    value: <?php echo json_encode($cartTotal); ?>,
    currency: '<?php echo $currency; ?>'
});
</script>

</body>
</html>
