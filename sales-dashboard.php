<?php
session_start();
$lang = $_SESSION['lang'] ?? 'nl';

// Load sales data
$salesFile = __DIR__ . '/sales.json';
$sales = file_exists($salesFile) ? json_decode(file_get_contents($salesFile), true) : [];

// Load product data
$productsFile = __DIR__ . '/products.json';
$products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) : [];

// Create product lookup with translations
$productLookup = [];
foreach ($products as $p) {
    $productLookup[$p['id']] = [
        'id'    => $p['id'],
        'name'  => $p['translations'][$lang]['name'] 
                    ?? ($p['name'] ?? 'Unknown Product'),
        'image' => $p['image_url'] ?? ''
    ];
}

// Conversion rate PLN -> USD
$conversionRate = 1;

// Group sales by date
$dailyStats = [];
$overallTotal = 0; // ✅ Overall total revenue (in USD)

foreach ($sales as $sale) {
    $day = date('Y-m-d', strtotime($sale['date']));

    if (!isset($dailyStats[$day])) {
        $dailyStats[$day] = [
            'total_revenue' => 0,
            'products' => []
        ];
    }

    $pid = $sale['product_id'] ?? 0;
    if (!isset($dailyStats[$day]['products'][$pid])) {
        $dailyStats[$day]['products'][$pid] = [
            'id'       => $pid,
            'name'     => $productLookup[$pid]['name'] ?? ($sale['name'] ?? 'Unknown'),
            'image'    => $productLookup[$pid]['image'] ?? '',
            'sold_qty' => 0,
            'revenue'  => 0
        ];
    }

    // Convert PLN to USD
    $convertedTotal = ($sale['total'] ?? 0) * $conversionRate;

    $dailyStats[$day]['products'][$pid]['sold_qty'] += $sale['quantity'] ?? 0;
    $dailyStats[$day]['products'][$pid]['revenue']  += $convertedTotal;
    $dailyStats[$day]['total_revenue'] += $convertedTotal;

    $overallTotal += $convertedTotal; // ✅ Sum all sales in USD
}

// Sort newest first
krsort($dailyStats);

// Helper function to label days
function dayLabel($date) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($date === $today) return "Today";
    if ($date === $yesterday) return "Yesterday";

    $diff = (strtotime(date('Y-m-d')) - strtotime($date)) / 86400;
    return $diff . " Days Ago";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SKELPOLL - Recent Daily Sales</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f8f9fa;
        margin: 0;
        padding: 0;
        color: #333;
    }
    header {
        background: linear-gradient(90deg, #000, #333);
        color: #fff;
        padding: 20px;
        text-align: center;
        font-size: 26px;
        font-weight: bold;
        letter-spacing: 1px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .container {
        max-width: 1100px;
        margin: 30px auto;
        padding: 15px;
    }
    .overall-sales {
        background: #28a745;
        color: #fff;
        padding: 15px;
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .day-block {
        background: #fff;
        padding: 20px;
        margin-bottom: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        transition: transform 0.2s ease;
    }
    .day-block:hover { transform: translateY(-3px); }
    .day-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 10px;
        color: #e60000;
    }
    .total-revenue {
        font-size: 16px;
        font-weight: bold;
        background: #e60000;
        color: #fff;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        display: inline-block;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    table { width: 100%; border-collapse: collapse; }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    th { background: #f1f1f1; color: #333; font-weight: bold; }
    .product-cell { display: flex; align-items: center; gap: 10px; }
    .product-cell img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .mobile-list { display: none; }
    @media (max-width: 768px) {
        table { display: none; }
        .mobile-list { display: flex; flex-direction: column; gap: 12px; }
        .mobile-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .mobile-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .mobile-card-header img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .mobile-card h3 { font-size: 16px; margin: 0; font-weight: bold; }
        .mobile-card p { margin: 2px 0; font-size: 14px; }
        .label { font-weight: bold; color: #000; }
    }
</style>
</head>
<body>

<header>SKELPOLL</header>

<div class="container">

    <!-- ✅ Overall Sales -->
    <div class="overall-sales">
        Overall Sales: €<?= number_format($overallTotal, 2) ?>
    </div>

<?php
$counter = 0;
foreach ($dailyStats as $day => $data):
    if ($counter >= 7) break; // last 7 days
    $counter++;
?>
    <div class="day-block">
        <div class="day-title"><?= dayLabel($day) ?> <small style="color:#666;">(<?= htmlspecialchars($day) ?>)</small></div>
        <div class="total-revenue"> €<?= number_format($data['total_revenue'], 2) ?></div>

        <!-- Desktop Table -->
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Units Sold</th>
                    <th>Revenue (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['products'] as $stat): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <?php if (!empty($stat['image'])): ?>
                                <img src="<?= htmlspecialchars($stat['image']) ?>" alt="<?= htmlspecialchars($stat['name']) ?>">
                            <?php endif; ?>
                            <?= htmlspecialchars($stat['name']) ?>
                        </div>
                    </td>
                    <td><?= $stat['sold_qty'] ?></td>
                    <td>€<?= number_format($stat['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Mobile List -->
        <div class="mobile-list">
            <?php foreach ($data['products'] as $stat): ?>
            <div class="mobile-card">
                <div class="mobile-card-header">
                    <?php if (!empty($stat['image'])): ?>
                        <img src="<?= htmlspecialchars($stat['image']) ?>" alt="<?= htmlspecialchars($stat['name']) ?>">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($stat['name']) ?></h3>
                </div>
                <p><span class="label">Units Sold:</span> <?= $stat['sold_qty'] ?></p>
                <p><span class="label">Revenue:</span> €<?= number_format($stat['revenue'], 2) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
