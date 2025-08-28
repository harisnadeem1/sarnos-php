<?php
session_start();
require_once 'database.php';
require_once 'cloaking.php';

// Check cloaking voor Live Monitoring
checkCloaking();

// Get current settings
$db = Database::getInstance();
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');
$page_title = "Over Ons - " . $shop_name;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .page-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .page-header p {
            font-size: 1.3rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }

        .about-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .about-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }

        .about-text {
            color: #333;
        }

        .about-text h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #dc3545;
        }

        .about-text p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #666;
        }

        .about-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .about-image:hover img {
            transform: scale(1.05);
        }

        .values-section {
            margin-bottom: 60px;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .value-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc3545, #ff8e53);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: white;
        }

        .value-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .value-card p {
            color: #666;
            line-height: 1.6;
        }

        .team-section {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 60px;
        }

        .team-section h2 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 50px;
            color: #2c3e50;
        }

        .team-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        .contact-cta {
            background: linear-gradient(135deg, #87CEEB, #B0E0E6);
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
            color: white;
        }

        .contact-cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .contact-cta p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: white;
            color: #2c3e50;
            padding: 18px 36px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .about-grid {
                grid-template-columns: 1fr;
            }

            .about-text h2 {
                font-size: 2rem;
            }

            .about-text p {
                font-size: 1rem;
            }

            .about-section,
            .team-section,
            .contact-cta {
                padding: 40px 20px;
            }

            .values-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .team-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .contact-cta h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .team-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="content-wrapper">
    <div class="page-header">
        <h1><?php echo $texts['about']['header']['title']; ?></h1>
        <p><?php echo $texts['about']['header']['subtitle']; ?></p>
    </div>

    <div class="about-section">
        <div class="about-content">
            <div class="about-grid">
                <div class="about-text">
                    <h2><?php echo $texts['about']['section']['title']; ?></h2>
                    <p><?php echo $texts['about']['section']['paragraph1']; ?></p>
                    <p><?php echo $texts['about']['section']['paragraph2']; ?></p>
                </div>
                <div class="about-image">
                    <img src="/about.png" alt="Team Sklepoll">
                </div>
            </div>
        </div>
    </div>

    <div class="values-section">
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-heart"></i></div>
                <h3><?php echo $texts['about']['values']['passion']['title']; ?></h3>
                <p><?php echo $texts['about']['values']['passion']['text']; ?></p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                <h3><?php echo $texts['about']['values']['trust']['title']; ?></h3>
                <p><?php echo $texts['about']['values']['trust']['text']; ?></p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-rocket"></i></div>
                <h3><?php echo $texts['about']['values']['innovation']['title']; ?></h3>
                <p><?php echo $texts['about']['values']['innovation']['text']; ?></p>
            </div>
        </div>
    </div>

    <div class="team-section">
        <h2><?php echo $texts['about']['stats']['title']; ?></h2>
        <div class="team-stats">
            <div class="stat-item">
                <div class="stat-number">50,000+</div>
                <div class="stat-label"><?php echo $texts['about']['stats']['clients']; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">1,000+</div>
                <div class="stat-label"><?php echo $texts['about']['stats']['products']; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">99.8%</div>
                <div class="stat-label"><?php echo $texts['about']['stats']['reviews']; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">5+</div>
                <div class="stat-label"><?php echo $texts['about']['stats']['experience']; ?></div>
            </div>
        </div>
    </div>

    <div class="contact-cta">
        <h2><?php echo $texts['about']['contact']['title']; ?></h2>
        <p><?php echo $texts['about']['contact']['text']; ?></p>
        <a href="kontakt.php" class="cta-button">
            <i class="fas fa-envelope"></i>
            <?php echo $texts['about']['contact']['button']; ?>
        </a>
    </div>
</div>


    <?php include 'footer.php'; ?>
</body>
</html> 