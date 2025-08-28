// Payment Page Interceptor - Add this script to your checkout page
// This will automatically show a beautiful "Redirecting to bank" overlay on payment processing pages

(function() {
    'use strict';
    
    // Check if we're ONLY on external payment processing pages (not our own checkout)
    function isPaymentProcessingPage() {
        const url = window.location.href.toLowerCase();
        const hostname = window.location.hostname.toLowerCase();
        
        // Only trigger on external payment pages, NOT our own site
        return (
            url.includes('registryo.com') ||
            (url.includes('stripe') && !hostname.includes('sklepoll.com')) ||
            hostname.includes('przelewy24.com') ||
            hostname.includes('payu.com') ||
            hostname.includes('dotpay.pl')
        );
    }
    
    // Create and inject the beautiful bank redirect overlay
    function showBankRedirectOverlay() {
        // Create overlay HTML
        const overlayHTML = `
        <div id="bankRedirectOverlay" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            animation: fadeIn 0.5s ease-out;
        ">
            <style>
                @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
                @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
                @keyframes spin { to { transform: rotate(360deg); } }
                @keyframes progressFill { 0% { width: 0%; } 100% { width: 100%; } }
                @keyframes bounce { 0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; } 40% { transform: scale(1); opacity: 1; } }
            </style>
            
            <div style="
                background: white;
                border-radius: 20px;
                padding: 50px 40px;
                text-align: center;
                box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 90%;
                animation: slideUp 0.8s ease-out;
            ">
                <div style="
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 30px;
                    background: linear-gradient(135deg, #28a745, #20c997);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: pulse 2s infinite;
                ">
                    <i class="fas fa-university" style="font-size: 36px; color: white;"></i>
                </div>
                
                <h1 style="
                    font-size: 28px;
                    font-weight: 700;
                    color: #1a1a1a;
                    margin-bottom: 15px;
                ">
                    Przekierowywanie do banku
                    <div style="display: inline-flex; gap: 4px; margin-left: 10px;">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #28a745; animation: bounce 1.4s infinite ease-in-out; animation-delay: -0.32s;"></div>
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #28a745; animation: bounce 1.4s infinite ease-in-out; animation-delay: -0.16s;"></div>
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #28a745; animation: bounce 1.4s infinite ease-in-out;"></div>
                    </div>
                </h1>
                
                <p style="
                    font-size: 16px;
                    color: #6b7280;
                    margin-bottom: 40px;
                    line-height: 1.6;
                ">
                    Proszę czekać, łączymy Cię z bezpieczną stroną Twojego banku
                </p>
                
                <div style="margin: 30px 0;">
                    <div style="
                        width: 100%;
                        height: 6px;
                        background: #e5e7eb;
                        border-radius: 3px;
                        overflow: hidden;
                        margin-bottom: 20px;
                    ">
                        <div style="
                            height: 100%;
                            background: linear-gradient(90deg, #28a745, #20c997);
                            width: 0%;
                            border-radius: 3px;
                            animation: progressFill 4s ease-in-out infinite;
                        "></div>
                    </div>
                </div>
                
                <div style="text-align: left; margin: 30px 0;">
                    <div id="step1" style="
                        display: flex;
                        align-items: center;
                        gap: 15px;
                        padding: 12px 0;
                        font-size: 15px;
                        color: #28a745;
                        font-weight: 600;
                        transform: translateX(5px);
                    ">
                        <i class="fas fa-check" style="
                            width: 24px;
                            height: 24px;
                            border-radius: 50%;
                            background: #28a745;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 12px;
                        "></i>
                        <span>Płatność została przetworzona</span>
                    </div>
                    
                    <div id="step2" style="
                        display: flex;
                        align-items: center;
                        gap: 15px;
                        padding: 12px 0;
                        font-size: 15px;
                        color: #6b7280;
                        opacity: 0.5;
                    ">
                        <i class="fas fa-shield-alt" style="
                            width: 24px;
                            height: 24px;
                            border-radius: 50%;
                            background: #f3f4f6;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 12px;
                        "></i>
                        <span>Nawiązywanie bezpiecznego połączenia</span>
                    </div>
                    
                    <div id="step3" style="
                        display: flex;
                        align-items: center;
                        gap: 15px;
                        padding: 12px 0;
                        font-size: 15px;
                        color: #6b7280;
                        opacity: 0.5;
                    ">
                        <i class="fas fa-university" style="
                            width: 24px;
                            height: 24px;
                            border-radius: 50%;
                            background: #f3f4f6;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 12px;
                        "></i>
                        <span>Przekierowywanie do banku</span>
                    </div>
                </div>
                
                <div style="
                    background: #f8f9fa;
                    border-radius: 12px;
                    padding: 20px;
                    margin: 30px 0;
                    border: 1px solid #e9ecef;
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        font-weight: 600;
                        color: #28a745;
                        margin-bottom: 10px;
                    ">
                        <i class="fas fa-lock"></i>
                        <span>Połączenie zabezpieczone SSL</span>
                    </div>
                    <div style="
                        font-size: 14px;
                        color: #6b7280;
                        text-align: center;
                    ">
                        Twoja płatność jest chroniona najwyższymi standardami bezpieczeństwa
                    </div>
                </div>
                
                <div style="
                    width: 50px;
                    height: 50px;
                    margin: 20px auto;
                ">
                    <div style="
                        width: 100%;
                        height: 100%;
                        border: 4px solid #e5e7eb;
                        border-left-color: #28a745;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                    "></div>
                </div>
            </div>
        </div>
        `;
        
        // Inject overlay into page
        document.body.insertAdjacentHTML('beforeend', overlayHTML);
        
        // Animate steps
        setTimeout(() => {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            if (step1 && step2) {
                step1.style.color = '#6b7280';
                step1.style.fontWeight = '400';
                step1.style.transform = 'translateX(0)';
                step1.querySelector('i').style.background = '#f3f4f6';
                step1.querySelector('i').style.color = '#6b7280';
                
                step2.style.color = '#28a745';
                step2.style.fontWeight = '600';
                step2.style.opacity = '1';
                step2.style.transform = 'translateX(5px)';
                step2.querySelector('i').style.background = '#28a745';
                step2.querySelector('i').style.color = 'white';
            }
        }, 1500);
        
        setTimeout(() => {
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            if (step2 && step3) {
                step2.style.color = '#6b7280';
                step2.style.fontWeight = '400';
                step2.style.transform = 'translateX(0)';
                step2.querySelector('i').style.background = '#f3f4f6';
                step2.querySelector('i').style.color = '#6b7280';
                
                step3.style.color = '#28a745';
                step3.style.fontWeight = '600';
                step3.style.opacity = '1';
                step3.style.transform = 'translateX(5px)';
                step3.querySelector('i').style.background = '#28a745';
                step3.querySelector('i').style.color = 'white';
            }
        }, 3000);
    }
    
    // Enhanced payment redirect function for your checkout button
    window.enhancedPaymentRedirect = function(paymentUrl) {
        showBankRedirectOverlay();
        setTimeout(() => {
            window.location.href = paymentUrl;
        }, 1000);
    };
    
    // Auto-detect and show overlay if we're on a payment page
    if (isPaymentProcessingPage()) {
        console.log('Payment processing page detected');
        setTimeout(showBankRedirectOverlay, 500);
    }
    
    // Also check after DOM is fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (isPaymentProcessingPage()) {
                setTimeout(showBankRedirectOverlay, 500);
            }
        });
    }
    
    // Monitor for URL changes (for single-page applications)
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            if (isPaymentProcessingPage()) {
                setTimeout(showBankRedirectOverlay, 200);
            }
        }
    }).observe(document, { subtree: true, childList: true });
    
})();