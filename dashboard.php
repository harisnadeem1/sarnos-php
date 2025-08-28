<?php
session_start();
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
                // Varianten verwerken
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
                    'image_url' => $_POST['image_url'],
                    'additional_images' => $_POST['additional_images'],
                    'shopify_variant_id' => $_POST['shopify_variant_id'],
                    'category' => $category,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'variants' => $variants
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

            case 'update_settings':
                $db->updateSetting('shop_name', $_POST['shop_name']);
                $db->updateSetting('shop_description', $_POST['shop_description']);
                $db->updateSetting('shopify_shop_url', $_POST['shopify_shop_url']);
                $success_message = "Instellingen succesvol bijgewerkt!";
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
        }
    }
}

// Get current settings
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');
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
    <title>Admin Dashboard - <?php echo htmlspecialchars($shop_name); ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Quill.js - Professionele rijke teksteditor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <!-- Simple Image Size Control -->
    <script>
        // Eenvoudige afbeelding grootte controle
        const ImageResize = {
            default: function(quill, options) {
                let selectedImg = null;
                let sizeControl = null;
                
                quill.root.addEventListener('click', function(evt) {
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
                window.applyImageSize = function() {
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
                
                window.resetImageSize = function() {
                    if (!selectedImg) return;
                    
                    selectedImg.style.width = '';
                    selectedImg.style.height = '';
                    showNotification('🔄 Afbeelding teruggezet naar originele grootte', 'success');
                    hideControl();
                };
                
                window.hideImageControl = function() {
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(255,255,255,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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

        .variant-row { display: flex; gap: 8px; margin-bottom: 6px; }
        .variant-input { flex: 1.2; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .variant-image-input { flex: 1.5; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
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
        .btn-variant-remove { padding: 6px 12px; border-radius: 4px; border: none; background: #ff4757; color: white; font-weight: 600; cursor: pointer; }

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
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Hover effect voor afbeeldingen */
        .ql-editor img:hover {
            transform: scale(1.02) !important;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2) !important;
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
                Admin Dashboard - <?php echo htmlspecialchars($shop_name); ?>
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
                <button class="tab-button active" onclick="showTab('products')">
                    <i class="fas fa-box"></i> Productbeheer
                </button>
                <button class="tab-button" onclick="showTab('categories')">
                    <i class="fas fa-tags"></i> Categorieën
                </button>
                <button class="tab-button" onclick="showTab('settings')">
                    <i class="fas fa-cog"></i> Instellingen
                </button>
            </div>

            <!-- ===== PRODUCTS TAB ===== -->
            <div id="products-tab" class="tab-content active">
                <!-- ===== ADD PRODUCT SECTION ===== -->
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        Nieuw Product Toevoegen
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_product">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Productnaam *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Prijs (zł) *</label>
                                <input type="number" id="price" name="price" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="old_price">Oude prijs (zł) - voor vergelijking</label>
                                <input type="number" id="old_price" name="old_price" step="0.01" placeholder="Laat leeg als er geen oude prijs is">
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Categorie</label>
                                <select id="category" name="category" onchange="toggleCustomCategory()">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="custom">+ Nieuwe categorie toevoegen</option>
                                </select>
                                <input type="text" id="custom_category" name="custom_category" 
                                       placeholder="Typ nieuwe categorienaam..." 
                                       style="display: none; margin-top: 10px;">
                            </div>
                            
                            <div class="form-group">
                                <label for="stock_quantity">Voorraad</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="shopify_variant_id">Shopify Variant ID *</label>
                                <input type="text" id="shopify_variant_id" name="shopify_variant_id" required 
                                       placeholder="bijv. 55328322027855">
                            </div>
                            
                            <div class="form-group">
                                <label for="image_url">Hoofdafbeelding URL</label>
                                <input type="url" id="image_url" name="image_url" 
                                       placeholder="https://example.com/image.jpg">
                            </div>
                            
                            <div class="form-group">
                                <label for="additional_images">Extra afbeeldingen (gescheiden door komma's)</label>
                                <textarea id="additional_images" name="additional_images" rows="3" 
                                          placeholder="https://example.com/image2.jpg, https://example.com/image3.jpg"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Varianten (optioneel)</label>
                                <div id="variants-container">
                                    <div class="variant-row">
                                        <input type="text" name="variant_names[]" class="variant-input" placeholder="Variantnaam (bijv. Rood, 64GB, XL)">
                                        <input type="url" name="variant_images[]" class="variant-image-input" placeholder="Afbeelding-URL (optioneel)">
                                        <button type="button" class="btn-variant-remove" style="display:none;" onclick="removeVariantRow(this)">-</button>
                                    </div>
                                </div>
                                <button type="button" class="btn-variant-add" onclick="addVariantRow()">+ Variant toevoegen</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Beschrijving (Quill Editor - Ctrl+V + Klik afbeelding voor grootte)</label>
                            <textarea id="description" name="description" 
                                      placeholder="Voer een productbeschrijving in..." rows="10"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Product Toevoegen
                        </button>
                    </form>
                </section>

                <!-- ===== PRODUCTS LIST ===== -->
                <section class="admin-section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i>
                        Productoverzicht (<?php echo count($products); ?> producten)
                    </h2>
                    
                    <?php if (empty($products)): ?>
                        <p>Nog geen producten toegevoegd. Voeg je eerste product toe!</p>
                    <?php else: ?>
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Afbeelding</th>
                                    <th>Naam</th>
                                    <th>Prijs</th>
                                    <th>Categorie</th>
                                    <th>Voorraad</th>
                                    <th>Shopify ID</th>
                                    <th>Status</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="product-image-cell">
                                            <?php if ($product['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                     alt="Product" onerror="this.style.display='none'">
                                            <?php else: ?>
                                                <i class="fas fa-image" style="font-size: 24px; color: #ccc;"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                        <td><?php echo number_format($product['price'], 2); ?> zł</td>
                                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td><?php echo $product['stock_quantity']; ?></td>
                                        <td><code><?php echo htmlspecialchars($product['shopify_variant_id']); ?></code></td>
                                        <td>
                                            <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $product['is_active'] ? 'Actief' : 'Inactief'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="editProduct(<?php echo $product['id']; ?>)" 
                                                    class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; margin-right: 5px;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Weet je zeker dat je dit product wilt verwijderen?')">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
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
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($categories as $category): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; 
                                        padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($category); ?></span>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Weet je zeker dat je categorie \'<?php echo htmlspecialchars($category); ?>\' wilt verwijderen?')">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_name" value="<?php echo htmlspecialchars($category); ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;">
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
                        <i class="fas fa-store"></i>
                        Webshop Instellingen
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="shop_name">Webshop Naam *</label>
                                <input type="text" id="shop_name" name="shop_name" 
                                       value="<?php echo htmlspecialchars($shop_name); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="shopify_shop_url">Shopify Winkel URL *</label>
                                <input type="text" id="shopify_shop_url" name="shopify_shop_url" 
                                       value="<?php echo htmlspecialchars($shopify_shop_url); ?>" required
                                       placeholder="bijv. yourshop.myshopify.com">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="shop_description">Webshop Beschrijving</label>
                            <textarea id="shop_description" name="shop_description"><?php echo htmlspecialchars($shop_description); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Instellingen Opslaan
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <!-- ===== EDIT PRODUCT MODAL ===== -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90%; overflow-y: auto;">
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
                        <input type="number" id="edit_old_price" name="old_price" step="0.01" placeholder="Laat leeg als er geen oude prijs is">
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
                    <label for="edit_description">Beschrijving (Quill Editor - Ctrl+V + Klik afbeelding voor grootte)</label>
                    <textarea id="edit_description" name="description" rows="10"></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="margin-right: 10px;">
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
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
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
            setTimeout(function() {
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
                                 [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                 ['image'],
                                 ['clean']
                             ],
                             imageResize: {
                                 displaySize: true
                             }
                         }
                     });
                    
                    // Sync Quill content met textarea
                    editDescriptionQuill.on('text-change', function() {
                        editDescriptionElement.value = editDescriptionQuill.root.innerHTML;
                    });
                    
                    // Custom afbeelding upload handler
                    const toolbar = editDescriptionQuill.getModule('toolbar');
                    toolbar.addHandler('image', function() {
                        selectLocalImage(editDescriptionQuill);
                    });
                    
                    // Set content after Quill is ready
                    setTimeout(function() {
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
        document.getElementById('editModal').addEventListener('click', function(e) {
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

        // Quill.js Editor Initialisatie
        let descriptionQuill = null;
        let editDescriptionQuill = null;

                 document.addEventListener('DOMContentLoaded', function() {
            // Registreer de image resize module
            if (window.ImageResize) {
                Quill.register('modules/imageResize', ImageResize.default);
            }
            
            // Toon intro bericht
            setTimeout(() => {
                showNotification('🎉 Quill.js geladen! Ctrl+V om afbeeldingen te plakken, klik erop voor eenvoudige grootte aanpassing!', 'success');
            }, 1000);
            
            setTimeout(function() {
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
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['image'],
                                ['clean']
                            ],
                            imageResize: {
                                displaySize: true
                            }
                        }
                    });
                    
                    // Sync Quill content met textarea
                    descriptionQuill.on('text-change', function() {
                        descriptionElement.value = descriptionQuill.root.innerHTML;
                    });
                    
                    // Laad bestaande content
                    if (descriptionElement.value) {
                        descriptionQuill.root.innerHTML = descriptionElement.value;
                    }
                    
                    // Custom afbeelding upload handler
                    const toolbar = descriptionQuill.getModule('toolbar');
                    toolbar.addHandler('image', function() {
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

            input.onchange = function() {
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
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 10000;
                padding: 12px 20px; border-radius: 6px; font-size: 14px;
                background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#e3f2fd'};
                color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0d47a1'};
                border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bbdefb'};
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 350px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 4000);
        }
    </script>
</body>
</html> 