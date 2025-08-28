<?php
$pixel_id = '698814149641552';
$access_token = 'EAARZApiZBr5JkBPPuozQA0ZBCeuSmlhrAbrPKno8BMNDpXD3o1MeZA6HZCvkksgxtuIZAx2MUR8bJbZCdHEgGM4VMmbHAHKHMU5AGaW5QNeFvenIaj57vZCAenTul1pOnzFZAxcsPf6HIuHiZCHSu599Wi7jWzPxleZAZCdNH9Pk55FX1pwRRMT9frww6FHSfDOptwZDZD';

$input = json_decode(file_get_contents('php://input'), true);

$product_id = $input['product_id'] ?? '';
$product_name = $input['product_name'] ?? '';
$price = $input['price'] ?? 0;
$currency = $input['currency'] ?? 'PLN';

$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$event_data = [
    'data' => [[
        'event_name' => 'AddToCart',
        'event_time' => time(),
        'action_source' => 'website',
        'user_data' => [
            'client_ip_address' => $user_ip,
            'client_user_agent' => $user_agent
        ],
        'custom_data' => [
            'content_ids' => [$product_id],
            'content_name' => $product_name,
            'currency' => $currency,
            'value' => $price
        ]
    ]]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v18.0/{$pixel_id}/events?access_token={$access_token}");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Log to file
file_put_contents(__DIR__ . '/fb_capi_log.txt', date('c') . " - ATC: " . $response . PHP_EOL, FILE_APPEND);

echo json_encode(['status' => 'ok']);
