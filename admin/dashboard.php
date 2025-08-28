<?php
session_start();
// Zet de tijdzone voor correcte timestamps
date_default_timezone_set('Europe/Amsterdam');
require_once '../database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Optional: Check session timeout (24 hours)
$session_timeout = 24 * 60 * 60; // 24 hours in seconds
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $session_timeout) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}

// Initialize database
$db = Database::getInstance();

// Load cloaking configuration - altijd fresh reload
require_once '../cloaking.php';

// Force fresh instance elke keer om cache problemen te voorkomen
$cloaking = new CloakingSystem();
$cloakingConfig = $cloaking->getConfig();

// Extra check voor GET requests na success/error redirects
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Maak opnieuw een verse instantie na redirects
    unset($cloaking);
    $cloaking = new CloakingSystem();
    $cloakingConfig = $cloaking->getConfig();
}

// Handle success/error messages from redirects
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Cloaking instellingen succesvol opgeslagen!";
}
if (isset($_GET['error']) && $_GET['error'] == '1') {
    if (isset($_GET['error_msg']) && !empty($_GET['error_msg'])) {
        $error_message = "Fout bij opslaan cloaking instellingen: " . htmlspecialchars(urldecode($_GET['error_msg']));
    } else {
        $error_message = "Fout bij opslaan cloaking instellingen.";
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                // Handle custom category
                $category = $_POST['category'];
                if ($category === 'custom' && !empty($_POST['custom_category'])) {
                    $category = trim($_POST['custom_category']);
                    $db->addCategory($category); // Add to categories list
                }

                // Process variants (with NL + FR + shared image)
                $variantsNl = $_POST['variant_name_nl'] ?? [];
                $variantsFr = $_POST['variant_name_fr'] ?? [];
                $variantImages = $_POST['variant_images'] ?? [];

                $variantsNlProcessed = [];
                $variantsFrProcessed = [];

                foreach ($variantImages as $i => $img) {
                    $nameNl = isset($variantsNl[$i]) ? trim($variantsNl[$i]) : '';
                    $nameFr = isset($variantsFr[$i]) ? trim($variantsFr[$i]) : '';
                    $img = trim($img);

                    if ($nameNl !== '' || $nameFr !== '') {
                        if ($nameNl !== '') {
                            $variantsNlProcessed[] = ['name' => $nameNl, 'image' => $img];
                        }
                        if ($nameFr !== '') {
                            $variantsFrProcessed[] = ['name' => $nameFr, 'image' => $img];
                        }
                    }
                }

                // Process images (split by comma)
                $additionalImages = !empty($_POST['additional_images'])
                    ? array_map('trim', explode(',', $_POST['additional_images']))
                    : [];

                // Build product data in the structure frontend expects
                $data = [
                    'translations' => [
                        'nl' => [
                            'name' => trim($_POST['name_nl']),
                            'description' => $_POST['description_nl'], // comes from Quill (HTML)
                            'variants' => $variantsNlProcessed
                        ],
                        'fr' => [
                            'name' => trim($_POST['name_fr']),
                            'description' => $_POST['description_fr'], // comes from Quill (HTML)
                            'variants' => $variantsFrProcessed
                        ]
                    ],
                    'price' => floatval($_POST['price']),
                    'old_price' => isset($_POST['old_price']) ? floatval($_POST['old_price']) : 0,
                    'image_url' => $_POST['image_url'] ?? '',
                    'images' => $additionalImages,
                    'shopify_variant_id' => $_POST['shopify_variant_id'] ?? '',
                    'category' => $category,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                try {
                    $result = $db->addProduct($data);
                    if ($result) {
                        $success_message = "Product succesvol toegevoegd!";
                    } else {
                        $error_message = "Fout bij toevoegen product - kon niet opslaan.";
                    }
                } catch (Exception $e) {
                    $error_message = "Fout bij toevoegen product: " . $e->getMessage();
                }
                break;




            case 'update_product':
                $variants = [];
                if (!empty($_POST['variant_names'])) {
                    foreach ($_POST['variant_names'] as $i => $vname) {
                        $vname = trim($vname);
                        $vimg = isset($_POST['variant_images'][$i]) ? trim($_POST['variant_images'][$i]) : '';
                        if ($vname !== '') {
                            $variants[] = ['name' => $vname, 'image' => $vimg];
                        }
                    }
                }
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => floatval($_POST['price']),
                    'old_price' => isset($_POST['old_price']) ? floatval($_POST['old_price']) : 0,
                    'image_url' => $_POST['image_url'],
                    'additional_images' => $_POST['additional_images'],
                    'shopify_variant_id' => $_POST['shopify_variant_id'],
                    'category' => isset($_POST['category']) ? $_POST['category'] : '',
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'variants' => $variants
                ];
                try {
                    $result = $db->updateProduct($_POST['product_id'], $data);
                    if ($result) {
                        $success_message = "Product succesvol bijgewerkt!";
                    } else {
                        $error_message = "Fout bij bijwerken product - kon niet opslaan.";
                    }
                } catch (Exception $e) {
                    $error_message = "Fout bij bijwerken product: " . $e->getMessage();
                }
                break;

            case 'delete_product':
                if ($db->deleteProduct($_POST['product_id'])) {
                    $success_message = "Product succesvol verwijderd!";
                } else {
                    $error_message = "Fout bij verwijderen product.";
                }
                break;

            case 'delete_multiple_products':
                if (isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
                    $productIds = array_map('intval', $_POST['product_ids']);
                    if ($db->deleteMultipleProducts($productIds)) {
                        $success_message = count($productIds) . " producten succesvol verwijderd!";
                    } else {
                        $error_message = "Fout bij verwijderen producten.";
                    }
                } else {
                    $error_message = "Geen producten geselecteerd om te verwijderen.";
                }
                break;

            case 'add_category':
                $categoryName = trim($_POST['category_name']);
                if (!empty($categoryName)) {
                    if ($db->addCategory($categoryName)) {
                        $success_message = "Categorie '$categoryName' succesvol toegevoegd!";
                    } else {
                        $error_message = "Fout bij toevoegen categorie.";
                    }
                } else {
                    $error_message = "Categorienaam mag niet leeg zijn.";
                }
                break;

            case 'delete_category':
                if ($db->deleteCategory($_POST['category_name'])) {
                    $success_message = "Categorie succesvol verwijderd!";
                } else {
                    $error_message = "Fout bij verwijderen categorie.";
                }
                break;

            case 'export_products':
                try {
                    $exportData = $db->exportProducts();
                    $filename = 'producten_export_' . date('Y-m-d_H-i-s') . '.json';

                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . strlen(json_encode($exportData)));

                    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    exit();
                } catch (Exception $e) {
                    $error_message = "Fout bij exporteren: " . $e->getMessage();
                }
                break;

            case 'update_cloaking':
                require_once '../cloaking.php';
                $cloaking = new CloakingSystem();

                // Verbeterde verwerking van IP whitelist input
                $ip_whitelist_input = $_POST['ip_whitelist'] ?? '';
                $ip_whitelist_processed = [];

                if (!empty($ip_whitelist_input)) {
                    // Split op newlines en filter lege regels
                    $lines = explode("\n", $ip_whitelist_input);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $ip_whitelist_processed[] = $line;
                        }
                    }
                }

                $newConfig = [
                    'enabled' => isset($_POST['cloaking_enabled']),
                    'allowed_countries' => isset($_POST['allowed_countries']) ? $_POST['allowed_countries'] : [],
                    'cloaking_redirect_url' => $_POST['cloaking_redirect_url'] ?? 'alternative_page.php',
                    'ip_whitelist' => $ip_whitelist_processed,
                    'hide_cloaking_url' => isset($_POST['hide_cloaking_url']),
                    'block_cloud_providers' => isset($_POST['block_cloud_providers'])
                ];

                try {
                    $cloaking->saveConfig($newConfig);
                    $success_message = "Cloaking instellingen succesvol opgeslagen!";

                    // Force fresh reload van de hele cloaking instantie
                    unset($cloaking);
                    require_once '../cloaking.php';
                    $cloaking = new CloakingSystem();
                    $cloakingConfig = $cloaking->getConfig();

                    // Redirect terug naar cloaking tab om op dezelfde pagina te blijven
                    header("Location: dashboard.php?tab=cloaking&success=1");
                    exit();
                } catch (Exception $e) {
                    $error_message = "Fout bij opslaan cloaking instellingen: " . $e->getMessage();
                    // Log de volledige fout voor debugging
                    error_log("Admin dashboard cloaking save error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());

                    // Redirect terug naar cloaking tab met foutdetails
                    $errorMsg = urlencode($e->getMessage());
                    header("Location: dashboard.php?tab=cloaking&error=1&error_msg=" . $errorMsg);
                    exit();
                }
                break;

            case 'add_my_ip':
                require_once '../cloaking.php';
                $cloaking = new CloakingSystem();
                $config = $cloaking->getConfig();

                // Haal het huidige IP op
                $currentIP = $cloaking->getVisitorIP();

                // Controleer of IP al in de whitelist staat
                $ipWhitelist = $config['ip_whitelist'] ?? [];

                if (!in_array($currentIP, $ipWhitelist)) {
                    // Voeg IP toe aan whitelist
                    $ipWhitelist[] = $currentIP;

                    // Update configuratie
                    $newConfig = $config;
                    $newConfig['ip_whitelist'] = $ipWhitelist;

                    try {
                        $cloaking->saveConfig($newConfig);

                        // Return JSON response voor AJAX
                        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => "IP {$currentIP} succesvol toegevoegd aan whitelist!",
                                'ip' => $currentIP,
                                'whitelist' => $ipWhitelist
                            ]);
                            exit();
                        }

                        $success_message = "IP {$currentIP} succesvol toegevoegd aan whitelist!";
                    } catch (Exception $e) {
                        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => false,
                                'message' => "Fout bij toevoegen IP: " . $e->getMessage()
                            ]);
                            exit();
                        }
                        $error_message = "Fout bij toevoegen IP: " . $e->getMessage();
                    }
                } else {
                    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => "IP {$currentIP} staat al in de whitelist!",
                            'already_exists' => true
                        ]);
                        exit();
                    }
                    $error_message = "IP {$currentIP} staat al in de whitelist!";
                }

                // Reload configuratie
                unset($cloaking);
                require_once '../cloaking.php';
                $cloaking = new CloakingSystem();
                $cloakingConfig = $cloaking->getConfig();
                break;

            case 'import_products':
                try {
                    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('Geen geldig bestand geüpload');
                    }

                    $fileContent = file_get_contents($_FILES['import_file']['tmp_name']);
                    $importData = json_decode($fileContent, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Ongeldig JSON bestand: ' . json_last_error_msg());
                    }

                    $overwrite = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === '1';

                    // Process images during import
                    if (isset($importData['products']) && is_array($importData['products'])) {
                        foreach ($importData['products'] as &$product) {
                            $product = $db->processProductImages($product);
                        }
                    }

                    $result = $db->importProducts($importData, $overwrite);
                    $success_message = "Import succesvol! {$result['imported']} producten geïmporteerd, {$result['skipped']} overgeslagen van {$result['total']} totaal.";
                } catch (Exception $e) {
                    $error_message = "Fout bij importeren: " . $e->getMessage();
                }
                break;

            case 'get_monitoring_data':
                // Debug: Log de request
                error_log("Live Monitoring: get_monitoring_data request received");

                require_once '../cloaking.php';
                $cloaking = new CloakingSystem();

                $filters = [];

                // Apply filters from request
                if (!empty($_POST['hours'])) {
                    $filters['hours'] = intval($_POST['hours']);
                }
                if (!empty($_POST['search'])) {
                    $search = $_POST['search'];
                    if (filter_var($search, FILTER_VALIDATE_IP)) {
                        $filters['ip'] = $search;
                    } else {
                        $filters['country'] = $search;
                    }
                }
                if (!empty($_POST['status'])) {
                    $filters['status'] = $_POST['status'];
                }

                $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
                $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
                $offset = ($page - 1) * $limit;

                // Get monitoring data
                $data = $cloaking->getLiveMonitoringData($limit + $offset, $filters);
                $stats = $cloaking->getMonitoringStats();

                // Debug: Log de response data
                error_log("Live Monitoring: Data count = " . count($data) . ", Stats = " . json_encode($stats));

                // Apply pagination
                $paginatedData = array_slice($data, $offset, $limit);

                $response = [
                    'success' => true,
                    'data' => $paginatedData,
                    'stats' => $stats,
                    'total' => count($data),
                    'page' => $page,
                    'pages' => ceil(count($data) / $limit),
                    'timestamp' => time(),
                    'debug' => [
                        'filters' => $filters,
                        'limit' => $limit,
                        'offset' => $offset,
                        'raw_data_count' => count($data)
                    ]
                ];

                // Debug: Log de volledige response
                error_log("Live Monitoring Response: " . json_encode($response));

                header('Content-Type: application/json');
                echo json_encode($response);
                exit();

            case 'get_monitoring_stats':
                require_once '../cloaking.php';
                $cloaking = new CloakingSystem();

                $stats = $cloaking->getMonitoringStats();

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'stats' => $stats,
                    'timestamp' => time()
                ]);
                exit();

            case 'clear_monitoring_data':
                require_once '../cloaking.php';
                $cloaking = new CloakingSystem();

                $success = $cloaking->clearMonitoringData();

                if ($success) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Alle monitoring data is succesvol verwijderd.'
                    ]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Fout bij het verwijderen van monitoring data.'
                    ]);
                }
                exit();
        }
    }
}

