<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';

// Add your actual business contact details inside these quotes.
$contactEmail = '';
$contactPhone = '';
$contactAddress = '';

$hasEmail = filter_var($contactEmail, FILTER_VALIDATE_EMAIL);
$phoneLink = preg_replace('/[^0-9+]/', '', $contactPhone);

$hasContactDetails = (
    $hasEmail ||
    $contactPhone !== '' ||
    $contactAddress !== ''
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us - Spices Lanka</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .contact-page {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 20px;
            color: #0b2216;
        }

        .contact-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .contact-heading h1 {
            font-size: 36px;
            margin-bottom: 12px;
        }

        .contact-heading p {
            color: #666;
            line-height: 1.7;
        }

        .contact-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
            margin-bottom: 40px;
        }

        .contact-card {
            background: #f8f6ef;
            border: 1px solid #eee8d8;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }

        .contact-card h2 {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .contact-card p {
            color: #666;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .contact-card a {
            color: #8a650b;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .contact-address {
            font-style: normal;
            color: #555;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .contact-help {
            padding: 30px;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            margin-bottom: 35px;
        }

        .contact-help h2 {
            margin-bottom: 20px;
        }

        .contact-help details {
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }

        .contact-help details:last-child {
            border-bottom: none;
        }

        .contact-help summary {
            cursor: pointer;
            font-weight: bold;
        }

        .contact-help p {
            color: #555;
            line-height: 1.8;
            margin-top: 12px;
        }

        .contact-help a {
            color: #8a650b;
        }

        .contact-note {
            padding: 25px;
            background: #f8f6ef;
            text-align: center;
            border-radius: 10px;
            color: #555;
            margin-bottom: 35px;
            line-height: 1.7;
        }

        .contact-shop-link {
            text-align: center;
            margin-bottom: 45px;
        }

        .contact-shop-link a {
            display: inline-block;
            padding: 12px 25px;
            background: #0b2216;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        @media (max-width: 600px) {
            .contact-cards {
                grid-template-columns: 1fr;
            }

            .contact-help {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<?php include 'components/header.php'; ?>

<main class="contact-page">

    <div class="contact-heading">
        <h1>Contact Us</h1>
        <p>
            Have a question about our products or your order?
            Get in touch with Spices Lanka.
        </p>
    </div>

    <?php if ($hasContactDetails): ?>

        <section class="contact-cards" aria-label="Contact details">

            <?php if ($hasEmail): ?>
                <div class="contact-card">
                    <h2>Email Us</h2>

                    <p>Send us your product or order enquiry.</p>

                    <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($contactPhone !== ''): ?>
                <div class="contact-card">
                    <h2>Call Us</h2>

                    <p>Speak to us about your enquiry.</p>

                    <a href="tel:<?= htmlspecialchars($phoneLink, ENT_QUOTES, 'UTF-8'); ?>">
                        <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($contactAddress !== ''): ?>
                <div class="contact-card">
                    <h2>Our Address</h2>

                    <address class="contact-address">
                        <?= nl2br(htmlspecialchars($contactAddress, ENT_QUOTES, 'UTF-8')); ?>
                    </address>
                </div>
            <?php endif; ?>

        </section>

    <?php else: ?>

        <p class="contact-note">
            Our contact details will be available soon.
            You can find answers to common shopping questions below.
        </p>

    <?php endif; ?>

    <section class="contact-help">

        <h2>Shopping Help</h2>

        <details>
            <summary>How can I find a product?</summary>
            <p>
                Visit our <a href="shop.php">Shop</a> to browse products,
                or use the search bar to look for a particular spice.
            </p>
        </details>

        <details>
            <summary>Can I browse products by category?</summary>
            <p>
                Yes. Open the <a href="categories.php">Categories</a>
                page and choose a category to see its products.
            </p>
        </details>

        <details>
            <summary>Do I need an account to buy products?</summary>
            <p>
                Yes. You can browse products before logging in,
                but you need to log in to continue to checkout.
                New customers can
                <a href="register.php">create an account</a>.
            </p>
        </details>

        <details>
            <summary>What should I include in an order enquiry?</summary>
            <p>
                Include your order reference, if available,
                and a short description of your question.
                Please do not include your password or card details.
            </p>
        </details>

    </section>

    <div class="contact-shop-link">
        <a href="shop.php">Continue Shopping</a>
    </div>

</main>

<?php include 'components/footer.php'; ?>

<script src="js/main.js"></script>
<script src="js/cart.js"></script>

</body>
</html>

