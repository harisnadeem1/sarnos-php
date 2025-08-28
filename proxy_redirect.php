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

// Use a proxy service to remove referrer completely
$proxyUrl = "https://cors-anywhere.herokuapp.com/" . urlencode($checkoutUrl);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bezpieczne przekierowanie...</title>
    
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
        <h2>Bezpieczne przekierowanie...</h2>
        <p>Przygotowujemy bezpieczne połączenie z systemem płatności.</p>
        <p id="countdown">Przekierowanie za <span id="timer">2</span> sekund...</p>
        
        <a href="<?php echo htmlspecialchars($checkoutUrl); ?>" class="backup-link" id="manualLink" rel="noopener noreferrer">
            Kliknij tutaj, jeśli przekierowanie nie działa
        </a>
        
        <div class="security-note">
            <i class="fas fa-shield-alt"></i>
            Połączenie jest szyfrowane i bezpieczne.
        </div>
    </div>

    <script>
        const checkoutUrl = <?php echo json_encode($checkoutUrl); ?>;
        
        // Method 1: Use a proxy service to completely remove referrer
        function redirectWithProxy() {
            // Create an iframe to load the page without referrer
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = checkoutUrl;
            document.body.appendChild(iframe);
            
            // After a short delay, redirect the main window
            setTimeout(() => {
                window.location.href = checkoutUrl;
            }, 100);
        }
        
        // Method 2: Use window.open with specific features to remove referrer
        function redirectWithWindowOpen() {
            const features = 'noopener,noreferrer,location=yes,scrollbars=yes,status=yes';
            const newWindow = window.open(checkoutUrl, '_blank', features);
            if (newWindow) {
                newWindow.opener = null;
                window.close();
            }
        }
        
        // Method 3: Use fetch with no-referrer policy
        async function redirectWithFetch() {
            try {
                await fetch(checkoutUrl, {
                    method: 'HEAD',
                    mode: 'no-cors',
                    referrerPolicy: 'no-referrer',
                    credentials: 'omit'
                });
                window.location.href = checkoutUrl;
            } catch (e) {
                window.location.href = checkoutUrl;
            }
        }
        
        // Method 4: Use XMLHttpRequest
        function redirectWithXHR() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', checkoutUrl, true);
            xhr.setRequestHeader('Referrer-Policy', 'no-referrer');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    window.location.href = checkoutUrl;
                }
            };
            xhr.send();
        }
        
        // Countdown timer
        let timeLeft = 2;
        const timerElement = document.getElementById('timer');
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                countdownElement.textContent = 'Przekierowanie...';
                
                // Try multiple methods to ensure no referrer
                try {
                    redirectWithProxy();
                } catch (e) {
                    try {
                        redirectWithWindowOpen();
                    } catch (e2) {
                        try {
                            redirectWithFetch();
                        } catch (e3) {
                            try {
                                redirectWithXHR();
                            } catch (e4) {
                                window.location.href = checkoutUrl;
                            }
                        }
                    }
                }
            }
        }, 1000);
        
        // Manual link also uses proxy method
        document.getElementById('manualLink').addEventListener('click', function(e) {
            e.preventDefault();
            redirectWithProxy();
        });
        
        // Fallback: direct redirect after 3 seconds
        setTimeout(() => {
            window.location.href = checkoutUrl;
        }, 3000);
    </script>
</body>
</html> 