<?php
// Get current settings
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');
$shop_email = $db->getSetting('shop_email');
$shop_phone = $db->getSetting('shop_phone');
$shop_address = $db->getSetting('shop_address');
$shop_city = $db->getSetting('shop_city');
$shop_postal_code = $db->getSetting('shop_postal_code');
$shop_country = $db->getSetting('shop_country');
$shop_nip = $db->getSetting('shop_nip');
$shop_regon = $db->getSetting('shop_regon');
$shop_krs = $db->getSetting('shop_krs');
$shop_bank_account = $db->getSetting('shop_bank_account');
$shop_bank_name = $db->getSetting('shop_bank_name');
$shop_bank_swift = $db->getSetting('shop_bank_swift');
$shop_bank_iban = $db->getSetting('shop_bank_iban');
?>

<div class="newsletter-section">
    <div class="newsletter-container">
        <div class="newsletter-content">
            <h2><?php echo $texts['footer']['newsletter']['title']; ?></h2>
            <p><?php echo $texts['footer']['newsletter']['subtitle']; ?></p>
            <form class="newsletter-form" action="#" method="POST">
                <div class="form-group">
                    <input type="email" 
                           placeholder="<?php echo $texts['footer']['newsletter']['placeholder']; ?>" 
                           required>
                    <button type="submit" class="subscribe-btn">
                        <?php echo $texts['footer']['newsletter']['button']; ?>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <div class="footer-logo">
                <h2 style="color: white; font-size: 1.8rem; font-weight: 700; margin: 0;">Sarnos</h2>
            </div>
            <p><?php echo $texts['footer']['about']['text']; ?></p>
            <div class="social-links">
                <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        
        <div class="footer-section">
            <h3><?php echo $texts['footer']['contact']['title']; ?></h3>
            <p><i class="fas fa-envelope"></i> <?php echo $texts['footer']['contact']['email']; ?></p>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo $texts['footer']['contact']['address_line1']; ?></p>
            <p><?php echo $texts['footer']['contact']['address_line2']; ?></p>
            <p><?php echo $texts['footer']['contact']['address_line3']; ?></p>
        </div>
        
        <div class="footer-section">
            <h3><?php echo $texts['footer']['info']['title']; ?></h3>
            <ul>
                <li><a href="regulamin.php"><?php echo $texts['footer']['info']['terms']; ?></a></li>
                <li><a href="polityka-prywatnosci.php"><?php echo $texts['footer']['info']['privacy']; ?></a></li>
                <li><a href="informacje-wysylka.php"><?php echo $texts['footer']['info']['shipping']; ?></a></li>
                <li><a href="zwroty.php"><?php echo $texts['footer']['info']['returns']; ?></a></li>
                <li><a href="kontakt.php"><?php echo $texts['footer']['info']['contact']; ?></a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3><?php echo $texts['footer']['company']['title']; ?></h3>
            <p><?php echo $texts['footer']['company']['legal_name']; ?></p>
            <p><?php echo $texts['footer']['company']['kvk']; ?></p>
            <p><?php echo $texts['footer']['company']['vat']; ?></p>
            <!-- <p><?php echo $texts['footer']['company']['bank']; ?></p>
            <p><?php echo $texts['footer']['company']['swift']; ?></p>
            <p><?php echo $texts['footer']['company']['iban']; ?></p> -->
        </div>
    </div>
    
    <div class="footer-bottom">
        <p><?php echo $texts['footer']['bottom']['text']; ?></p>
    </div>
</footer>


<style>
.footer {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #1a252f 100%);
    color: white;
    padding: 60px 0 20px;
    margin-top: 0;
    box-shadow: 0 -4px 20px rgba(44, 62, 80, 0.3);
    position: relative;
    overflow: hidden;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #e74c3c, #f39c12, #3498db, #2ecc71);
    animation: rainbow 3s ease-in-out infinite;
}

@keyframes rainbow {
    0%, 100% { transform: translateX(-100%); }
    50% { transform: translateX(100%); }
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    padding: 0 20px;
}

