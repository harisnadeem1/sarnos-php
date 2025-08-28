<?php
session_start();
require_once 'database.php';

// Check if we have a valid cart session
if (!isset($_SESSION['cart_session_id'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();

// Get cart items
$cartItems = $db->getCartItems($_SESSION['cart_session_id']);

if (empty($cartItems)) {
    header('Location: index.php');
    exit();
}

// Build Shopify URL
$shopifyUrl = $db->getSetting('shopify_shop_url');
$cartString = '';

foreach ($cartItems as $item) {
    if ($cartString) $cartString .= ',';
    $cartString .= $item['shopify_variant_id'] . ':' . $item['quantity'];
}

$checkoutUrl = "https://{$shopifyUrl}/cart/{$cartString}";

// Server-side proxy to remove referrer completely
if (isset($_GET['redirect']) && $_GET['redirect'] === '1') {
    // Set headers to remove referrer
    header('Referrer-Policy: no-referrer');
    
    // Use cURL to make the request without referrer
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $checkoutUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    // Remove referrer completely
    curl_setopt($ch, CURLOPT_REFERER, '');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // If successful, redirect to Shopify
    if ($httpCode >= 200 && $httpCode < 400) {
        header('Location: ' . $checkoutUrl);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bezpieczne połączenie...</title>
    
    <!-- CRITICAL: Remove referrer completely -->
    <meta name="referrer" content="no-referrer">
    <meta name="referrer" content="no-referrer-when-downgrade">
    <meta name="referrer" content="unsafe-url">
    
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .redirect-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .backup-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .backup-link:hover {
            background: #5a6fd8;
        }
        
        .security-note {
            font-size: 12px;
            color: #888;
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="spinner"></div>
        <h2>Bezpieczne połączenie...</h2>
        <p>Nawiązujemy bezpieczne połączenie z systemem płatności.</p>
        <p id="countdown">Przekierowanie za <span id="timer">1</span> sekund...</p>
        
        <a href="?redirect=1" class="backup-link" id="manualLink">
            Kliknij tutaj, jeśli przekierowanie nie działa
        </a>
        
        <div class="security-note">
            <i class="fas fa-shield-alt"></i>
            Połączenie jest szyfrowane i bezpieczne.
        </div>
    </div>

    <script>
        // Countdown timer
        let timeLeft = 1;
        const timerElement = document.getElementById('timer');
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                countdownElement.textContent = 'Przekierowanie...';
                
                // Use server-side proxy to remove referrer
                window.location.href = '?redirect=1';
            }
        }, 1000);
        
        // Manual link also uses server proxy
        document.getElementById('manualLink').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '?redirect=1';
        });
        
        // Fallback: direct redirect after 2 seconds
        setTimeout(() => {
            window.location.href = '?redirect=1';
        }, 2000);
    </script>
</body>
</html> 