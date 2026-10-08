<?php
require_once __DIR__ . '/auth.php';

function productEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (isset($_GET['id']) && (!$id || $id < 1)) {
    http_response_code(400);
    exit('Invalid product ID.');
}

$id = $id ?: 0;
$error = '';

$categories = $conn->query(
    "SELECT Category_ID, Category_Name
     FROM category ORDER BY Category_Name"
)->fetch_all(MYSQLI_ASSOC);

$categoryNames = [];
foreach ($categories as $categoryRow) {
    $categoryNames[(int) $categoryRow['Category_ID']] =
        $categoryRow['Category_Name'];
}

$product = [
    'Product_Name' => '',
    'Category_ID' => '',
    'Description' => '',
    'Price' => '',
    'Stock_Quantity' => 0,
    'Weight' => '100g',
    'Image' => ''
];

if ($id > 0) {
    $stmt = $conn->prepare(
        "SELECT * FROM product WHERE Product_ID = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        http_response_code(404);
        exit('Product not found.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['product_csrf'], $token)) {
        http_response_code(403);
        exit('Invalid request. Refresh the page.');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $weight = trim((string) ($_POST['weight'] ?? ''));
    $image = trim((string) ($_POST['image'] ?? ''));

    $categoryId = filter_input(
        INPUT_POST, 'category_id', FILTER_VALIDATE_INT
    );

    $priceText = trim((string) ($_POST['price'] ?? ''));
    $stock = filter_input(
        INPUT_POST, 'stock', FILTER_VALIDATE_INT
    );

    $product = [
        'Product_Name' => $name,
        'Category_ID' => $categoryId,
        'Description' => $description,
        'Price' => $priceText,
        'Stock_Quantity' => $_POST['stock'] ?? '',
        'Weight' => $weight,
        'Image' => $image
    ];

    if ($name === '' || mb_strlen($name) > 255) {
        $error = 'Enter a product name (maximum 255 characters).';
    } elseif (!isset($categoryNames[$categoryId])) {
        $error = 'Select a valid category.';
    } elseif (
        !preg_match('/^\d{1,8}(\.\d{1,2})?$/', $priceText)
    ) {
        $error = 'Enter a valid price with up to 2 decimal places.';
    } elseif (
        $stock === false || $stock === null ||
        $stock < 0 || $stock > 2147483647
    ) {
        $error = 'Stock must be a whole number of 0 or more.';
    } elseif ($weight === '' || mb_strlen($weight) > 40) {
        $error = 'Enter a weight (maximum 40 characters).';
    } elseif (
        strlen($image) > 255 ||
        !preg_match('/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/i', $image)
    ) {
        $error = 'Use an image filename such as cinnamon.jpg.';
    } elseif (!is_file(__DIR__ . '/../assets/' . $image)) {
        $error = 'Image not found. Put the image in the assets folder.';
    } elseif (mb_strlen($categoryNames[$categoryId]) > 50) {
        $error = 'Category name must be 50 characters or fewer.';
    } else {
        $categoryName = $categoryNames[$categoryId];

        if ($id > 0) {
            $stmt = $conn->prepare(
                "UPDATE product
                 SET Product_Name = ?, Category_ID = ?,
                     Category = ?, Description = ?, Price = ?,
                     Stock_Quantity = ?, Stock = ?,
                     Weight = ?, Image = ?
                 WHERE Product_ID = ?"
            );

            $stmt->bind_param(
                'sisssiissi',
                $name,
                $categoryId,
                $categoryName,
                $description,
                $priceText,
                $stock,
                $stock,
                $weight,
                $image,
                $id
            );
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO product
                 (Product_Name, Category_ID, Category, Description,
                  Price, Stock_Quantity, Stock, Weight, Image, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
            );

            $stmt->bind_param(
                'sisssiiss',
                $name,
                $categoryId,
                $categoryName,
                $description,
                $priceText,
                $stock,
                $stock,
                $weight,
                $image
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: products.php?saved=1');
            exit();
        }

        $error = 'Could not save the product.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit Product' : 'Add Product' ?> - Spices Lanka</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f5f6f2;
            color: #183829;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 650px;
            margin: 35px auto;
            background: white;
            padding: 28px;
            border-radius: 12px;
        }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font: inherit;
        }

        textarea { min-height: 100px; }

        button {
            margin-top: 22px;
            background: #215739;
            color: white;
            padding: 12px 22px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        a { color: #215739; }
        small { color: #666; }

        .error {
            background: #ffe3e3;
            color: #922;
            padding: 12px;
            border-radius: 6px;
        }

        @media (max-width: 700px) {
            main { margin: 20px 12px; padding: 20px; }
        }
    </style>
</head>
<body>
<main>
    <a href="products.php">← Back to Products</a>

    <h1><?= $id ? 'Edit Product' : 'Add Product' ?></h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= productEscape($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf"
               value="<?= productEscape($_SESSION['product_csrf']) ?>">

        <label for="name">Product Name</label>
        <input id="name" name="name" required maxlength="255"
               value="<?= productEscape($product['Product_Name']) ?>">

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <option value="">Select category</option>

            <?php foreach ($categories as $categoryRow): ?>
                <option
                    value="<?= (int) $categoryRow['Category_ID'] ?>"
                    <?= (int) $product['Category_ID'] ===
                        (int) $categoryRow['Category_ID'] ? 'selected' : '' ?>>
                    <?= productEscape($categoryRow['Category_Name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= productEscape(
            $product['Description']
        ) ?></textarea>

        <label for="price">Price (LKR)</label>
        <input id="price" type="number" name="price"
               min="0" max="99999999.99" step="0.01" required
               value="<?= productEscape($product['Price']) ?>">

        <label for="stock">Stock Quantity</label>
        <input id="stock" type="number" name="stock"
               min="0" max="2147483647" step="1" required
               value="<?= productEscape($product['Stock_Quantity']) ?>">
        <small>Enter the total quantity currently available.</small>

        <label for="weight">Weight</label>
        <input id="weight" name="weight" required maxlength="40"
               placeholder="100g"
               value="<?= productEscape($product['Weight']) ?>">

        <label for="image">Image Filename</label>
        <input id="image" name="image" required maxlength="255"
               placeholder="cinnamon.jpg"
               value="<?= productEscape($product['Image']) ?>">
        <small>Put the image in assets, then enter its filename here.</small>

        <br>
        <button type="submit">Save Product</button>
    </form>
</main>
</body>
</html>