.footer-logo {
    margin-bottom: 20px;
}

.footer-logo-image {
    height: 60px;
    width: auto;
    object-fit: contain;
    margin-bottom: 15px;
}

.footer-logo-text {
    font-size: 1.8rem;
    font-weight: 700;
    color: white;
    letter-spacing: 1px;
    margin: 0;
    padding: 0;
    text-transform: uppercase;
    position: relative;
    display: inline-block;
}

.footer-logo-text::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 100%;
    height: 2px;
    background: rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.footer-logo-text:hover::after {
    background: white;
    transform: scaleX(1.1);
}

.footer-section h3 {
    color: white;
    font-size: 1.4rem;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.footer-section h3::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 50px;
    height: 2px;
    background: rgba(255, 255, 255, 0.5);
}

.footer-section p {
    margin-bottom: 12px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 12px;
}

.footer-section ul li a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
    position: relative;
}

.footer-section ul li a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 1px;
    bottom: -2px;
    left: 0;
    background-color: white;
    transition: width 0.3s ease;
}

.footer-section ul li a:hover {
    color: white;
    transform: translateX(5px);
}

.footer-section ul li a:hover::after {
    width: 100%;
}

.social-links {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.social-link {
    color: white;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.social-link:hover {
    background: linear-gradient(135deg, #3498db, #2ecc71);
    color: white;
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

.footer-bottom {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background-color: #000000;
    padding: 20px;
}

.footer-bottom p {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .footer {
        padding: 40px 0 20px;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-section {
        text-align: left;
        padding: 0 20px;
    }
    
    .footer-section h3 {
        font-size: 1.3rem;
    }
    
    .footer-section h3::after {
        left: 0;
        transform: none;
    }
    
    .footer-logo {
        text-align: left;
    }
    
    .footer-logo-text {
        font-size: 1.5rem;
    }
    
    .social-links {
        justify-content: flex-start;
    }
    
    .footer-section ul li a:hover {
        transform: translateX(5px);
    }
    
    .footer-bottom {
        padding: 20px;
    }
}

.newsletter-section {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 50%, #a71e2a 100%);
    padding: 30px 0;
    margin: 0;
    position: relative;
    overflow: hidden;
}

.newsletter-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="rgba(255,255,255,0.05)"/></svg>');
    opacity: 0.1;
}

.newsletter-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}

.newsletter-content {
    text-align: center;
    color: white;
}

.newsletter-content h2 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.newsletter-content p {
    font-size: 1rem;
    margin-bottom: 20px;
    opacity: 0.9;
}

.newsletter-form {
    max-width: 500px;
    margin: 0 auto;
}

.newsletter-form .form-group {
    display: flex;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    padding: 3px;
    border-radius: 50px;
    backdrop-filter: blur(10px);
}

.newsletter-form input {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 50px;
    font-size: 0.95rem;
    background: white;
    color: #333;
}

.newsletter-form input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
}

.subscribe-btn {
    padding: 12px 25px;
    border: none;
    border-radius: 50px;
    background: #ffffff;
    color: #dc3545;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.subscribe-btn:hover {
    background: #f8f9fa;
    color: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
}

.subscribe-btn i {
    transition: transform 0.3s ease;
    font-size: 0.9rem;
}

.subscribe-btn:hover i {
    transform: translateX(3px);
}

@media (max-width: 768px) {
    .newsletter-section {
        padding: 25px 0;
    }
    
    .newsletter-content h2 {
        font-size: 1.5rem;
    }
    
    .newsletter-content p {
        font-size: 0.9rem;
    }
    
    .newsletter-form .form-group {
        flex-direction: column;
        background: none;
        padding: 0;
    }
    
    .newsletter-form input {
        width: 100%;
        margin-bottom: 8px;
    }
    
    .subscribe-btn {
        width: 100%;
        justify-content: center;
        padding: 10px 20px;
    }
}
</style> 