<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'database.php';

// Malum config
$MALUM_SECRET_KEY = "sec_bade412d27ebfe79cc47172d62f9d35f689a29afed46d122799367";
$MALUM_MERCHANT_ID = "689A29AFED437";
$BASE_URL = "https://sklepoll.com/";

// Get cart from session
$cartTotal = $_SESSION['cart_total'] ?? 0;
$cartItems = $_SESSION['cart_items'] ?? [];

if ($cartTotal <= 0 || empty($cartItems)) {
    echo json_encode(["status" => "error", "message" => "Koszyk pusty"]);
    exit;
}

// Get email from form
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : "unknown@example.com";

// Prepare payment data
$payment_data = [
    "amount" => round($cartTotal, 2),
    "currency" => "PLN",
    "customer_email" => $email,
    "cancel_url" => $BASE_URL . "cart.php?malum_cancelled=1",
    "success_url" => $BASE_URL . "payment-success.php",
    "webhook_url" => $BASE_URL . "malum_webhook.php",
    "buyer_pays_fees" => false,
    "merchant_pays_gw_fees" => true,
    "metadata" => "ORDER_" . time()
];

// Call Malum API
$auth_header = "{$MALUM_MERCHANT_ID}:{$MALUM_SECRET_KEY}";
$ch = curl_init("https://malum.co/api/v2/payment/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "MALUM: {$auth_header}"   // ✅ correct header
]);

// 🚨 Local testing only – bypass SSL validation
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["status" => "error", "message" => "cURL error: " . curl_error($ch)]);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);

// Debug raw response if API fails
if (!$result || !isset($result["status"]) || $result["status"] !== "success") {
    echo json_encode([
        "status" => "error",
        "message" => "API error",
        "response" => $response
    ]);
    exit;
}

// Success – return redirect URL
$transaction_id = $result["transaction_id"];
echo json_encode([
    "status" => "success",
    "redirect_url" => "https://malum.co/go2payment?payToken={$transaction_id}&payMethod=przelewy24-direct&redirect=1"
]);
