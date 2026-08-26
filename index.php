<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spices Lanka - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- HEADER -->
    <?php include 'components/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-text">
            <h1>Pure Taste<br><span>Pure Sri Lanka</span></h1>
            <p>Discover the finest range of 100% natural spices delivered to your doorstep.</p>
            <a href="shop.php" class="btn-shop">Shop Now</a>
        </div>

        <!-- HERO VIDEO SECTION -->
        <div class="hero-image">
            <video autoplay loop muted playsinline width="100%" style="border-radius: 8px; max-height: 350px; object-fit: cover;">
                <source src="assets/hero.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </section>

    <!-- SHOP BY CATEGORY -->
    <div class="section-title">Shop by Category</div>
    <section class="card-grid">

        <div class="card">
            <img src="assets/chili.jpg" alt="Chilli Powder">
            <div class="card-title">Chilli Powder</div>
        </div>

        <div class="card">
            <img src="assets/peper.jpg" alt="Pepper">
            <div class="card-title">Pepper</div>
        </div>

        <div class="card">
            <img src="assets/curryjpg.jpg" alt="Curry Powder">
            <div class="card-title">Curry Powder</div>
        </div>

        <div class="card">
            <img src="assets/others.jpg" alt="Other Spices">
            <div class="card-title">Other Spices</div>
        </div>

    </section>

    <!-- BEST SELLERS -->
    <div class="section-title">Best Sellers</div>
    <section class="card-grid">

        <div class="card">
            <span class="badge">Best Seller</span>
            <span class="wishlist-icon">♡</span>
            <img src="assets/cinnamon.jpg" alt="Ceylon Cinnamon">
            <div class="card-title">Ceylon Cinnamon</div>
        </div>

        <div class="card">
            <span class="badge">Organic</span>
            <span class="wishlist-icon">♡</span>
            <img src="assets/organic.jpg" alt="Ceylon Pepper">
            <div class="card-title">Ceylon Pepper</div>
        </div>

        <div class="card">
            <span class="badge">Local Favorite</span>
            <span class="wishlist-icon">♡</span>
            <img src="assets/local.jpg" alt="Curry Powder">
            <div class="card-title">Curry & chilli Powder</div>
        </div>

        <div class="card">
            <span class="badge">Exclusive</span>
            <span class="wishlist-icon">♡</span>
            <img src="assets/ex.jpg" alt="Chilli Powder">
            <div class="card-title">Cinnamon Tea</div>
        </div>

    </section>

    <!-- FOOTER -->
    <?php include 'components/footer.php'; ?>

</body>
</html>