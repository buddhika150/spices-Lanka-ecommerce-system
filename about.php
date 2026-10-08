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

    <title>About Us - Spices Lanka</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .about-page {
            max-width: 1150px;
            margin: 45px auto;
            padding: 0 20px;
            color: #0b2216;
        }

        .about-heading {
            text-align: center;
            margin-bottom: 40px;
        }

        .about-heading h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .about-heading p {
            color: #777;
            line-height: 1.6;
        }

        .about-story {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 35px;
            align-items: center;
        }

        .about-story img {
            width: 100%;
            height: 360px;
            object-fit: cover;
            border-radius: 12px;
        }

        .about-story h2 {
            font-size: 28px;
            margin-bottom: 18px;
        }

        .about-story p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .about-values {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin: 45px 0;
        }

        .about-value {
            background: #f8f6ef;
            border: 1px solid #eee8d8;
            border-radius: 12px;
            padding: 28px;
        }

        .about-value h3 {
            margin-bottom: 12px;
            color: #a77b13;
        }

        .about-value p {
            color: #555;
            line-height: 1.7;
        }

        .about-shop {
            text-align: center;
            padding: 35px 20px;
            background: #0b2216;
            color: white;
            border-radius: 12px;
            margin-bottom: 45px;
        }

        .about-shop h2 {
            margin-bottom: 12px;
        }

        .about-shop p {
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .about-shop a {
            display: inline-block;
            background: #c49a32;
            color: #0b2216;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .about-story,
            .about-values {
                grid-template-columns: 1fr;
            }

            .about-story img {
                height: 260px;
            }
        }
    </style>
</head>

<body>

<?php include 'components/header.php'; ?>

<main class="about-page">

    <div class="about-heading">
        <h1>About Spices Lanka</h1>
        <p>Discover the flavours of Sri Lankan cooking.</p>
    </div>

    <section class="about-story">

        <img src="assets/spices.jpg" alt="A selection of spices">

        <div>
            <h2>Our Story</h2>

            <p>
                Spices Lanka is an online store created to bring
                Sri Lankan spices and their distinctive flavours
                closer to people who enjoy cooking.
            </p>

            <p>
                Our collection includes whole spices, spice powders
                and herbal teas. From cinnamon and black pepper
                to curry powder, you can explore ingredients for
                everyday meals and new recipes.
            </p>

            <p>
                We aim to make discovering and shopping for spices
                simple, with clear product information and
                convenient browsing.
            </p>
        </div>

    </section>

    <section class="about-values" aria-label="Our aims">

        <div class="about-value">
            <h3>Our Mission</h3>
            <p>
                To make Sri Lankan spices easier to discover
                and help customers find ingredients that suit
                their cooking.
            </p>
        </div>

        <div class="about-value">
            <h3>Our Collection</h3>
            <p>
                Explore whole spices, ground powders and
                herbal teas through our product categories.
            </p>
        </div>

        <div class="about-value">
            <h3>Our Approach</h3>
            <p>
                We focus on a straightforward shopping experience,
                useful product details and clear communication.
            </p>
        </div>

    </section>

    <section class="about-shop">
        <h2>Find Your Next Favourite Flavour</h2>
        <p>Browse our collection and discover something for your kitchen.</p>
        <a href="shop.php">Explore Our Shop</a>
    </section>

</main>

<?php include 'components/footer.php'; ?>

<script src="js/main.js"></script>
<script src="js/cart.js"></script>

</body>
</html>