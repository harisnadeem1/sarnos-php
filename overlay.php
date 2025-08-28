<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przekierowanie do banku...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .redirect-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .bank-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; }
        }

        .redirect-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .redirect-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .progress-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 30px 0;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            animation: dotAnimation 1.5s ease-in-out infinite;
        }

        .dot:nth-child(2) { animation-delay: 0.3s; }
        .dot:nth-child(3) { animation-delay: 0.6s; }

        @keyframes dotAnimation {
            0%, 60%, 100% { transform: scale(1); opacity: 0.4; }
            30% { transform: scale(1.4); opacity: 1; }
        }

        .security-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .security-icon {
            font-size: 24px;
            color: #4ade80;
        }

        .bank-logos {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            opacity: 0.8;
        }

        .bank-logo {
            width: 60px;
            height: 40px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            animation: float 3s ease-in-out infinite;
        }

        .bank-logo:nth-child(2) { animation-delay: 0.5s; }
        .bank-logo:nth-child(3) { animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .redirect-footer {
            margin-top: 40px;
            font-size: 12px;
            opacity: 0.7;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .redirect-container {
                padding: 40px 20px;
            }
            
            .redirect-title {
                font-size: 24px;
            }
            
            .bank-logos {
                gap: 10px;
            }
            
            .bank-logo {
                width: 50px;
                height: 35px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="bank-icon">
            🏦
        </div>
        
        <h1 class="redirect-title">Przekierowywanie do banku</h1>
        <p class="redirect-subtitle">
            Łączymy Cię z bezpieczną stroną Twojego banku.<br>
            Za chwilę zostaniesz przekierowany do systemu płatności.
        </p>

        <div class="progress-dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>

        <div class="bank-logos">
            <div class="bank-logo">PKO</div>
            <div class="bank-logo">mBank</div>
            <div class="bank-logo">ING</div>
        </div>

        <div class="security-info">
            <div class="security-icon">🔒</div>
            <div>
                <strong>Bezpieczne połączenie</strong><br>
                Wszystkie dane są szyfrowane i chronione
            </div>
        </div>

        <div class="redirect-footer">
            Jeśli przekierowanie nie nastąpi automatycznie, 
            <a href="#" onclick="window.location.reload()" style="color: #4ade80;">kliknij tutaj</a>
        </div>
    </div>

    <script>
        // Auto-detect if we're on a payment processing page
        const currentUrl = window.location.href;
        
        // Check if we're on registryo or similar payment processing URL
        if (currentUrl.includes('registryo.com') || 
            currentUrl.includes('stripe') || 
            currentUrl.includes('przelewy24')) {
            
            // Show this overlay for payment processing pages
            document.body.style.display = 'flex';
            
            // Optional: Auto-refresh if stuck (safety net)
            setTimeout(() => {
                if (window.location.href === currentUrl) {
                    window.location.reload();
                }
            }, 10000); // Refresh after 10 seconds if still on same page
        }

        // Add some dynamic text updates
        const messages = [
            'Łączymy Cię z bezpieczną stroną Twojego banku...',
            'Przygotowywanie sesji płatności...',
            'Przekierowywanie do systemu bankowego...',
            'Prawie gotowe, za chwilę będziesz w banku...'
        ];

        let messageIndex = 0;
        const subtitle = document.querySelector('.redirect-subtitle');
        
        setInterval(() => {
            messageIndex = (messageIndex + 1) % messages.length;
            subtitle.innerHTML = messages[messageIndex] + '<br>Proszę czekać, nie zamykaj tej strony.';
        }, 2000);
    </script>
</body>
</html>