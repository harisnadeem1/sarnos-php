<?php
session_start();
require_once 'database.php';
require_once 'cloaking.php';

// Check cloaking voor Live Monitoring
checkCloaking();

// Initialize session ID for cart
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = session_id();
}

// Get current settings
$db = Database::getInstance();
$shop_name = $db->getSetting('shop_name');
$shop_description = $db->getSetting('shop_description');
$page_title = "Artykuły ogrodowe - " . $shop_name;

// Initialize cart data
$cartItemCount = $db->getCartItemCount($_SESSION['cart_session_id']);
$cartItems = $db->getCartItems($_SESSION['cart_session_id']);
$cartTotal = $db->getCartTotal($_SESSION['cart_session_id']);

// Get filter parameters
$category_filter = 'Artykuły ogrodowe'; // Fixed category
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$products_per_page = 12;

// Get all products
$all_products = $db->getAllProducts(true);

// Apply category filter - only show "Artykuły ogrodowe" products
$filtered_products = array_filter($all_products, function($product) use ($category_filter) {
    return isset($product['category']) && $product['category'] === $category_filter;
});

// Search filter
if (!empty($search_query)) {
    $filtered_products = array_filter($filtered_products, function($product) use ($search_query) {
        $search_lower = strtolower($search_query);
        $name_match = strpos(strtolower($product['name']), $search_lower) !== false;
        $desc_match = isset($product['description']) && strpos(strtolower($product['description']), $search_lower) !== false;
        return $name_match || $desc_match;
    });
}

// Sort products
switch ($sort_by) {
    case 'price-low':
        usort($filtered_products, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        break;
    case 'price-high':
        usort($filtered_products, function($a, $b) {
            return $b['price'] <=> $a['price'];
        });
        break;
    case 'popular':
        usort($filtered_products, function($a, $b) {
            $stock_a = isset($a['stock_quantity']) ? $a['stock_quantity'] : 0;
            $stock_b = isset($b['stock_quantity']) ? $b['stock_quantity'] : 0;
            return $stock_b <=> $stock_a;
        });
        break;
    case 'newest':
    default:
        usort($filtered_products, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        break;
}

// Pagination
$total_products = count($filtered_products);
$total_pages = ceil($total_products / $products_per_page);
$offset = ($page - 1) * $products_per_page;
$products = array_slice($filtered_products, $offset, $products_per_page);

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_to_cart':
            $productId = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            if ($productId > 0 && $quantity > 0) {
                $db->addToCart($_SESSION['cart_session_id'], $productId, $quantity);
                $success_message = "Produkt dodany do koszyka!";
            }
            break;
            
        case 'update_cart':
            $productId = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $db->updateCartQuantity($_SESSION['cart_session_id'], $productId, $quantity);
            break;
            
        case 'remove_from_cart':
            $productId = intval($_POST['product_id']);
            $db->removeFromCart($_SESSION['cart_session_id'], $productId);
            $success_message = "Produkt usunięty z koszyka!";
            break;
            
        case 'checkout':
            $cartItems = $db->getCartItems($_SESSION['cart_session_id']);
            if (!empty($cartItems)) {
                header("Location: checkout_redirect.php");
                exit();
            }
            break;
    }
}
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
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    padding: 60px 40px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
}

.page-header h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
}

.page-header h1 i {
    font-size: 3rem;
    color: #d4f7dc;
}

.page-header p {
    font-size: 1.3rem;
    max-width: 600px;
    margin: 0 auto;
    opacity: 0.95;
}

.filters-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 40px;
}

.filters-form {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    font-weight: 500;
    color: #333;
}

.filter-group select,
.filter-group input {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.95rem;
}

.filter-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.product-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.product-image {
    position: relative;
    overflow: hidden;
    height: 200px;
    background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
    background: white;
}

.product-image i {
    font-size: 3rem;
    color: #ccc;
}

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #ff6b00;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.delivery-badge {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.product-info {
    padding: 20px;
}