// Get current settings
$shopify_shop_url = $db->getSetting('shopify_shop_url');

// Get all products and categories
$products = $db->getAllProducts(false);
$categories = $db->getCategories();


?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Quill.js - Professionele rijke teksteditor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <!-- Custom animations for notifications -->
    <style>
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* IP Whitelist hover effects */
        #addMyIP:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            transition: all 0.2s ease;
        }

        #currentIP {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        #currentIP:hover {
            transform: scale(1.05);
        }

        /* Live Monitoring Styles */
        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .monitoring-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .monitoring-stat-card:hover {
            transform: translateY(-5px);
        }

        .monitoring-stat-card h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .monitoring-stat-card .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }

        .monitoring-stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .monitoring-stat-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .monitoring-stat-card.red {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .monitoring-stat-card.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        #monitoringTable tbody tr {
            transition: background-color 0.2s ease;
        }

        #monitoringTable tbody tr:hover {
            background-color: #f8f9fa !important;
        }

        #monitoringTable tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-toegelaten {
            background: #d4edda;
            color: #155724;
        }

        .status-cloaked {
            background: #fff3cd;
            color: #856404;
        }

        .status-geblokkeerd {
            background: #f8d7da;
            color: #721c24;
        }

        .status-test {
            background: #cce8ff;
            color: #004085;
        }

        .country-flag {
            width: 20px;
            height: 15px;
            border-radius: 2px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .monitoring-filters {
            animation: slideInRight 0.5s ease;
        }

        .monitoring-table-loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .monitoring-table-loading i {
            font-size: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Simple Image Size Control -->
    <script>
        // Eenvoudige afbeelding grootte controle
        const ImageResize = {
            default: function (quill, options) {
                let selectedImg = null;
                let sizeControl = null;

                quill.root.addEventListener('click', function (evt) {
                    if (evt.target && evt.target.tagName === 'IMG') {
                        selectImage(evt.target);
                    } else {
                        hideControl();
                    }
                });

                function selectImage(img) {
                    hideControl();
                    selectedImg = img;
                    showSizeControl();
                }

                function showSizeControl() {
                    if (!selectedImg) return;

                    // Maak size control panel
                    sizeControl = document.createElement('div');
                    sizeControl.className = 'image-size-control';
                    sizeControl.style.cssText = `
                        position: fixed;
                        top: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: white;
                        padding: 15px 20px;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                        border: 2px solid #28a745;
                        z-index: 10000;
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        font-family: Inter, Arial, sans-serif;
                        font-size: 14px;
                    `;

                    const currentWidth = Math.round(selectedImg.clientWidth);
                    const currentHeight = Math.round(selectedImg.clientHeight);

                    sizeControl.innerHTML = `
                        <div style="font-weight: bold; color: #28a745;">📏 Afbeelding Grootte:</div>
                        <input type="number" id="img-width" value="${currentWidth}" min="50" max="800" 
                               style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
                        <span style="color: #666;">×</span>
                        <input type="number" id="img-height" value="${currentHeight}" min="50" max="600" 
                               style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
                        <span style="color: #666;">px</span>
                        <button onclick="applyImageSize()" 
                                style="background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            ✓ Toepassen
                        </button>
                        <button onclick="resetImageSize()" 
                                style="background: #6c757d; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                            🔄 Reset
                        </button>
                        <button onclick="hideImageControl()" 
                                style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                            ✕ Sluiten
                        </button>
                    `;

                    document.body.appendChild(sizeControl);

                    // Highlight geselecteerde afbeelding
                    selectedImg.style.outline = '3px solid #28a745';
                    selectedImg.style.outlineOffset = '2px';

                    // Auto-hide bij klik elders
                    setTimeout(() => {
                        document.addEventListener('click', onClickOutside);
                    }, 100);
                }

                function hideControl() {
                    if (sizeControl) {
                        sizeControl.remove();
                        sizeControl = null;
                    }
                    if (selectedImg) {
                        selectedImg.style.outline = '';
                        selectedImg.style.outlineOffset = '';
                        selectedImg = null;
                    }
                    document.removeEventListener('click', onClickOutside);
                }

                function onClickOutside(evt) {
                    if (!evt.target.closest('.image-size-control') && evt.target.tagName !== 'IMG') {
                        hideControl();
                    }
                }

                // Globale functies voor buttons
                window.applyImageSize = function () {
                    if (!selectedImg) return;

                    const width = parseInt(document.getElementById('img-width').value);
                    const height = parseInt(document.getElementById('img-height').value);

                    if (width >= 50 && height >= 50) {
                        selectedImg.style.width = width + 'px';
                        selectedImg.style.height = height + 'px';
                        showNotification(`✅ Afbeelding aangepast naar ${width} × ${height} pixels`, 'success');
                    } else {
                        showNotification('❌ Minimum grootte is 50 × 50 pixels', 'error');
                    }
                };

                window.resetImageSize = function () {
                    if (!selectedImg) return;

                    selectedImg.style.width = '';
                    selectedImg.style.height = '';
                    showNotification('🔄 Afbeelding teruggezet naar originele grootte', 'success');
                    hideControl();
                };

                window.hideImageControl = function () {
                    hideControl();
                };
            }
        };

        // Maak ImageResize beschikbaar
        window.ImageResize = ImageResize;
    </script>

    <style>
        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* ===== ADMIN HEADER ===== */
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .admin-header h1 {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 2rem;
            font-weight: 700;
        }

        .admin-nav {
            margin-top: 15px;
            display: flex;
            align-items: center;
        }

        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            margin-right: 20px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255, 255, 255, 0.1);
        }

        .admin-nav .logout-btn {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            margin-left: auto;
        }

        .admin-nav .logout-btn:hover {
            background: rgba(220, 53, 69, 0.3);
            border-color: rgba(220, 53, 69, 0.5);
        }

        /* ===== MAIN CONTENT ===== */
        .admin-main {
            padding: 40px 0;
        }

        .admin-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* ===== FORMS ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #dc3545;
            outline: none;
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #dc3545;
            color: white;
        }

        .btn-primary:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        /* ===== PRODUCTS TABLE ===== */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .products-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .products-table tr:hover {
            background: #f8f9fa;
        }

        .product-image-cell img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 30px;
        }

        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .tab-button.active {
            color: #dc3545;
            border-bottom-color: #dc3545;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .products-table {
                font-size: 12px;
            }

            .admin-header h1 {
                font-size: 1.5rem;
            }
        }

        .variant-row {
            display: flex;
            gap: 8px;
            margin-bottom: 6px;
        }

        .variant-input {
            flex: 1.2;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .variant-image-input {
            flex: 1.5;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .btn-variant-add {
            margin-top: 4px;
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            background: #dc3545;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-variant-remove {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            background: #ff4757;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        /* Quill Editor Styling */
        .ql-container {
            font-family: 'Inter', Arial, sans-serif !important;
            font-size: 14px !important;
            border-radius: 0 0 8px 8px !important;
        }

        .ql-toolbar {
            border-radius: 8px 8px 0 0 !important;
            background: #f8f9fa !important;
            border-bottom: 1px solid #e9ecef !important;
        }

        .ql-editor {
            min-height: 200px !important;
            padding: 15px !important;
        }

        .ql-editor.ql-blank::before {
            color: #999 !important;
            font-style: normal !important;
        }

        .ql-snow .ql-tooltip {
            z-index: 10001 !important;
        }

        /* Quill afbeelding styling */
        .ql-editor img {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 8px !important;
            margin: 10px 0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            cursor: pointer !important;
        }

        /* Image size control styling */
        .image-size-control {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .image-size-control input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
        }

        .image-size-control button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Hover effect voor afbeeldingen */
        .ql-editor img:hover {
            transform: scale(1.02) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2) !important;
            transition: all 0.3s ease !important;
        }
    </style>
</head>

<body>
    <!-- ===== ADMIN HEADER ===== -->
    <header class="admin-header">
        <div class="admin-container">
            <h1>
                <i class="fas fa-cogs"></i>
                Admin Dashboard
            </h1>
            <nav class="admin-nav">
                <a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="../index.php"><i class="fas fa-external-link-alt"></i> Bekijk Website</a>
                <a href="#"><i class="fas fa-chart-bar"></i> Statistieken</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Uitloggen</a>
            </nav>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="admin-main">
        <div class="admin-container">

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- ===== TABS ===== -->
            <div class="tabs">
                <button class="tab-button" onclick="showTab('products')" id="products-tab-btn">
                    <i class="fas fa-box"></i> Productbeheer
                </button>
                <button class="tab-button" onclick="showTab('categories')" id="categories-tab-btn">
                    <i class="fas fa-tags"></i> Categorieën
                </button>

                <button class="tab-button" onclick="showTab('export')" id="export-tab-btn">
                    <i class="fas fa-download"></i> Export/Import
                </button>
                <button class="tab-button" onclick="showTab('settings')" id="settings-tab-btn">
                    <i class="fas fa-cog"></i> Instellingen
                </button>
                <button class="tab-button" onclick="showTab('cloaking')" id="cloaking-tab-btn">
                    <i class="fas fa-shield-alt"></i> Cloaking
                </button>
                <button class="tab-button" onclick="showTab('monitoring')" id="monitoring-tab-btn">
                    <i class="fas fa-chart-line"></i> Live Monitoring
                </button>
            </div>

            <!-- ===== PRODUCTS TAB ===== -->
            <div id="products-tab" class="tab-content">
                <!-- ===== ADD PRODUCT SECTION ===== -->
               <section class="admin-section">
    <h2 class="section-title">
        <i class="fas fa-plus-circle"></i>
        Add New Product
    </h2>

    <form method="POST" class="admin-form">
        <input type="hidden" name="action" value="add_product">

        <!-- Product Info Card -->
        <div class="form-card">
            <h3 class="form-card-title">Product Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="name_nl">Product Name (Dutch) *</label>
                    <input type="text" id="name_nl" name="name_nl" required>
                </div>
                <div class="form-group">
                    <label for="name_fr">Product Name (French) *</label>
                    <input type="text" id="name_fr" name="name_fr" required>
                </div>
                <div class="form-group">
                    <label for="price">Price (€) *</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="old_price">Old Price (€)</label>
                    <input type="number" id="old_price" name="old_price" step="0.01" placeholder="Leave empty if no old price">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" onchange="toggleCustomCategory()">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="custom">+ Add New Category</option>
                    </select>
                    <input type="text" id="custom_category" name="custom_category"
                        placeholder="Type new category name..." style="display:none; margin-top:10px;">
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="0">
                </div>
                <div class="form-group">
                    <label for="shopify_variant_id">Shopify Variant ID</label>
                    <input type="text" id="shopify_variant_id" name="shopify_variant_id" placeholder="e.g. 55328322027855">
                </div>
            </div>
        </div>

        <!-- Images Card -->
        <div class="form-card">
            <h3 class="form-card-title">Images</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="image_url">Main Image URL</label>
                    <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label for="additional_images">Additional Images</label>
                    <textarea id="additional_images" name="additional_images" rows="2"
                        placeholder="https://example.com/image2.jpg, https://example.com/image3.jpg"></textarea>
                </div>
            </div>
        </div>

        <!-- Variants Card -->
        <div class="form-card">
            <h3 class="form-card-title">Variants</h3>
            <div id="variants-container">
                <div class="variant-row">
                    <input type="text" name="variant_name_nl[]" placeholder="Variant Name (Dutch)">
                    <input type="text" name="variant_name_fr[]" placeholder="Variant Name (French)">
                    <input type="url" name="variant_images[]" placeholder="Image URL">
                    <button type="button" class="btn-variant-remove" style="display:none;" onclick="removeVariantRow(this)">Remove</button>
                </div>
            </div>
            <button type="button" class="btn-variant-add" onclick="addVariantRow()">+ Add Variant</button>
        </div>

        <!-- Description Card -->
        <div class="form-card">
            <h3 class="form-card-title">Product Descriptions</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="description_nl">Description (Dutch)</label>
                    <div id="editor_nl" class="quill-editor"></div>
                    <textarea id="description_nl" name="description_nl" style="display:none;"></textarea>
                </div>
                <div class="form-group">
                    <label for="description_fr">Description (French)</label>
                    <div id="editor_fr" class="quill-editor"></div>
                    <textarea id="description_fr" name="description_fr" style="display:none;"></textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit" onclick="syncQuillEditors()">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
    </form>
</section>

<!-- Styles -->
<style>
    .admin-form { max-width: 1100px; margin: auto; font-family: 'Segoe UI', sans-serif; }
    .form-card { background: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .form-card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 8px; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .form-group label { display:block; font-weight: 600; margin-bottom: 6px; color: #333; }
    .form-group input, .form-group textarea, .form-group select {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; transition: border 0.2s;
    }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
        border-color: #28a745; outline: none; box-shadow: 0 0 0 2px rgba(40,167,69,0.15);
    }

    /* Variant rows */
    .variant-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 15px;
        margin-bottom: 15px;
    }
    .btn-variant-add, .btn-variant-remove {
        background: #28a745; color: white; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;
    }
    .btn-variant-remove { background: #dc3545; }
    .btn-variant-add { margin-top: 10px; }

    /* Description editors */
    .quill-editor { height: 220px; background:#fff; border:1px solid #ddd; border-radius:6px; }

    /* Submit */
    .form-actions { text-align: right; margin-top: 20px; }
    .btn-submit {
        padding: 12px 25px; background: linear-gradient(135deg, #28a745, #218838);
        color: white; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 16px; transition: 0.2s;
    }
    .btn-submit:hover { background: linear-gradient(135deg, #218838, #1e7e34); }
</style>



                <!-- Quill Scripts -->
                <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
                <script>
                    var quillNl = new Quill('#editor_nl', { theme: 'snow' });
                    var quillFr = new Quill('#editor_fr', { theme: 'snow' });

                    function syncQuillEditors() {
                        document.querySelector('#description_nl').value = quillNl.root.innerHTML;
                        document.querySelector('#description_fr').value = quillFr.root.innerHTML;
                    }
                </script>


                <!-- ===== PRODUCTS LIST ===== -->
              <section class="admin-section">
    <h2 class="section-title">
        <i class="fas fa-list"></i>
        Product Overview (<?php echo count($products); ?> products)
    </h2>

    <?php if (empty($products)): ?>
        <p>No products added yet. Add your first product!</p>
    <?php else: ?>
        <form method="POST" id="bulkDeleteForm" onsubmit="return confirmBulkDelete()">
            <input type="hidden" name="action" value="delete_multiple_products">

            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <button type="button" onclick="selectAllProducts()" class="btn btn-secondary">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button type="button" onclick="deselectAllProducts()" class="btn btn-secondary">
                    <i class="fas fa-square"></i> Deselect All
                </button>
                <button type="submit" id="bulkDeleteBtn" class="btn btn-danger" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
            </div>

            <!-- Products Table -->
            <div class="table-wrapper">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Shopify ID</th>
                            <th>Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="product_ids[]"
                                        value="<?php echo $product['id']; ?>" class="product-checkbox"
                                        onchange="updateBulkDeleteButton()">
                                </td>
                                <td class="product-image-cell">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Product"
                                            onerror="this.style.display='none'">
                                    <?php else: ?>
                                        <i class="fas fa-image placeholder-icon"></i>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($product['translations']['nl']['name'] ?? $product['name']); ?></strong></td>
                                <td>€<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td><code><?php echo htmlspecialchars($product['shopify_variant_id']); ?></code></td>
                                <td>
                                    <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" onclick="editProduct(<?php echo $product['id']; ?>)"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this product?')">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php endif; ?>
</section>

<!-- Styles -->
<style>
    .bulk-actions {
        margin-bottom: 15px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn-sm {
        font-size: 13px;
        padding: 6px 12px;
    }
    .table-wrapper {
        overflow-x: auto;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .products-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .products-table th, .products-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }
    .products-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    .products-table tbody tr:hover {
        background: #fdfdfd;
    }
    .product-image-cell img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
    }
    .placeholder-icon {
        font-size: 24px;
        color: #bbb;
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    .actions-cell {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
</style>

            </div>

            <!-- ===== CATEGORIES TAB ===== -->
            <div id="categories-tab" class="tab-content">
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        Nieuwe Categorie Toevoegen
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_category">

                        <div style="display: flex; gap: 15px; align-items: end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label for="category_name">Categorienaam *</label>
                                <input type="text" id="category_name" name="category_name" required
                                    placeholder="bijv. Tablets, Monitoren, etc.">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Categorie Toevoegen
                            </button>
                        </div>
                    </form>
                </section>

                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i>
                        Bestaande Categorieën (<?php echo count($categories); ?>)
                    </h2>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($categories as $category): ?>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; 
                                        padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($category); ?></span>
                                <form method="POST" style="display: inline;"
                                    onsubmit="return confirm('Weet je zeker dat je categorie \'<?php echo htmlspecialchars($category); ?>\' wilt verwijderen?')">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_name"
                                        value="<?php echo htmlspecialchars($category); ?>">
                                    <button type="submit" class="btn btn-danger"
                                        style="padding: 6px 10px; font-size: 12px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>



            <!-- ===== SETTINGS TAB ===== -->
            <div id="settings-tab" class="tab-content">
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-cog"></i>
                        Webshop Instellingen
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">

                        <div class="form-group">
                            <label for="shopify_shop_url">Shopify Winkel URL *</label>
                            <input type="text" id="shopify_shop_url" name="shopify_shop_url"
                                value="<?php echo htmlspecialchars($shopify_shop_url); ?>" required
                                placeholder="bijv. yourshop.myshopify.com">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Instellingen Opslaan
                        </button>
                    </form>
                </section>
            </div>

            <!-- ===== CLOAKING TAB ===== -->
            <div id="cloaking-tab" class="tab-content">
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        Cloaking Beheer
                    </h2>
                    <p class="section-description">Beheer cloaking instellingen voor TikTok en geografische filtering
                    </p>

                    <?php if (isset($success_message) && strpos($success_message, 'Cloaking') !== false): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>

                    <?php if (isset($error_message) && strpos($error_message, 'cloaking') !== false): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="form-layout">
                        <input type="hidden" name="action" value="update_cloaking">

                        <div class="form-row">
                            <div class="form-group">
                                <h3>🌍 Algemene Cloaking Instellingen</h3>

                                <label style="display: flex; align-items: center; margin-bottom: 15px;">
                                    <input type="checkbox" name="cloaking_enabled" <?php echo $cloakingConfig['enabled'] ? 'checked' : ''; ?> style="margin-right: 10px;">
                                    Cloaking systeem inschakelen
                                </label>
                                <small>Schakel het gehele cloaking systeem in of uit</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <h3>🌎 Land-gebaseerde Filtering</h3>

                                <label>Toegestane landen (selecteer meerdere):</label>
                                <div
                                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 8px; margin: 15px 0; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                    <?php
                                    $countries = [
                                        'NL' => 'Nederland',
                                        'BE' => 'België',
                                        'DE' => 'Duitsland',
                                        'FR' => 'Frankrijk',
                                        'GB' => 'Verenigd Koninkrijk',
                                        'US' => 'Verenigde Staten',
                                        'CA' => 'Canada',
                                        'AU' => 'Australië',
                                        'ES' => 'Spanje',
                                        'IT' => 'Italië',
                                        'PL' => 'Polen',
                                        'CZ' => 'Tsjechië',
                                        'AT' => 'Oostenrijk',
                                        'CH' => 'Zwitserland',
                                        'DK' => 'Denemarken',
                                        'SE' => 'Zweden',
                                        'NO' => 'Noorwegen'
                                    ];

                                    foreach ($countries as $code => $name):
                                        ?>
                                        <label
                                            style="display: flex; align-items: center; font-size: 14px; margin-bottom: 5px;">
                                            <input type="checkbox" name="allowed_countries[]" value="<?php echo $code; ?>"
                                                <?php echo in_array($code, $cloakingConfig['allowed_countries']) ? 'checked' : ''; ?> style="margin-right: 8px;">
                                            <?php echo $code; ?> - <?php echo $name; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <small>Bezoekers uit andere landen worden doorgestuurd naar de cloaking pagina</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <h3>🛡️ IP Whitelist</h3>

                                <!-- Mijn IP toevoegen sectie -->
                                <div
                                    style="background: #e3f2fd; border: 1px solid #90caf9; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                                    <h4 style="margin: 0 0 10px 0; color: #1565c0;">⚡ Snel Toevoegen</h4>
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="font-weight: bold; color: #1565c0;">Jouw IP:</span>
                                            <code id="currentIP"
                                                style="background: #fff; padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd;">
                                                <?php
                                                require_once '../cloaking.php';
                                                $tempCloaking = new CloakingSystem();
                                                echo htmlspecialchars($tempCloaking->getVisitorIP());
                                                ?>
                                            </code>
                                            <button type="button" id="addMyIP" onclick="addMyIPToWhitelist()"
                                                style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                                                ➕ Mijn IP Toevoegen
                                            </button>
                                            <button type="button" onclick="refreshMyIP()"
                                                style="background: #6c757d; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                                                🔄
                                            </button>
                                        </div>
                                    </div>
                                    <small style="color: #666; display: block; margin-top: 8px;">
                                        💡 <strong>Tip:</strong> Voeg je eigen IP toe om altijd toegang te hebben,
                                        ongeacht land-instellingen.
                                    </small>
                                </div>

                                <label for="ip_whitelist">IP-adressen die altijd toegang hebben:</label>
                                <textarea name="ip_whitelist" rows="6"
                                    placeholder="Voer één IP-adres per regel in:&#10;192.168.1.100&#10;10.0.0.0/24&#10;203.0.113.*&#10;127.0.0.1"
                                    class="form-input" style="font-family: monospace;"><?php
                                    if (isset($cloakingConfig['ip_whitelist']) && is_array($cloakingConfig['ip_whitelist'])) {
                                        echo htmlspecialchars(implode("\n", $cloakingConfig['ip_whitelist']));
                                    }
                                    ?></textarea>
                                <small>
                                    <strong>Ondersteunde formaten:</strong><br>
                                    • Exact IP: <code>192.168.1.100</code><br>
                                    • CIDR range: <code>192.168.1.0/24</code><br>
                                    • Wildcard: <code>192.168.1.*</code><br>
                                    IP-adressen op deze lijst krijgen altijd toegang, ongeacht land-instellingen.
                                </small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <h3>🔗 Cloaking Doorverwijzing</h3>

                                <label for="cloaking_redirect_url">URL naar cloaking pagina:</label>
                                <input type="text" name="cloaking_redirect_url"
                                    value="<?php echo htmlspecialchars($cloakingConfig['cloaking_redirect_url'] ?? 'alternative_page.php'); ?>"
                                    placeholder="bijv. alternative_page.php of https://example.com/cloaking"
                                    class="form-input">
                                <small>De pagina waarnaar bezoekers uit niet-toegestane landen worden
                                    doorgestuurd</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <h3>🔒 URL Verberging</h3>

                                <label>
                                    <input type="checkbox" name="hide_cloaking_url" value="1" <?php echo ($cloakingConfig['hide_cloaking_url'] ?? true) ? 'checked' : ''; ?>>
                                    Verberg cloaking URL (professioneler)
                                </label>
                                <small>
                                    <strong>Aanbevolen: ✅ Aan</strong><br>
                                    • <strong>Aan:</strong> URL blijft https://sklepoll.com/ (professioneel)<br>
                                    • <strong>Uit:</strong> URL toont https://sklepoll.com/alternative_page.php
                                    (zichtbaar)
                                </small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <h3>☁️ Cloud Provider Blocking</h3>

                                <label style="display: flex; align-items: center; margin-bottom: 15px;">
                                    <input type="checkbox" name="block_cloud_providers" value="1" <?php echo ($cloakingConfig['block_cloud_providers'] ?? false) ? 'checked' : ''; ?>
                                        style="margin-right: 10px;">
                                    Blokkeer bekende cloudproviders (anti-bot bescherming)
                                </label>

                                <div
                                    style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; padding: 15px; margin-top: 10px;">
                                    <h4 style="margin-top: 0; color: #2e7d32;">🎯 Geblokkeerde Providers</h4>
                                    <div
                                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-bottom: 10px;">
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Amazon AWS</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Google Cloud</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Microsoft Azure</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            DigitalOcean</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Linode</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Hetzner</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            OVHcloud</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Alibaba Cloud</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Oracle Cloud</span>
                                        <span
                                            style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e0e0e0;">☁️
                                            Tencent Cloud</span>
                                    </div>
                                    <p style="color: #2e7d32; margin: 0; font-size: 14px;">
                                        <strong>Doel:</strong> Voorkom dat TikTok-bots, scrapers en crawlers vanuit
                                        cloudproviders je echte website kunnen bereiken. Deze systemen draaien vaak op
                                        AWS, Google Cloud, etc.
                                    </p>
                                </div>

                                <small style="color: #666;">
                                    <strong>Hoe het werkt:</strong><br>
                                    • IP-adressen van bekende cloudproviders worden automatisch gedetecteerd<br>
                                    • Deze bezoekers krijgen standaard de cloaking pagina te zien<br>
                                    • IP's op de whitelist hebben nog steeds voorrang (blijven toegang houden)<br>
                                    • Helpt tegen geautomatiseerde verificatiebots van TikTok en andere platforms
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Cloaking Instellingen Opslaan
                            </button>
                        </div>
                    </form>

                    <div style="margin-top: 30px;">
                        <h3>📊 Cloaking Statistieken</h3>
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 15px 0;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">Status</h4>
                                <p
                                    style="margin: 0; font-size: 18px; font-weight: bold; color: <?php echo $cloakingConfig['enabled'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $cloakingConfig['enabled'] ? '🟢 Actief' : '🔴 Inactief'; ?>
                                </p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">Toegestane landen</h4>
                                <p style="margin: 0; font-size: 18px; font-weight: bold; color: #007bff;">
                                    <?php echo count($cloakingConfig['allowed_countries']); ?>
                                </p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">IP Whitelist</h4>
                                <p style="margin: 0; font-size: 18px; font-weight: bold; color: #28a745;">
                                    <?php echo isset($cloakingConfig['ip_whitelist']) ? count($cloakingConfig['ip_whitelist']) : 0; ?>
                                </p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">URL Verberging</h4>
                                <p
                                    style="margin: 0; font-size: 14px; font-weight: bold; color: <?php echo ($cloakingConfig['hide_cloaking_url'] ?? true) ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo ($cloakingConfig['hide_cloaking_url'] ?? true) ? '🔒 Verborgen' : '👁️ Zichtbaar'; ?>
                                </p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">Cloud Blocking</h4>
                                <p
                                    style="margin: 0; font-size: 14px; font-weight: bold; color: <?php echo ($cloakingConfig['block_cloud_providers'] ?? false) ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo ($cloakingConfig['block_cloud_providers'] ?? false) ? '☁️ Actief' : '🔓 Uit'; ?>
                                </p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                                <h4 style="margin: 0 0 10px 0; color: #666;">Doorverwijzing</h4>
                                <p style="margin: 0; font-size: 12px; font-weight: bold; color: #6c757d;">
                                    <?php echo htmlspecialchars($cloakingConfig['cloaking_redirect_url'] ?? 'alternative_page.php'); ?>
                                </p>
                            </div>
                        </div>

                        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                            <div
                                style="margin-top: 20px; padding: 15px; background: #e3f2fd; border: 1px solid #90caf9; border-radius: 8px;">
                                <h4>🔍 Debug Informatie</h4>
                                <p><strong>Config bestand:</strong> <?php echo realpath('../cloaking_config.json'); ?></p>
                                <p><strong>Laatste wijziging:</strong>
                                    <?php echo file_exists('../cloaking_config.json') ? date('Y-m-d H:i:s', filemtime('../cloaking_config.json')) : 'Niet gevonden'; ?>
                                </p>
                                <p><strong>IP Whitelist (raw):</strong>
                                    <code><?php echo htmlspecialchars(var_export($cloakingConfig['ip_whitelist'], true)); ?></code>
                                </p>
                                <p><strong>URL Verberging:</strong>
                                    <?php echo ($cloakingConfig['hide_cloaking_url'] ?? true) ? 'AAN (URLs worden verborgen)' : 'UIT (URLs zijn zichtbaar)'; ?>
                                </p>

                                <?php
                                // Geolocation accuracy analysis
                                $geolocationLogFile = '../geolocation_accuracy.json';
                                if (file_exists($geolocationLogFile)) {
                                    $geolocationLogs = json_decode(file_get_contents($geolocationLogFile), true) ?? [];
                                    $failures = array_filter($geolocationLogs, function ($log) {
                                        return $log['type'] === 'failure'; });
                                    $discrepancies = array_filter($geolocationLogs, function ($log) {
                                        return $log['type'] === 'discrepancy'; });

                                    echo '<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">';
                                    echo '<h5>🌍 Geolocation Nauwkeurigheid (laatste 500 checks)</h5>';
                                    echo '<p><strong>Totaal logs:</strong> ' . count($geolocationLogs) . '</p>';
                                    echo '<p><strong>API Failures:</strong> ' . count($failures) . ' (' . round((count($failures) / max(count($geolocationLogs), 1)) * 100, 1) . '%)</p>';
                                    echo '<p><strong>Discrepanties:</strong> ' . count($discrepancies) . ' (' . round((count($discrepancies) / max(count($geolocationLogs), 1)) * 100, 1) . '%)</p>';

                                    if (!empty($discrepancies)) {
                                        echo '<h6>🔍 Recente Discrepanties:</h6>';
                                        echo '<div style="max-height: 150px; overflow-y: auto; font-size: 12px;">';
                                        foreach (array_slice($discrepancies, -5) as $disc) {
                                            echo '<div style="margin: 5px 0; padding: 5px; background: #f8f9fa; border-radius: 3px;">';
                                            echo '<strong>IP:</strong> ' . htmlspecialchars($disc['ip']) . ' | ';
                                            echo '<strong>Bronnen:</strong> ' . implode(', ', $disc['detected_countries']) . ' | ';
                                            echo '<strong>Gekozen:</strong> ' . htmlspecialchars($disc['chosen_country']);
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<p><strong>Geolocation logs:</strong> Nog geen data beschikbaar</p>';
                                }
                                ?>

                                <p><a href="?tab=cloaking">🔙 Verberg Debug</a></p>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 15px;">
                                <a href="?tab=cloaking&debug=1"
                                    style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;">🔍
                                    Toon Debug Info</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div
                        style="margin-top: 30px; background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; padding: 20px;">
                        <h3 style="margin-top: 0; color: #2e7d32;">✅ URL Verberging Actief</h3>
                        <p style="color: #2e7d32; margin-bottom: 15px;">
                            <strong>Professionele cloaking:</strong> Bezoekers zien altijd de originele URL (bijv.
                            https://sklepoll.com/)
                            ook als ze de cloaking pagina te zien krijgen. Dit zorgt voor een professionelere
                            uitstraling.
                        </p>
                        <p style="color: #2e7d32; margin-bottom: 0;">
                            <strong>Hoe het werkt:</strong> In plaats van een redirect worden bezoekers uit
                            niet-toegestane landen
                            intern doorgestuurd naar de alternative page, zonder dat de URL verandert.
                        </p>
                    </div>

                    <div
                        style="margin-top: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px;">
                        <h3 style="margin-top: 0; color: #856404;">⚠️ Waarschuwing</h3>
                        <p style="margin-bottom: 0; color: #856404;">
                            <strong>Let op:</strong> Cloaking kan leiden tot problemen met advertentieplatformen en
                            zoekmachines.
                            Gebruik deze functie alleen als je begrijpt wat de gevolgen kunnen zijn.
                            Test altijd grondig voordat je het systeem activeert.
                        </p>
                    </div>
                </section>
            </div>

            <!-- ===== EXPORT/IMPORT TAB ===== -->
            <div id="export-tab" class="tab-content">
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-download"></i>
                        Producten Exporteren
                    </h2>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: #28a745;">
                            <i class="fas fa-info-circle"></i> Export Informatie
                        </h3>
                        <p>De export bevat alle producten inclusief:</p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Productgegevens (naam, prijs, beschrijving, etc.)</li>
                            <li>Alle afbeeldingen (worden lokaal opgeslagen)</li>
                            <li>Categorieën</li>
                            <li>Webshop instellingen</li>
                            <li>Varianten en extra afbeeldingen</li>
                        </ul>
                        <p><strong>Export datum:</strong> <?php echo date('d-m-Y H:i:s'); ?></p>
                        <p><strong>Totaal producten:</strong> <?php echo count($products); ?></p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="export_products">
                        <button type="submit" class="btn btn-success" style="font-size: 16px; padding: 12px 24px;">
                            <i class="fas fa-download"></i>
                            Alle Producten Exporteren (JSON)
                        </button>
                    </form>
                </section>

                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-upload"></i>
                        Producten Importeren
                    </h2>

                    <div
                        style="background: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffeaa7;">
                        <h3 style="margin-top: 0; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> Import Waarschuwing
                        </h3>
                        <p><strong>Let op:</strong> Bij het importeren worden afbeeldingen automatisch gedownload en
                            lokaal opgeslagen.</p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Producten met dezelfde Shopify Variant ID worden overgeslagen (tenzij overschrijven is
                                aangevinkt)</li>
                            <li>Nieuwe producten krijgen automatisch nieuwe IDs</li>
                            <li>Alle afbeeldingen worden gedownload naar de uploads/ map</li>
                        </ul>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_products">

                        <div class="form-group">
                            <label for="import_file">Selecteer Export Bestand (JSON) *</label>
                            <input type="file" id="import_file" name="import_file" accept=".json" required
                                style="padding: 10px; border: 2px dashed #ddd; border-radius: 8px; width: 100%;">
                        </div>

                        <div class="form-group" style="margin-top: 15px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" name="overwrite_existing" value="1" style="margin-right: 10px;">
                                Bestaande producten overschrijven (indienzelfde Shopify Variant ID)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 12px 24px;">
                            <i class="fas fa-upload"></i>
                            Producten Importeren
                        </button>
                    </form>
                </section>

                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i>
                        Backup Geschiedenis
                    </h2>

                    <div style="background: #e9ecef; padding: 15px; border-radius: 8px;">
                        <p style="margin: 0; color: #6c757d;">
                            <i class="fas fa-clock"></i>
                            Laatste export: <?php echo date('d-m-Y H:i:s'); ?>
                        </p>
                        <p style="margin: 5px 0 0 0; color: #6c757d;">
                            <i class="fas fa-box"></i>
                            Export bestanden worden automatisch gedownload naar je computer
                        </p>
                    </div>
                </section>
            </div>

            <!-- ===== LIVE MONITORING TAB ===== -->
            <div id="monitoring-tab" class="tab-content">
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Live Monitoring Dashboard
                    </h2>
                    <p class="section-description">Realtime inzicht in website verkeer en cloaking activiteit</p>

                    <!-- Debug Section -->
                    <?php
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        require_once '../cloaking.php';
                        $debugCloaking = new CloakingSystem();
                        $debugData = $debugCloaking->getLiveMonitoringData(10);
                        $debugStats = $debugCloaking->getMonitoringStats();

                        echo '<div style="background: #fffbf0; border: 2px solid #ff9800; padding: 20px; margin-bottom: 20px; border-radius: 8px;">';
                        echo '<h3>🔧 Debug Informatie</h3>';
                        echo '<p><strong>Monitoring bestand:</strong> ' . (file_exists('../live_monitoring.json') ? '✅ Bestaat' : '❌ Niet gevonden') . '</p>';
                        echo '<p><strong>Data entries:</strong> ' . count($debugData) . '</p>';
                        echo '<p><strong>Stats totaal:</strong> ' . $debugStats['total'] . '</p>';
                        echo '<p><strong>Laatste 24u:</strong> ' . $debugStats['last_24h'] . '</p>';
                        echo '<h4>Laatste 3 entries:</h4>';
                        echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;">';
                        echo htmlspecialchars(json_encode(array_slice($debugData, 0, 3), JSON_PRETTY_PRINT));
                        echo '</pre>';
                        echo '<p><a href="?tab=monitoring">❌ Verberg Debug</a> | <a href="../test_monitoring_ajax.php" target="_blank">🧪 Test AJAX</a> | <a href="../force_monitoring_data.php" target="_blank">🔧 Force Data</a></p>';
                        echo '</div>';
                    } else {
                        echo '<div style="text-align: right; margin-bottom: 10px;">';
                        echo '<a href="?tab=monitoring&debug=1" style="background: #ff9800; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;">🔧 Debug Mode</a>';
                        echo '</div>';
                    }
                    ?>

                    <!-- Monitoring Controls -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                        <div
                            style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
                            <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                                <button onclick="refreshMonitoringData()" class="btn btn-primary"
                                    style="padding: 8px 15px;">
                                    <i class="fas fa-sync-alt"></i> Vernieuwen
                                </button>

                                <button onclick="clearAllMonitoringData()" class="btn"
                                    style="background: #dc3545; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                                    <i class="fas fa-trash-alt"></i> Verwijder Alle Data
                                </button>

                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="autoRefresh" checked style="margin-right: 8px;">
                                    <span style="font-size: 14px;">Auto-refresh (30s)</span>
                                </label>

                                <select id="timeFilter" onchange="applyFilters()"
                                    style="padding: 6px 10px; border-radius: 5px; border: 1px solid #ddd;">
                                    <option value="">Alle tijd</option>
                                    <option value="1">Laatste 1 uur</option>
                                    <option value="6">Laatste 6 uur</option>
                                    <option value="24" selected>Laatste 24 uur</option>
                                    <option value="168">Laatste 7 dagen</option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="searchFilter" placeholder="Zoek IP of land..."
                                    style="padding: 6px 10px; border-radius: 5px; border: 1px solid #ddd; width: 200px;"
                                    onkeyup="applyFilters()">

                                <select id="statusFilter" onchange="applyFilters()"
                                    style="padding: 6px 10px; border-radius: 5px; border: 1px solid #ddd;">
                                    <option value="">Alle statussen</option>
                                    <option value="toegelaten">✅ Toegelaten</option>
                                    <option value="cloaked">🔄 Cloaked</option>
                                    <option value="geblokkeerd">❌ Geblokkeerd</option>
                                    <option value="test">🧪 Test</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div id="monitoringStats"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
                        <!-- Stats worden hier via JavaScript geladen -->
                    </div>

                    <!-- Live Activity Table -->
                    <div
                        style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
                        <div
                            style="padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; color: #333;">
                                <i class="fas fa-globe"></i> Recente Activiteit
                            </h3>
                            <div id="liveIndicator"
                                style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #28a745;">
                                <div
                                    style="width: 8px; height: 8px; background: #28a745; border-radius: 50%; animation: pulse 2s infinite;">
                                </div>
                                <span>Live</span>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table id="monitoringTable" style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th
                                            style="padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #dee2e6;">
                                            <i class="fas fa-clock"></i> Tijd
                                        </th>
                                        <th
                                            style="padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #dee2e6;">
                                            <i class="fas fa-map-marker-alt"></i> IP / Land
                                        </th>
                                        <th
                                            style="padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #dee2e6;">
                                            <i class="fas fa-wifi"></i> Netwerk
                                        </th>
                                        <th
                                            style="padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #dee2e6;">
                                            <i class="fas fa-info-circle"></i> Status
                                        </th>
                                        <th
                                            style="padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #dee2e6;">
                                            <i class="fas fa-file-alt"></i> Beschrijving
                                        </th>
                                        <th
                                            style="padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 1px solid #dee2e6;">
                                            <i class="fas fa-link"></i> Pagina
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="monitoringTableBody">
                                    <!-- Data wordt hier via JavaScript geladen -->
                                </tbody>
                            </table>
                        </div>

                        <div id="monitoringPagination"
                            style="padding: 15px; text-align: center; border-top: 1px solid #eee; background: #f8f9fa;">
                            <!-- Pagination wordt hier via JavaScript geladen -->
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- ===== EDIT PRODUCT MODAL ===== -->
    <div id="editModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90%; overflow-y: auto;">
            <h3 style="margin-bottom: 20px;">Product Bewerken</h3>

            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="product_id" id="edit_product_id">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">Productnaam *</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_price">Prijs (zł) *</label>
                        <input type="number" id="edit_price" name="price" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_old_price">Oude prijs (zł) - voor vergelijking</label>
                        <input type="number" id="edit_old_price" name="old_price" step="0.01"
                            placeholder="Laat leeg als er geen oude prijs is">
                    </div>

                    <div class="form-group">
                        <label for="edit_category">Categorie</label>
                        <select id="edit_category" name="category">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_stock_quantity">Voorraad</label>
                        <input type="number" id="edit_stock_quantity" name="stock_quantity">
                    </div>

                    <div class="form-group">
                        <label for="edit_shopify_variant_id">Shopify Variant ID *</label>
                        <input type="text" id="edit_shopify_variant_id" name="shopify_variant_id" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_image_url">Hoofdafbeelding URL</label>
                        <input type="url" id="edit_image_url" name="image_url">
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_additional_images">Extra afbeeldingen (gescheiden door komma's)</label>
                    <textarea id="edit_additional_images" name="additional_images" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_description">Beschrijving (Quill Editor - Ctrl+V + Klik afbeelding voor
                        grootte)</label>
                    <textarea id="edit_description" name="description" rows="10"></textarea>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary"
                        style="margin-right: 10px;">
                        Annuleren
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Product Bijwerken
                    </button>
                </div>
            </form>
        </div>
    </div>



    <script>
        const products = <?php echo json_encode($products); ?>;

        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');

            // Add active class to correct button
            const targetButton = document.querySelector(`[onclick="showTab('${tabName}')"]`);
            if (targetButton) {
                targetButton.classList.add('active');
            }

            // Save active tab to localStorage
            localStorage.setItem('activeTab', tabName);
        }

        // Load active tab on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Check for tab parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');

            if (tabParam) {
                // If tab specified in URL, use that
                showTab(tabParam);
            } else {
                // Otherwise, check localStorage for last active tab
                const savedTab = localStorage.getItem('activeTab');
                if (savedTab) {
                    showTab(savedTab);
                }
            }
        });

        function editProduct(productId) {
            const product = products.find(p => p.id == productId);
            if (!product) return;

            // Fill form with product data
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_old_price').value = product.old_price || '';
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_stock_quantity').value = product.stock_quantity;
            document.getElementById('edit_shopify_variant_id').value = product.shopify_variant_id;
            document.getElementById('edit_image_url').value = product.image_url || '';

            // Handle additional images
            if (product.images && product.images.length > 1) {
                const additionalImages = product.images.slice(1).join(', ');
                document.getElementById('edit_additional_images').value = additionalImages;
            } else {
                document.getElementById('edit_additional_images').value = '';
            }

            // Show modal first
            document.getElementById('editModal').style.display = 'block';

            // Initialize Quill for edit modal
            setTimeout(function () {
                // Verwijder bestaande Quill instance
                if (editDescriptionQuill) {
                    const container = document.getElementById('edit-description-editor');
                    if (container) {
                        container.remove();
                    }
                    editDescriptionQuill = null;
                }

                const editDescriptionElement = document.getElementById('edit_description');
                if (editDescriptionElement) {
                    // Verberg de originele textarea
                    editDescriptionElement.style.display = 'none';

                    // Maak een container voor Quill
                    const quillContainer = document.createElement('div');
                    quillContainer.id = 'edit-description-editor';
                    quillContainer.style.minHeight = '150px';
                    editDescriptionElement.parentNode.insertBefore(quillContainer, editDescriptionElement.nextSibling);

                    // Initialiseer Quill met image resize
                    editDescriptionQuill = new Quill('#edit-description-editor', {
                        theme: 'snow',
                        placeholder: 'Bewerk productbeschrijving... Ctrl+V voor afbeeldingen, klik erop om grootte aan te passen!',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                ['image'],
                                ['clean']
                            ],
                            imageResize: {
                                displaySize: true
                            }
                        }
                    });

                    // Sync Quill content met textarea
                    editDescriptionQuill.on('text-change', function () {
                        editDescriptionElement.value = editDescriptionQuill.root.innerHTML;
                    });

                    // Custom afbeelding upload handler
                    const toolbar = editDescriptionQuill.getModule('toolbar');
                    toolbar.addHandler('image', function () {
                        selectLocalImage(editDescriptionQuill);
                    });

                    // Set content after Quill is ready
                    setTimeout(function () {
                        if (product.description) {
                            editDescriptionQuill.root.innerHTML = product.description;
                        }
                    }, 100);
                }
            }, 100);
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        function toggleCustomCategory() {
            const categorySelect = document.getElementById('category');
            const customInput = document.getElementById('custom_category');

            if (categorySelect.value === 'custom') {
                customInput.style.display = 'block';
                customInput.required = true;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
            }
        }

        function addVariantRow() {
            const container = document.getElementById('variants-container');
            const row = document.createElement('div');
            row.className = 'variant-row';
            row.innerHTML = `<input type="text" name="variant_names[]" class="variant-input" placeholder="Variantnaam (bijv. Rood, 64GB, XL)">
                <input type="url" name="variant_images[]" class="variant-image-input" placeholder="Afbeelding-URL (optioneel)">
                <button type="button" class="btn-variant-remove" onclick="removeVariantRow(this)">-</button>`;
            container.appendChild(row);
            updateVariantRemoveButtons();
        }
        function removeVariantRow(btn) {
            btn.parentElement.remove();
            updateVariantRemoveButtons();
        }
        function updateVariantRemoveButtons() {
            const rows = document.querySelectorAll('#variants-container .variant-row');
            rows.forEach((row, idx) => {
                const btn = row.querySelector('.btn-variant-remove');
                btn.style.display = rows.length > 1 ? '' : 'none';
            });
        }
        document.addEventListener('DOMContentLoaded', updateVariantRemoveButtons);

        // Bulk Delete Functions
        function updateBulkDeleteButton() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            const selectedCount = document.getElementById('selectedCount');

            if (checkboxes.length > 0) {
                bulkDeleteBtn.style.display = 'inline-block';
                selectedCount.textContent = checkboxes.length;
            } else {
                bulkDeleteBtn.style.display = 'none';
            }

            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.product-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            if (allCheckboxes.length > 0) {
                selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
                selectAllCheckbox.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
            }
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.product-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            updateBulkDeleteButton();
        }

        function selectAllProducts() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateBulkDeleteButton();
        }

        function deselectAllProducts() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkDeleteButton();
        }

        function confirmBulkDelete() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Geen producten geselecteerd om te verwijderen.');
                return false;
            }

            const count = checkboxes.length;
            return confirm(`Weet je zeker dat je ${count} product${count > 1 ? 'en' : ''} wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.`);
        }

        // Quill.js Editor Initialisatie
        let descriptionQuill = null;
        let editDescriptionQuill = null;

        document.addEventListener('DOMContentLoaded', function () {
            // Registreer de image resize module
            if (window.ImageResize) {
                Quill.register('modules/imageResize', ImageResize.default);
            }

            // Toon intro bericht
            setTimeout(() => {
                showNotification('🎉 Quill.js geladen! Ctrl+V om afbeeldingen te plakken, klik erop voor eenvoudige grootte aanpassing!', 'success');
            }, 1000);

            setTimeout(function () {
                // Initialiseer Quill voor description
                const descriptionElement = document.getElementById('description');
                if (descriptionElement) {
                    // Verberg de originele textarea
                    descriptionElement.style.display = 'none';

                    // Maak een container voor Quill
                    const quillContainer = document.createElement('div');
                    quillContainer.id = 'description-editor';
                    quillContainer.style.minHeight = '200px';
                    descriptionElement.parentNode.insertBefore(quillContainer, descriptionElement.nextSibling);

                    // Initialiseer Quill met image resize
                    descriptionQuill = new Quill('#description-editor', {
                        theme: 'snow',
                        placeholder: 'Typ hier je productbeschrijving... Ctrl+V voor afbeeldingen, klik erop om grootte aan te passen!',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                ['image'],
                                ['clean']
                            ],
                            imageResize: {
                                displaySize: true
                            }
                        }
                    });

                    // Sync Quill content met textarea
                    descriptionQuill.on('text-change', function () {
                        descriptionElement.value = descriptionQuill.root.innerHTML;
                    });

                    // Laad bestaande content
                    if (descriptionElement.value) {
                        descriptionQuill.root.innerHTML = descriptionElement.value;
                    }

                    // Custom afbeelding upload handler
                    const toolbar = descriptionQuill.getModule('toolbar');
                    toolbar.addHandler('image', function () {
                        selectLocalImage(descriptionQuill);
                    });
                }
            }, 500);
        });

        // Functie voor afbeelding upload
        function selectLocalImage(quill) {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = function () {
                const file = input.files[0];
                if (file) {
                    uploadImageToServer(file, quill);
                }
            };
        }

        function uploadImageToServer(file, quill) {
            const formData = new FormData();
            formData.append('file', file);

            // Toon loading bericht
            showNotification('📤 Afbeelding wordt geüpload...', 'info');

            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Voeg afbeelding toe aan editor
                        const range = quill.getSelection();
                        quill.insertEmbed(range ? range.index : 0, 'image', result.url);
                        showNotification('✅ Afbeelding succesvol toegevoegd!', 'success');
                    } else {
                        showNotification('❌ Fout bij uploaden: ' + (result.error || 'Onbekende fout'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('❌ Netwerk fout: ' + error.message, 'error');
                });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');

            // Bepaal kleuren op basis van type
            let bgColor, textColor, borderColor;
            switch (type) {
                case 'success':
                    bgColor = '#d4edda';
                    textColor = '#155724';
                    borderColor = '#c3e6cb';
                    break;
                case 'error':
                    bgColor = '#f8d7da';
                    textColor = '#721c24';
                    borderColor = '#f5c6cb';
                    break;
                case 'warning':
                    bgColor = '#fff3cd';
                    textColor = '#856404';
                    borderColor = '#ffeaa7';
                    break;
                default:
                    bgColor = '#e3f2fd';
                    textColor = '#0d47a1';
                    borderColor = '#bbdefb';
            }

            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 10000;
                padding: 12px 20px; border-radius: 6px; font-size: 14px;
                background: ${bgColor};
                color: ${textColor};
                border: 1px solid ${borderColor};
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 350px;
                animation: slideInRight 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            // Auto remove met animatie
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // IP Whitelist functionaliteit
        function addMyIPToWhitelist() {
            const button = document.getElementById('addMyIP');
            const originalText = button.innerHTML;

            // Disable button en toon loading
            button.disabled = true;
            button.innerHTML = '⏳ Bezig...';
            button.style.background = '#6c757d';

            // AJAX request
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add_my_ip&ajax=1'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success
                        showNotification(data.message, 'success');

                        // Update de whitelist textarea
                        updateWhitelistTextarea(data.whitelist);

                        // Button feedback
                        button.innerHTML = '✅ Toegevoegd!';
                        button.style.background = '#28a745';

                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.disabled = false;
                            button.style.background = '#28a745';
                        }, 2000);
                    } else {
                        // Error
                        showNotification(data.message, data.already_exists ? 'warning' : 'error');

                        button.innerHTML = data.already_exists ? '✓ Al Toegevoegd' : '❌ Fout';
                        button.style.background = data.already_exists ? '#ffc107' : '#dc3545';

                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.disabled = false;
                            button.style.background = '#28a745';
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Onbekende fout opgetreden', 'error');

                    button.innerHTML = '❌ Fout';
                    button.style.background = '#dc3545';

                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.style.background = '#28a745';
                    }, 3000);
                });
        }

        function refreshMyIP() {
            const ipElement = document.getElementById('currentIP');
            const originalText = ipElement.textContent;

            // Toon loading
            ipElement.textContent = '🔄 Bezig...';
            ipElement.style.background = '#f8f9fa';

            // Update IP via externe service voor real-time IP
            fetch('https://api.ipify.org?format=json')
                .then(response => response.json())
                .then(data => {
                    ipElement.textContent = data.ip;
                    ipElement.style.background = '#d4edda';
                    showNotification('IP ververst: ' + data.ip, 'success');

                    // Reset background na 2 seconden
                    setTimeout(() => {
                        ipElement.style.background = '#fff';
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error refreshing IP:', error);
                    ipElement.textContent = originalText;
                    ipElement.style.background = '#f8d7da';
                    showNotification('Kon IP niet verversen', 'warning');

                    // Reset background na 2 seconden
                    setTimeout(() => {
                        ipElement.style.background = '#fff';
                    }, 2000);
                });
        }

        function updateWhitelistTextarea(whitelist) {
            const textarea = document.querySelector('textarea[name="ip_whitelist"]');
            if (textarea && Array.isArray(whitelist)) {
                textarea.value = whitelist.join('\n');

                // Visual feedback op de textarea
                textarea.style.background = '#d4edda';
                setTimeout(() => {
                    textarea.style.background = '';
                }, 1000);
            }
        }

        // ===== LIVE MONITORING FUNCTIONALITY =====
        let monitoringData = [];
        let currentPage = 1;
        let totalPages = 1;
        let autoRefreshInterval = null;

        function initializeMonitoring() {
            if (document.getElementById('monitoring-tab')) {
                refreshMonitoringData();

                // Start auto-refresh als checkbox aangevinkt is
                const autoRefreshCheckbox = document.getElementById('autoRefresh');
                if (autoRefreshCheckbox && autoRefreshCheckbox.checked) {
                    startAutoRefresh();
                }

                // Event listener voor auto-refresh checkbox
                if (autoRefreshCheckbox) {
                    autoRefreshCheckbox.addEventListener('change', function () {
                        if (this.checked) {
                            startAutoRefresh();
                        } else {
                            stopAutoRefresh();
                        }
                    });
                }
            }
        }

        function refreshMonitoringData() {
            const timeFilter = document.getElementById('timeFilter').value;
            const searchFilter = document.getElementById('searchFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;

            showMonitoringLoading();

            const formData = new FormData();
            formData.append('action', 'get_monitoring_data');
            formData.append('page', currentPage);
            formData.append('limit', 50);

            if (timeFilter) formData.append('hours', timeFilter);
            if (searchFilter) formData.append('search', searchFilter);
            if (statusFilter) formData.append('status', statusFilter);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log('Live Monitoring Response Status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Live Monitoring Raw Response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Live Monitoring Parsed Data:', data);

                        if (data.success) {
                            monitoringData = data.data;
                            totalPages = data.pages;
                            currentPage = data.page;

                            console.log('Live Monitoring Update - Data count:', data.data.length, 'Total:', data.total);

                            updateMonitoringStats(data.stats);
                            updateMonitoringTable(data.data);
                            updateMonitoringPagination(data.page, data.pages, data.total);
                        } else {
                            console.error('Live Monitoring Error:', data);
                            showNotification('Fout bij laden monitoring data: ' + (data.error || 'Onbekend'), 'error');
                        }
                    } catch (e) {
                        console.error('Live Monitoring JSON Parse Error:', e, 'Raw text:', text);
                        showNotification('Fout bij verwerken monitoring data', 'error');
                    }
                })
                .catch(error => {
                    console.error('Live Monitoring Fetch Error:', error);
                    showNotification('Netwerk fout bij laden monitoring data', 'error');
                });
        }

        function showMonitoringLoading() {
            const tableBody = document.getElementById('monitoringTableBody');
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="monitoring-table-loading">
                            <i class="fas fa-spinner"></i><br>
                            Monitoring data laden...
                        </td>
                    </tr>
                `;
            }
        }

        function updateMonitoringStats(stats) {
            const statsContainer = document.getElementById('monitoringStats');
            if (!statsContainer || !stats) return;

            statsContainer.innerHTML = `
                <div class="monitoring-stat-card green">
                    <h4>✅ Toegelaten</h4>
                    <p class="stat-number">${stats.toegelaten || 0}</p>
                </div>
                <div class="monitoring-stat-card orange">
                    <h4>🔄 Cloaked</h4>
                    <p class="stat-number">${stats.cloaked || 0}</p>
                </div>
                <div class="monitoring-stat-card red">
                    <h4>❌ Geblokkeerd</h4>
                    <p class="stat-number">${stats.geblokkeerd || 0}</p>
                </div>
                <div class="monitoring-stat-card blue">
                    <h4>📊 Laatste 24u</h4>
                    <p class="stat-number">${stats.last_24h || 0}</p>
                </div>
            `;
        }

        function updateMonitoringTable(data) {
            const tableBody = document.getElementById('monitoringTableBody');
            if (!tableBody) return;

            if (!data || data.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">
                            <i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            Geen monitoring data gevonden voor de geselecteerde filters.
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = data.map(entry => {
                const statusClass = `status-${entry.status}`;
                const statusIcon = getStatusIcon(entry.status);
                const timeAgo = getTimeAgo(entry.timestamp);

                return `
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                            <div style="font-weight: 500;">${entry.datetime}</div>
                            <small style="color: #6c757d;">${timeAgo}</small>
                        </td>
                        <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                            <div style="font-family: monospace; font-weight: bold;">${entry.ip}</div>
                            <div style="display: flex; align-items: center; margin-top: 4px;">
                                <img src="https://flagcdn.com/20x15/${entry.country.toLowerCase()}.png" 
                                     class="country-flag" onerror="this.style.display='none'" />
                                <span style="font-size: 13px; color: #6c757d;">${entry.country}</span>
                            </div>
                        </td>
                        <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                            <div style="font-size: 13px; font-weight: 500; color: #495057;">${entry.isp || '🌐 Unknown ISP'}</div>
                        </td>
                        <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                            <span class="status-badge ${statusClass}">
                                ${statusIcon} ${entry.status}
                            </span>
                        </td>
                        <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                            <div style="font-size: 13px;">${entry.description}</div>
                        </td>
                        <td style="padding: 12px; border-bottom: 1px solid #dee2e6;">
                            <code style="font-size: 11px; background: #f8f9fa; padding: 2px 4px; border-radius: 3px;">
                                ${entry.request_uri || '/'}
                            </code>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function getStatusIcon(status) {
            switch (status) {
                case 'toegelaten': return '✅';
                case 'cloaked': return '🔄';
                case 'geblokkeerd': return '❌';
                case 'test': return '🧪';
                default: return '❓';
            }
        }

        function getTimeAgo(timestamp) {
            const now = Math.floor(Date.now() / 1000);
            const diff = now - timestamp;

            if (diff < 60) return 'Net';
            if (diff < 3600) return Math.floor(diff / 60) + 'm geleden';
            if (diff < 86400) return Math.floor(diff / 3600) + 'u geleden';
            return Math.floor(diff / 86400) + 'd geleden';
        }

        function updateMonitoringPagination(page, pages, total) {
            const pagination = document.getElementById('monitoringPagination');
            if (!pagination) return;

            let paginationHtml = `<div style="color: #6c757d; margin-bottom: 10px;">
                Totaal: ${total} entries | Pagina ${page} van ${pages}
            </div>`;

            if (pages > 1) {
                paginationHtml += '<div style="display: flex; gap: 5px; justify-content: center;">';

                // Previous button
                if (page > 1) {
                    paginationHtml += `<button onclick="goToPage(${page - 1})" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">◀ Vorige</button>`;
                }

                // Page numbers
                const startPage = Math.max(1, page - 2);
                const endPage = Math.min(pages, page + 2);

                for (let i = startPage; i <= endPage; i++) {
                    const isActive = i === page ? 'btn-primary' : 'btn-secondary';
                    paginationHtml += `<button onclick="goToPage(${i})" class="btn ${isActive}" style="padding: 5px 10px; font-size: 12px;">${i}</button>`;
                }

                // Next button
                if (page < pages) {
                    paginationHtml += `<button onclick="goToPage(${page + 1})" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Volgende ▶</button>`;
                }

                paginationHtml += '</div>';
            }

            pagination.innerHTML = paginationHtml;
        }

        function clearAllMonitoringData() {
            if (!confirm('Ben je zeker dat je ALLE monitoring data wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'clear_monitoring_data');

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Alle monitoring data succesvol verwijderd!', 'success');
                        // Reset pagination en refresh de data
                        currentPage = 1;
                        refreshMonitoringData();
                    } else {
                        showNotification('Fout bij verwijderen: ' + (data.error || 'Onbekende fout'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error clearing monitoring data:', error);
                    showNotification('Netwerk fout bij verwijderen van data', 'error');
                });
        }

        function goToPage(page) {
            currentPage = page;
            refreshMonitoringData();
        }

        function applyFilters() {
            currentPage = 1; // Reset naar eerste pagina bij filtering
            refreshMonitoringData();
        }

        function startAutoRefresh() {
            stopAutoRefresh(); // Stop existing interval
            autoRefreshInterval = setInterval(refreshMonitoringData, 30000); // Refresh elke 30 seconden
        }

        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
        }

        // Initialize monitoring when tab is shown
        document.addEventListener('DOMContentLoaded', function () {
            // Check of we op de monitoring tab moeten starten
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'monitoring') {
                setTimeout(initializeMonitoring, 500);
            }
        });

        // Override showTab functie om monitoring te initialiseren
        const originalShowTab = window.showTab;
        window.showTab = function (tabName) {
            originalShowTab(tabName);

            if (tabName === 'monitoring') {
                setTimeout(initializeMonitoring, 100);
            } else {
                stopAutoRefresh(); // Stop auto-refresh als we andere tab selecteren
            }
        };
    </script>
</body>

</html>