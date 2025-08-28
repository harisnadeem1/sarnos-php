<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Global Sports Journal - Daily Sports News & Fitness</title>
    <meta name="description" content="Your daily source of honest and inspiring stories in the world of sports, fitness, and health.">
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
            max-width: 1200px;
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
        
        .hero {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .hero h1 {
            font-size: 2.5rem;
            color: #1e3c72;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            color: #666;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .content-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .content-card:hover {
            transform: translateY(-5px);
        }
        
        .content-card h3 {
            color: #1e3c72;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        
        .content-card p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .about-section {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .about-section h2 {
            color: #1e3c72;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .about-item h4 {
            color: #2a5298;
            font-size: 1.2rem;
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
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .nav {
                gap: 1rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
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
                    <a href="alternative_page.php" class="active">Home</a>
                    <a href="sports-news.php">Sports News</a>
                    <a href="fitness-health.php">Fitness & Health</a>
                    <a href="privacy-policy.php">Privacy Policy</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <section class="hero">
                <h1>Welcome to The Global Sports Journal</h1>
                <p>Your daily source of honest and inspiring stories in the world of sports, fitness, and health. Join our community of sports enthusiasts and discover the latest trends, training tips, and motivational stories.</p>
            </section>

            <div class="content-grid">
                <div class="content-card">
                    <h3>🏃‍♂️ Daily Sports Coverage</h3>
                    <p>Stay updated with the latest sports news, athlete profiles, and performance analysis from around the globe. We cover everything from professional leagues to grassroots sports.</p>
                </div>
                
                <div class="content-card">
                    <h3>💪 Fitness & Training</h3>
                    <p>Discover effective workout routines, nutrition tips, and recovery strategies designed by certified fitness professionals and sports scientists.</p>
                </div>
                
                <div class="content-card">
                    <h3>🧠 Mental Health in Sports</h3>
                    <p>Explore the psychological aspects of sports performance, mindfulness techniques, and mental resilience building for athletes and fitness enthusiasts.</p>
                </div>
                
                <div class="content-card">
                    <h3>📚 Educational Resources</h3>
                    <p>Access comprehensive guides, research-backed articles, and expert insights to enhance your understanding of sports science and performance optimization.</p>
                </div>
            </div>

            <section class="about-section">
                <h2>Our Mission & Vision</h2>
                <p style="text-align: center; font-size: 1.1rem; color: #666; margin-bottom: 2rem;">
                    At The Global Sports Journal, we believe that sports have the power to inspire, unite, and transform lives. Our mission is to provide authentic, well-researched content that educates, motivates, and supports the global sports community.
                </p>
                
                <div class="about-grid">
                    <div class="about-item">
                        <h4>🎯 What We Do</h4>
                        <p>We provide comprehensive sports journalism, fitness guidance, and health education. Our content ranges from breaking sports news to in-depth analysis of athletic performance and training methodologies.</p>
                    </div>
                    
                    <div class="about-item">
                        <h4>🌟 Our Values</h4>
                        <p>Integrity, accuracy, and passion drive everything we do. We're committed to delivering unbiased reporting, evidence-based health advice, and inspiring stories that celebrate human achievement in sports.</p>
                    </div>
                    
                    <div class="about-item">
                        <h4>🤝 Community Focus</h4>
                        <p>We believe in building a supportive community where athletes, fitness enthusiasts, and sports fans can find valuable information, inspiration, and guidance for their personal athletic journeys.</p>
                    </div>
                    
                    <div class="about-item">
                        <h4>📖 What You'll Find Here</h4>
                        <p>Detailed sports analysis, training tips, nutrition guides, mental health resources, athlete interviews, and educational content designed to help you achieve your fitness and sports goals.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> The Global Sports Journal. All rights reserved.</p>
            <p>Educational content for sports enthusiasts worldwide | Last updated: <?php echo date('F j, Y'); ?></p>
        </div>
    </footer>
</body>
</html> 