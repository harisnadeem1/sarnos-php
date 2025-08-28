<?php
// Set no-referrer policy before any output
header("Referrer-Policy: no-referrer");

session_start();
require_once 'database.php';

// Check if we have a valid cart session
if (!isset($_SESSION['cart_session_id'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$cartItems = $db->getCartItems($_SESSION['cart_session_id']);

if (empty($cartItems)) {
    header('Location: index.php');
    exit();
}

// Build the Shopify cart URL
$shopifyUrl = $db->getSetting('shopify_shop_url');
$cartString = '';

foreach ($cartItems as $item) {
    if ($cartString) $cartString .= ',';
    $cartString .= $item['shopify_variant_id'] . ':' . $item['quantity'];
}

$checkoutUrl = "https://{$shopifyUrl}/cart/{$cartString}";
//$checkoutUrl = "https://httpbin.org/headers";

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <title>Redirecting...</title>
    <script>
        // Instant redirect
        window.location.href = <?php echo json_encode($checkoutUrl); ?>;
    </script>
</head>
<body></body>
</html>
