<?php
// track-checkout.php - Server-side Facebook CAPI tracking for checkout
session_start();

// Facebook CAPI Configuration
$pixel_id = '698814149641552';
$access_token = 'EAARZApiZBr5JkBPPuozQA0ZBCeuSmlhrAbrPKno8BMNDpXD3o1MeZA6HZCvkksgxtuIZAx2MUR8bJbZCdHEgGM4VMmbHAHKHMU5AGaW5QNeFvenIaj57vZCAenTul1pOnzFZAxcsPf6HIuHiZCHSu599Wi7jWzPxleZAZCdNH9Pk55FX1pwRRMT9frww6FHSfDOptwZDZD';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Extract data
$event = $input['event'] ?? 'InitiateCheckout';
$value = $input['value'] ?? 0;
$currency = $input['currency'] ?? 'EUR';
$items = $input['items'] ?? [];
$customer_email = $input['customer_email'] ?? '';

// Get user data
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$hashed_email = $customer_email ? hash('sha256', strtolower(trim($customer_email))) : null;

// Prepare event data for Facebook CAPI
$event_data = [
    'data' => [[
        'event_name' => $event,
        'event_time' => time(),
        'event_source_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout.php',
        'action_source' => 'website',
        'user_data' => array_filter([
            'em' => $hashed_email ? [$hashed_email] : [],
            'client_ip_address' => $user_ip,
            'client_user_agent' => $user_agent
        ]),
        'custom_data' => [
            'currency' => $currency,
            'value' => $value,
            'num_items' => count($items),
            'contents' => array_map(function($item) {
                return [
                    'id' => $item['item_id'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'item_price' => $item['price'] ?? 0
                ];
            }, $items)
        ]
    ]]
];

// Send to Facebook CAPI
$url = "https://graph.facebook.com/v18.0/{$pixel_id}/events?access_token={$access_token}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    $error = curl_error($ch);
    file_put_contents(__DIR__ . '/fb_capi_log.txt', date('c') . " - CHECKOUT CURL ERROR: " . $error . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'CURL failed', 'details' => $error]);
} else {
    file_put_contents(__DIR__ . '/fb_capi_log.txt', date('c') . " - CHECKOUT: HTTP {$http_code} - " . $response . PHP_EOL, FILE_APPEND);
    
    if ($http_code == 200) {
        echo json_encode(['success' => true, 'response' => $response]);
    } else {
        http_response_code($http_code);
        echo json_encode(['error' => 'API error', 'response' => $response]);
    }
}

curl_close($ch);
?>