<?php
require_once __DIR__ . '/components/config.php';

$exchange_rate = 300;
$is_usd = (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD');

$best_sellers = false;
if (isset($conn) && $conn instanceof mysqli) {
    $query = "SELECT * FROM PRODUCT ORDER BY Product_ID DESC LIMIT 4";
    $best_sellers = $conn->query($query);
}
?>

<div class="section-title">Best Sellers</div>
<section class="card-grid">
    <?php 
    $badges = ["Best Seller", "Organic", "Local Favorite", "Exclusive"];
    $index = 0;

    if ($best_sellers && $best_sellers->num_rows > 0): 
        while ($row = $best_sellers->fetch_assoc()): 
            $badge = $badges[$index % count($badges)];
            $index++;
            $price_display = displayPrice($row['Price'], $is_usd, $exchange_rate);
    ?>
        <a href="product_detail.php?id=<?= $row['Product_ID']; ?>" class="card-link">
            <div class="card">
                <span class="badge"><?= $badge; ?></span>
                <span class="wishlist-icon">♡</span>
                <img src="assets/<?= htmlspecialchars($row['Image']); ?>" alt="<?= htmlspecialchars($row['Product_Name']); ?>" onerror="this.src='assets/chili.jpg';">
                <div class="card-title"><?= htmlspecialchars($row['Product_Name']); ?></div>
                <div class="price"><?= $price_display; ?></div>
            </div>
        </a>
    <?php endwhile; endif; ?>
</section>