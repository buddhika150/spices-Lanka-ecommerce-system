<?php 
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spices Lanka - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header Include -->
    <?php include 'components/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-text">
            <h1>Pure Taste<br><span>Pure Sri Lanka</span></h1>
            <p>Discover the finest range of 100% natural spices delivered to your doorstep.</p>
            <a href="shop.php" class="btn-shop">Shop Now</a>
        </div>
        <div class="hero-image">
            <video autoplay loop muted playsinline>
                <source src="assets/hero.mp4" type="video/mp4">
            </video>
        </div>
    </section>

    <!-- SHOP BY CATEGORY -->
    <div class="section-title">Shop by Category</div>
    <section class="card-grid">
        <a href="shop.php?category=Powders&search=Chilli" class="card-link">
            <div class="card">
                <img src="assets/chili.jpg" alt="Chilli Powder" onerror="this.src='assets/chili.jpg';">
                <div class="card-title">Chilli Powder</div>
            </div>
        </a>
        <a href="shop.php?category=Spices&search=Pepper" class="card-link">
            <div class="card">
                <img src="assets/pepper.jpg" alt="Pepper" onerror="this.src='assets/pepper.jpg';">
                <div class="card-title">Pepper</div>
            </div>
        </a>
        <a href="shop.php?category=Powders&search=Curry" class="card-link">
            <div class="card">
                <img src="assets/curryjpg.jpg" alt="Curry Powder" onerror="this.src='assets/curry.jpg';">
                <div class="card-title">Curry Powder</div>
            </div>
        </a>
        <a href="shop.php?category=Spices&search=Cinnamon" class="card-link">
            <div class="card">
                <img src="assets/others.jpg" alt="Cinnamon & Other Spices" onerror="this.src='assets/spices.jpg';">
                <div class="card-title">Cinnamon & Other Spices</div>
            </div>
        </a>
    </section>

    <!-- BEST SELLERS FILE INCLUDE -->
    <?php include 'bestsellers.php'; ?>

    <!-- Footer Include -->
    <?php include 'components/footer.php'; ?>

    <script src="js/main.js"></script>
</body>
</html>