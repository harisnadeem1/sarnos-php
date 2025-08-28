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
$page_title = "Informacje o Wysyłce - " . $shop_name;
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
</head>
<body>
<?php include 'header.php'; ?>

<style>
/* ===== RESET & BASE STYLES ===== */
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
    margin-bottom: 60px;
}

.page-header h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    color: #333;
}

.page-header p {
    font-size: 1.3rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.content-section {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.content-section h2 {
    color: #dc3545;
    font-size: 1.8rem;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.content-section p {
    color: #555;
    line-height: 1.6;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.content-section ul {
    color: #555;
    line-height: 1.6;
    margin-left: 20px;
    font-size: 1.1rem;
}

.content-section li {
    margin-bottom: 15px;
}

.content-section a {
    color: #dc3545;
    text-decoration: none;
    transition: color 0.3s ease;
}

.content-section a:hover {
    color: #dc3545;
    text-decoration: underline;
}

.shipping-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.shipping-info p {
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 40px 20px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .page-header p {
        font-size: 1.1rem;
    }
    
    .content-section {
        padding: 30px 20px;
    }
    
    .content-section h2 {
        font-size: 1.5rem;
    }
    
    .content-section p,
    .content-section ul {
        font-size: 1rem;
    }
}
</style>

<div class="content-wrapper">
    <div class="page-header">
        <h1><?php echo $texts['shipping']['header']['title']; ?></h1>
        <p><?php echo $texts['shipping']['header']['subtitle']; ?></p>
    </div>

    <div class="content-section">
        <h2><?php echo $texts['shipping']['delivery_time']['title']; ?></h2>
        <p><?php echo $texts['shipping']['delivery_time']['text']; ?></p>
        
        <div class="shipping-info">
            <p><strong><?php echo $texts['shipping']['delivery_time']['info']['shipping']; ?></strong></p>
            <p><strong><?php echo $texts['shipping']['delivery_time']['info']['days']; ?></strong></p>
            <p><strong><?php echo $texts['shipping']['delivery_time']['info']['area']; ?></strong></p>
        </div>
    </div>

    <div class="content-section">
        <h2><?php echo $texts['shipping']['tracking']['title']; ?></h2>
        <p><?php echo $texts['shipping']['tracking']['text']; ?></p>
        <ul>
            <?php foreach ($texts['shipping']['tracking']['list'] as $item): ?>
                <li><?php echo $item; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="content-section">
        <h2><?php echo $texts['shipping']['contact']['title']; ?></h2>
        <p><?php echo $texts['shipping']['contact']['text']; ?></p>
    </div>
</div>


<?php include 'footer.php'; ?>
</body>
</html> 