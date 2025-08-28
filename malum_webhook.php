<?php
$private_key = "sec_bade412d27ebfe79cc47172d62f9d35f689a29afed46d122799367";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid payload');
}

$expected_signature = hash_hmac('sha256', implode('|', [
    $data['business_id'],
    $data['order_id'],
    $data['amount'],
    $data['currency'],
    $data['status']
]), $private_key);

if ($expected_signature === $data['signature'] && $data['status'] === 'paid') {
    file_put_contents(__DIR__ . "/payments_log.txt", json_encode($data) . PHP_EOL, FILE_APPEND);
    http_response_code(200);
} else {
    http_response_code(400);
}
