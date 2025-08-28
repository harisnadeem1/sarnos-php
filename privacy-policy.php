<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - The Global Sports Journal</title>
    <meta name="description" content="Privacy policy and data protection information for The Global Sports Journal website.">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .nav {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav a:hover, .nav a.active {
            color: #ffd700;
        }
        
        /* Main Content */
        .main {
            padding: 3rem 0;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #1e3c72;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.1rem;
            color: #666;
        }
        
        .language-toggle {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .language-btn {
            background: #2a5298;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            margin: 0 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .language-btn:hover, .language-btn.active {
            background: #1e3c72;
        }
        
        .privacy-content {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .language-section {
            display: none;
        }
        
        .language-section.active {
            display: block;
        }
        
        .privacy-content h2 {
            color: #1e3c72;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }
        
        .privacy-content h2:first-child {
            margin-top: 0;
        }
        
        .privacy-content h3 {
            color: #2a5298;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            margin-top: 1.5rem;
        }
        
        .privacy-content p {
            margin-bottom: 1rem;
            text-align: justify;
        }
        
        .privacy-content ul {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }
        
        .privacy-content li {
            margin-bottom: 0.5rem;
        }
        
        /* Footer */
        .footer {
            background: #1e3c72;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 3rem;
        }
        
        .footer p {
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .nav {
                gap: 1rem;
            }
            
            .privacy-content {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="alternative_page.php" class="logo">🏆 The Global Sports Journal</a>
                <nav class="nav">
                    <a href="alternative_page.php">Home</a>
                    <a href="sports-news.php">Sports News</a>
                    <a href="fitness-health.php">Fitness & Health</a>
                    <a href="privacy-policy.php" class="active">Privacy Policy</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <section class="page-header">
                <h1>🔒 Privacy Policy</h1>
                <p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
            </section>

            <div class="language-toggle">
                <button class="language-btn active" onclick="showLanguage('english')">English</button>
                <button class="language-btn" onclick="showLanguage('polish')">Polski</button>
            </div>

            <div class="privacy-content">
                <!-- English Version -->
                <div id="english" class="language-section active">
                    <h2>Privacy Policy</h2>
                    <p><strong>Last updated:</strong> <?php echo date('F j, Y'); ?></p>
                    
                    <h3>1. Information We Collect</h3>
                    <p>The Global Sports Journal is committed to protecting your privacy. We collect limited information to provide you with the best possible experience:</p>
                    <ul>
                        <li>Basic analytics data (page views, browser type, general location)</li>
                        <li>Information you voluntarily provide through contact forms</li>
                        <li>Cookies for website functionality and analytics</li>
                    </ul>
                    
                    <h3>2. How We Use Your Information</h3>
                    <p>We use collected information solely for:</p>
                    <ul>
                        <li>Improving website content and user experience</li>
                        <li>Responding to your inquiries and feedback</li>
                        <li>Analyzing website traffic and performance</li>
                        <li>Ensuring website security and preventing fraud</li>
                    </ul>
                    
                    <h3>3. Information Sharing</h3>
                    <p>We do not sell, trade, or rent your personal information to third parties. We may share aggregated, anonymized data for research purposes only.</p>
                    
                    <h3>4. Cookies</h3>
                    <p>Our website uses cookies to enhance your browsing experience. You can control cookie settings through your browser preferences. Disabling cookies may affect website functionality.</p>
                    
                    <h3>5. Third-Party Services</h3>
                    <p>We may use third-party analytics services to better understand our audience. These services have their own privacy policies which we encourage you to review.</p>
                    
                    <h3>6. Data Security</h3>
                    <p>We implement appropriate security measures to protect your information against unauthorized access, alteration, disclosure, or destruction.</p>
                    
                    <h3>7. Your Rights</h3>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access the personal information we hold about you</li>
                        <li>Request correction of inaccurate information</li>
                        <li>Request deletion of your personal information</li>
                        <li>Opt-out of certain data collection practices</li>
                    </ul>
                    
                    <h3>8. Children's Privacy</h3>
                    <p>Our website is not directed to children under 13. We do not knowingly collect personal information from children under 13 years of age.</p>
                    
                    <h3>9. Changes to This Policy</h3>
                    <p>We may update this privacy policy periodically. Changes will be posted on this page with an updated revision date.</p>
                    
                    <h3>10. Contact Information</h3>
                    <p>If you have questions about this privacy policy, please contact us through our website's contact form.</p>
                </div>
                
                <!-- Polish Version -->
                <div id="polish" class="language-section">
                    <h2>Polityka Prywatności</h2>
                    <p><strong>Ostatnia aktualizacja:</strong> <?php echo date('j F Y'); ?></p>
                    
                    <h3>1. Informacje, które gromadzimy</h3>
                    <p>The Global Sports Journal zobowiązuje się do ochrony Twojej prywatności. Gromadzimy ograniczone informacje, aby zapewnić Ci najlepsze możliwe doświadczenie:</p>
                    <ul>
                        <li>Podstawowe dane analityczne (odsłony stron, typ przeglądarki, ogólna lokalizacja)</li>
                        <li>Informacje dobrowolnie podane przez formularze kontaktowe</li>
                        <li>Pliki cookie dla funkcjonalności witryny i analityki</li>
                    </ul>
                    
                    <h3>2. Jak wykorzystujemy Twoje informacje</h3>
                    <p>Wykorzystujemy zebrane informacje wyłącznie do:</p>
                    <ul>
                        <li>Poprawy zawartości witryny i doświadczenia użytkownika</li>
                        <li>Odpowiadania na Twoje zapytania i opinie</li>
                        <li>Analizowania ruchu i wydajności witryny</li>
                        <li>Zapewnienia bezpieczeństwa witryny i zapobiegania oszustwom</li>
                    </ul>
                    
                    <h3>3. Udostępnianie informacji</h3>
                    <p>Nie sprzedajemy, nie wymieniamy ani nie wynajmujemy Twoich danych osobowych stronom trzecim. Możemy udostępniać zagregowane, anonimowe dane wyłącznie w celach badawczych.</p>
                    
                    <h3>4. Pliki cookie</h3>
                    <p>Nasza witryna używa plików cookie, aby poprawić Twoje doświadczenie przeglądania. Możesz kontrolować ustawienia plików cookie przez preferencje przeglądarki. Wyłączenie plików cookie może wpłynąć na funkcjonalność witryny.</p>
                    
                    <h3>5. Usługi stron trzecich</h3>
                    <p>Możemy korzystać z usług analitycznych stron trzecich, aby lepiej zrozumieć naszą publiczność. Te usługi mają własne polityki prywatności, które zachęcamy do przejrzenia.</p>
                    
                    <h3>6. Bezpieczeństwo danych</h3>
                    <p>Wdrażamy odpowiednie środki bezpieczeństwa, aby chronić Twoje informacje przed nieuprawnionym dostępem, zmianą, ujawnieniem lub zniszczeniem.</p>
                    
                    <h3>7. Twoje prawa</h3>
                    <p>Masz prawo do:</p>
                    <ul>
                        <li>Dostępu do danych osobowych, które o Tobie posiadamy</li>
                        <li>Żądania poprawienia nieprawidłowych informacji</li>
                        <li>Żądania usunięcia Twoich danych osobowych</li>
                        <li>Rezygnacji z niektórych praktyk gromadzenia danych</li>
                    </ul>
                    
                    <h3>8. Prywatność dzieci</h3>
                    <p>Nasza witryna nie jest skierowana do dzieci poniżej 13 roku życia. Nie gromadzimy świadomie danych osobowych od dzieci poniżej 13 lat.</p>
                    
                    <h3>9. Zmiany w tej polityce</h3>
                    <p>Możemy okresowo aktualizować tę politykę prywatności. Zmiany zostaną opublikowane na tej stronie z zaktualizowaną datą rewizji.</p>
                    
                    <h3>10. Informacje kontaktowe</h3>
                    <p>Jeśli masz pytania dotyczące tej polityki prywatności, skontaktuj się z nami przez formularz kontaktowy na naszej stronie.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> The Global Sports Journal. All rights reserved.</p>
            <p>Educational content for sports enthusiasts worldwide | Last updated: <?php echo date('F j, Y'); ?></p>
        </div>
    </footer>

    <script>
        function showLanguage(language) {
            // Hide all language sections
            document.querySelectorAll('.language-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.language-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected language section
            document.getElementById(language).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html> 