.product-category {
    color: #22c55e;
    font-size: 0.9rem;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.product-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.product-description {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 15px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.price-current {
    font-size: 1.4rem;
    font-weight: 700;
    color: #22c55e;
}

.price-old {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
}

.product-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.delivery-info {
    color: #28a745;
    font-size: 0.9rem;
    text-align: left;
    margin-top: 5px;
    padding-left: 5px;
}

.btn-add-cart {
    flex: 1;
    background: #ff6b00;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add-cart:hover {
    background: #e55a00;
    transform: translateY(-1px);
}

.btn-wishlist {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-wishlist:hover {
    background: #e9ecef;
}

.no-products {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.no-products i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #ddd;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 40px;
}

.pagination a,
.pagination span {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.pagination a:hover,
.pagination .current {
    background: #22c55e;
    color: white;
    border-color: #22c55e;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 40px 15px;
    }
    
    .page-header {
        padding: 40px 20px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
    }
    
    .page-header h1 i {
        font-size: 2rem;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}
</style>

<div class="content-wrapper">
        <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06);">
        <h1 style="font-size: 1.8rem; color: #dc3545; margin-bottom: 12px; font-weight: 700;">🌿 Artykuły Ogrodowe</h1>
        <h2 style="font-size: 1.3rem; color: #333; margin-bottom: 10px; font-weight: 600;">Najlepsze produkty ogrodowe w najlepszych cenach!</h2>
        <p style="font-size: 0.95rem; color: #666; max-width: 700px; margin: 0 auto; line-height: 1.5;">
            Odkryj naszą wyjątkową kolekcję artykułów ogrodowych. Od profesjonalnych narzędzi po systemy nawadniania - 
            wszystko czego potrzebujesz do stworzenia pięknego ogrodu. <strong>Gwarantujemy najwyższą jakość w atrakcyjnych cenach!</strong>
        </p>
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px; color: #dc3545; font-weight: 500; font-size: 0.9rem;">
                <i class="fas fa-check-circle"></i>
                <span>Szybka dostawa 1-3 dni</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; color: #dc3545; font-weight: 500; font-size: 0.9rem;">
                <i class="fas fa-shield-alt"></i>
                <span>4 lata gwarancji</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; color: #dc3545; font-weight: 500; font-size: 0.9rem;">
                <i class="fas fa-star"></i>
                <span>Najwyższa jakość</span>
            </div>
        </div>
    </div>

    <div class="filters-section">
        <form class="filters-form" method="GET">
            <div class="filter-group">
                <label for="sort">Sortuj:</label>
                <select id="sort" name="sort">
                    <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Najnowsze</option>
                    <option value="price-low" <?php echo $sort_by === 'price-low' ? 'selected' : ''; ?>>Cena: od najniższej</option>
                    <option value="price-high" <?php echo $sort_by === 'price-high' ? 'selected' : ''; ?>>Cena: od najwyższej</option>
                    <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Najpopularniejsze</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Szukaj:</label>
                <input type="text" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       placeholder="Wpisz nazwę produktu...">
            </div>
            
            <button type="submit" class="filter-btn">Filtruj</button>
        </form>
    </div>

    <?php if (empty($products)): ?>
        <div class="no-products">
            <i class="fas fa-seedling"></i>
            <h3>Nie znaleziono produktów ogrodowych</h3>
            <p>Obecnie nie ma produktów w kategorii "Artykuły ogrodowe". Sprawdź później lub skontaktuj się z nami w sprawie dostępności.</p>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <a href="<?php echo Database::getProductUrl($product); ?>" class="product-card-link">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-seedling"></i>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                                <?php $discount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>
                                <div class="product-badge">-<?php echo $discount; ?>%</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-category">Artykuły ogrodowe</div>
                            
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <?php if (!empty($product['description'])): ?>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="product-price">
                                <span class="price-current"><?php echo number_format($product['price'], 2, ',', ' '); ?> zł</span>
                                <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                                    <span class="price-old"><?php echo number_format($product['old_price'], 2, ',', ' '); ?> zł</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <button class="btn-add-cart" onclick="event.preventDefault(); event.stopPropagation(); window.location.href='<?php echo Database::getProductUrl($product); ?>';">
                                    <i class="fas fa-eye"></i> Zobacz produkt
                                </button>
                                <button class="btn-wishlist" onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $product['id']; ?>)">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="delivery-info">
                                <i class="fas fa-truck"></i> Czas dostawy: 1-3 dni roboczych
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Poprzednia</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Następna</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Cart functions
function openCart() {
    document.getElementById('cartSidebar').classList.add('open');
    document.getElementById('overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCart() {
    document.getElementById('cartSidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Auto-open cart when product is added (if success message exists)
<?php if (isset($success_message) && strpos($success_message, 'dodany') !== false): ?>
    setTimeout(function() {
        openCart();
    }, 500);
<?php endif; ?>

// Auto-open cart when add-to-cart form is submitted
document.addEventListener('DOMContentLoaded', function() {
    const addToCartForms = document.querySelectorAll('form[action*="add_to_cart"], .product-actions form');
    addToCartForms.forEach(function(form) {
        form.addEventListener('submit', function() {
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';
                submitBtn.disabled = true;
                
                // Auto-open cart after a short delay
                setTimeout(function() {
                    openCart();
                    // Reset button after 2 seconds
                    setTimeout(function() {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 2000);
                }, 300);
            }
        });
    });
});

function addToCart(productId) {
    alert('Product toegevoegd aan winkelwagen! (ID: ' + productId + ')');
}

function toggleWishlist(productId) {
    alert('Product toegevoegd aan verlanglijst! (ID: ' + productId + ')');
}

// Auto-hide success messages
setTimeout(function() {
    const alerts = document.querySelectorAll('[style*="background: #d4edda"]');
    alerts.forEach(function(alert) {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s ease';
        setTimeout(function() {
            alert.remove();
        }, 300);
    });
}, 4000);

// Success message handling
<?php if (isset($success_message)): ?>
    // Show success notification
    const successMessage = document.createElement('div');
    successMessage.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); z-index: 9999; font-weight: 500;';
    successMessage.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i><?php echo $success_message; ?>';
    document.body.appendChild(successMessage);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        successMessage.style.opacity = '0';
        successMessage.style.transition = 'opacity 0.3s ease';
        setTimeout(() => successMessage.remove(), 300);
    }, 4000);
    
    // Update cart badge
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = '<?php echo $cartItemCount; ?>';
    } else if (<?php echo $cartItemCount; ?> > 0) {
        const cartIcon = document.querySelector('.cart-icon');
        if (cartIcon) {
            const badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.textContent = '<?php echo $cartItemCount; ?>';
            cartIcon.appendChild(badge);
        }
    }
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
</body>
</html>