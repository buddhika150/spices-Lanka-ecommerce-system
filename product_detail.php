<?php 
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';

$exchange_rate = 300;
$is_usd = (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD');

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;

if ($product_id > 0 && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT * FROM PRODUCT WHERE Product_ID = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['Product_Name']) : 'Product Detail'; ?> - Spices Lanka</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <div class="detail-container">
        <?php if ($product): ?>
            <div class="breadcrumb">
                <a href="index.php">home</a> / <a href="shop.php">shop</a> / <?= htmlspecialchars($product['Product_Name']); ?>
            </div>

            <div class="product-wrapper">
                <div class="product-image-box">
                    <img src="assets/<?= htmlspecialchars($product['Image']); ?>" alt="<?= htmlspecialchars($product['Product_Name']); ?>" onerror="this.src='assets/chili.jpg';">
                </div>

                <div class="product-info-box">
                    <h1 class="product-title"><?= htmlspecialchars($product['Product_Name']); ?></h1>
                    
                    <div class="rating">
                        ★★★★★ <span>(312 reviews)</span>
                    </div>

                    <div class="price-tag"><?= displayPrice($product['Price'], $is_usd, $exchange_rate); ?></div>

                    <p class="description">
                        <?= !empty($product['Description']) ? htmlspecialchars($product['Description']) : 'Authentic Sri Lankan spice carefully selected and processed to ensure premium quality and natural aroma.'; ?>
                    </p>

                    <div class="specs-grid">
                        <div class="specs-item"><span>Category:</span> <strong><?= isset($product['Category']) ? htmlspecialchars($product['Category']) : 'Spices'; ?></strong></div>
                        <div class="specs-item"><span>Weight:</span> <strong><?= isset($product['Weight']) ? htmlspecialchars($product['Weight']) : '100g Pack'; ?></strong></div>
                        <div class="specs-item"><span>Origin:</span> <strong>Sri Lanka 🇱🇰</strong></div>
                        <div class="specs-item"><span>Shelf Life:</span> <strong>24 Months</strong></div>
                    </div>

                    <div class="stock-status">
                        ✓ In stock (<?= isset($product['Stock_Quantity']) ? $product['Stock_Quantity'] : 20; ?> units available)
                    </div>

                    <!-- JavaScript Cart System සඳහා සකස් කළ Form එක -->
                    <div class="quantity-box">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1">
                    </div>

                    <div class="action-btns">
                        <button type="button" class="btn-add-cart" 
                                onclick="addToCart(
                                    <?= $product['Product_ID']; ?>, 
                                    '<?= htmlspecialchars($product['Product_Name'], ENT_QUOTES); ?>', 
                                    <?= $product['Price']; ?>, 
                                    '<?= htmlspecialchars($product['Image'], ENT_QUOTES); ?>'
                                )">
                            Add to Cart
                        </button>
                        
                        <a href="cart.php" class="btn-buy-now" style="text-align:center; text-decoration:none;"
                           onclick="addToCart(
                               <?= $product['Product_ID']; ?>, 
                               '<?= htmlspecialchars($product['Product_Name'], ENT_QUOTES); ?>', 
                               <?= $product['Price']; ?>, 
                               '<?= htmlspecialchars($product['Image'], ENT_QUOTES); ?>'
                           )">
                            Buy Now
                        </a>
                    </div>
                </div>
            </div>

            <div class="details-tabs-section">
                <div class="tab-headers">
                    <span class="tab-btn active">Description & Benefits</span>
                    <span class="tab-btn">Storage & Usage</span>
                    <span class="tab-btn">Shipping Info</span>
                </div>
                <div class="tab-content">
                    <p>Our <?= htmlspecialchars($product['Product_Name']); ?> is sourced directly from organic spice gardens in Sri Lanka. Processed under strict hygienic standards, it retains all natural oils, rich aroma, and authentic taste.</p>
                    <ul>
                        <li>100% Pure & Natural with no added preservatives or colors.</li>
                        <li>Packed in seal-lock pouches to preserve fresh aroma.</li>
                        <li>Rich in natural antioxidants and health benefits.</li>
                    </ul>
                </div>
            </div>

        <?php else: ?>
            <p style="text-align: center; margin: 50px 0; color: #666;">Product not found.</p>
        <?php endif; ?>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/main.js"></script>
    <!-- Cart Logic සඳහා cart.js ගොනුව සම්බන්ධ කර ඇත -->
    <script src="js/cart.js"></script>
</body>
</html>