<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';

// Read the category groups currently used by your products.
$sql = "
    SELECT
        TRIM(Category) AS category_name,
        COUNT(*) AS product_count
    FROM product
    WHERE Category IS NOT NULL
      AND TRIM(Category) <> ''
    GROUP BY TRIM(Category)
    ORDER BY category_name
";

$result = $conn->query($sql);

$categories = [];

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Images from your existing assets folder.
$categoryImages = [
    'spices'  => 'spices.jpg',
    'powders' => 'curry_powder.jpg',
    'tea'     => 'cinnamon_tea.jpg'
];

$categoryDescriptions = [
    'spices'  => 'Explore our aromatic Sri Lankan whole spices.',
    'powders' => 'Discover our range of ground spices and curry powders.',
    'tea'     => 'Enjoy our warming herbal tea selection.'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Categories - Spices Lanka</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .categories-page {
            max-width: 1200px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .categories-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .categories-heading h1 {
            color: #0b2216;
            font-size: 34px;
            margin-bottom: 10px;
        }

        .categories-heading p {
            color: #666;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .category-tile {
            display: block;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: #0b2216;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .category-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .category-tile img {
            width: 100%;
            height: 230px;
            object-fit: cover;
            display: block;
        }

        .category-tile-content {
            padding: 25px;
            text-align: center;
        }

        .category-tile-content h2 {
            margin-bottom: 10px;
            font-size: 24px;
        }

        .category-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .category-count {
            color: #b8860b;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .category-view-button {
            display: inline-block;
            background: #0b2216;
            color: #fff;
            padding: 11px 22px;
            border-radius: 5px;
        }

        .categories-empty {
            text-align: center;
            color: #666;
            padding: 40px;
        }

        @media (max-width: 900px) {
            .categories-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include 'components/header.php'; ?>

<main class="categories-page">

    <div class="categories-heading">
        <h1>Shop by Category</h1>
        <p>Choose a category to explore our products.</p>
    </div>

    <?php if (!empty($categories)): ?>

        <div class="categories-grid">

            <?php foreach ($categories as $category): ?>
                <?php
                $name = $category['category_name'];
                $key = strtolower($name);

                $image = $categoryImages[$key] ?? 'spices.jpg';

                $description = $categoryDescriptions[$key]
                    ?? 'Explore the products in this category.';

                $label = ($key === 'tea') ? 'Herbal Tea' : $name;

                $url = 'shop.php?' . http_build_query([
                    'category' => $name
                ]);
                ?>

                <a
                    href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                    class="category-tile"
                >
                    <img
                        src="assets/<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <div class="category-tile-content">

                        <h2>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </h2>

                        <p class="category-description">
                            <?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>
                        </p>

                        <p class="category-count">
                            <?= (int)$category['product_count']; ?> products
                        </p>

                        <span class="category-view-button">
                            View Products
                        </span>

                    </div>
                </a>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p class="categories-empty">
            No categories available yet.
        </p>

    <?php endif; ?>

</main>

<?php include 'components/footer.php'; ?>

<script src="js/main.js"></script>
<script src="js/cart.js"></script>

</body>
</html>