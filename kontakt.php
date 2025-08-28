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
$page_title = "Kontakt - " . $shop_name;
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

.contact-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 1.1rem;
}

.contact-info p {
    margin-bottom: 10px;
}

.contact-form {
    margin-top: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #dc3545;
    outline: none;
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
}

.form-group textarea {
    min-height: 150px;
    resize: vertical;
}

.submit-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 14px 28px;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: #dc3545;
    transform: translateY(-1px);
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
    
    .contact-info {
        font-size: 1rem;
    }
}
</style>

<div class="content-wrapper">
    <div class="page-header">
        <h1><?php echo $texts['contact']['header']['title']; ?></h1>
        <p><?php echo $texts['contact']['header']['subtitle']; ?></p>
    </div>

    <div class="content-section">
        <h2><?php echo $texts['contact']['address']['title']; ?></h2>
        <div class="contact-info">
            <p><?php echo $texts['contact']['address']['line1']; ?></p>
            <p><?php echo $texts['contact']['address']['line2']; ?></p>
            <p><?php echo $texts['contact']['address']['line3']; ?></p>
        </div>
    </div>

    <div class="content-section">
        <h2><?php echo $texts['contact']['form']['title']; ?></h2>
        <form class="contact-form" action="#" method="POST">
            <div class="form-group">
                <label for="name"><?php echo $texts['contact']['form']['name']; ?></label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email"><?php echo $texts['contact']['form']['email']; ?></label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="subject"><?php echo $texts['contact']['form']['subject']; ?></label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="message"><?php echo $texts['contact']['form']['message']; ?></label>
                <textarea id="message" name="message" required></textarea>
            </div>
            
            <button type="submit" class="submit-btn">
                <?php echo $texts['contact']['form']['button']; ?>
            </button>
        </form>
    </div>

    <div class="content-section">
        <h2><?php echo $texts['contact']['hours']['title']; ?></h2>
        <ul>
            <li><?php echo $texts['contact']['hours']['monday_friday']; ?></li>
            <li><?php echo $texts['contact']['hours']['saturday']; ?></li>
            <li><?php echo $texts['contact']['hours']['sunday']; ?></li>
        </ul>
    </div>
</div>


<?php include 'footer.php'; ?>
</body>
</html> 