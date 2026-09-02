<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';

$exchange_rate = 300;
$is_usd = (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 5000;

$sql = "SELECT * FROM PRODUCT WHERE Price <= ?";
$params = [$max_price];
$types = "d";

if (!empty($search)) {
    $sql .= " AND (Product_Name LIKE ? OR Description LIKE ? OR Category LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($category) && strtolower($category) != 'all') {
    $sql .= " AND (LOWER(Category) LIKE ? OR LOWER(Product_Name) LIKE ?)";
    $categoryTerm = '%' . strtolower($category) . '%';
    $params[] = $categoryTerm;
    $params[] = $categoryTerm;
    $types .= "ss";
}

$sql .= " ORDER BY Product_ID DESC";

$products = [];
if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Spices Lanka</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <div class="shop-container">
        <div class="sidebar">
            <h3>Categories</h3>
            <ul class="filter-list">
                <?php $base_url = "shop.php?search=" . urlencode($search) . "&max_price=" . $max_price; ?>
                <li><a href="<?= $base_url; ?>" class="<?= empty($category) ? 'active' : ''; ?>">All Products</a></li>
                <li><a href="<?= $base_url . '&category=Spices'; ?>" class="<?= strtolower($category) == 'spices' ? 'active' : ''; ?>">Spices</a></li>
                <li><a href="<?= $base_url . '&category=Powders'; ?>" class="<?= strtolower($category) == 'powders' ? 'active' : ''; ?>">Powders</a></li>
                <li><a href="<?= $base_url . '&category=Tea'; ?>" class="<?= strtolower($category) == 'tea' ? 'active' : ''; ?>">Herbal Tea</a></li>
            </ul>

            <form action="shop.php" method="GET" class="price-filter">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search); ?>">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category); ?>">
                <label for="max_price">Max Price Filter</label>
                <input type="range" id="max_price" name="max_price" min="100" max="5000" step="50" value="<?= $max_price; ?>" oninput="document.getElementById('priceVal').innerText = this.value">
                <div class="price-val">Max: <span id="priceVal"><?= displayPrice($max_price, $is_usd, $exchange_rate); ?></span></div>
                <button type="submit" class="btn-filter">Apply Price Filter</button>
            </form>
        </div>

        <div class="main-shop">
            <div class="shop-header">
                <h2>
                    <?php if(!empty($search)): ?>
                        Search Results for "<?= htmlspecialchars($search); ?>"
                    <?php elseif(!empty($category)): ?>
                        Category: <?= htmlspecialchars(ucfirst($category)); ?>
                    <?php else: ?>
                        Shop Products
                    <?php endif; ?>
                </h2>
            </div>

            <div class="product-grid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $row): ?>
                        <div class="product-card">
                            <img src="assets/<?= htmlspecialchars($row['Image']); ?>" alt="<?= htmlspecialchars($row['Product_Name']); ?>" onerror="this.src='assets/chili.jpg';">
                            <h4><?= htmlspecialchars($row['Product_Name']); ?></h4>
                            <div class="price"><?= displayPrice($row['Price'], $is_usd, $exchange_rate); ?></div>
                            <a href="product_detail.php?id=<?= $row['Product_ID']; ?>" class="btn-view">View Detail</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #777; grid-column: span 3;">No products found matching your criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>