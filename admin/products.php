<?php
require_once __DIR__ . '/auth.php';

function escapeProduct($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['product_csrf'], $token)) {
        http_response_code(403);
        exit('Invalid request. Refresh the page and try again.');
    }

    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    $active = $_POST['active'] ?? '';

    if (!$productId || $productId < 1 ||
        !in_array($active, ['0', '1'], true)) {
        http_response_code(400);
        exit('Invalid product details.');
    }

    $active = (int) $active;

    $stmt = $conn->prepare(
        "UPDATE product SET is_active = ? WHERE Product_ID = ?"
    );
    $stmt->bind_param('ii', $active, $productId);
    $stmt->execute();
    $stmt->close();

    header('Location: products.php?updated=1');
    exit();
}

$products = $conn->query(
    "SELECT Product_ID, Product_Name, Category, Price,
            Stock_Quantity, Weight, is_active
     FROM product
     ORDER BY Product_ID DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Spices Lanka</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6f2;
            color: #183829;
        }

        header {
            background: #0b2216;
            color: white;
            padding: 22px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 { margin: 0; }

        header a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
        }

        main {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 12px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #edf2e9;
            white-space: nowrap;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .active { background: #dcf3e2; color: #216536; }
        .inactive { background: #ffe3e3; color: #9b2929; }

        button {
            border: 0;
            padding: 9px 14px;
            border-radius: 5px;
            cursor: pointer;
            color: white;
        }

        .deactivate { background: #a73535; }
        .activate { background: #215739; }

        .success {
            background: #dcf3e2;
            padding: 12px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
<header>
    <h2>SPICES LANKA · PRODUCTS</h2>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="../index.php">Website</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Manage Products</h1>
    <p>
    <a href="product_form.php">+ Add New Product</a>
</p>

<?php if (isset($_GET['saved'])): ?>
    <p class="success">Product saved successfully.</p>
<?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <p class="success">Product status updated.</p>
    <?php endif; ?>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price (LKR)</th>
                    <th>Stock</th>
                    <th>Weight</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <?php $isActive = (int) $product['is_active'] === 1; ?>
                    <tr>
                        <td><?= (int) $product['Product_ID'] ?></td>
                        <td><?= escapeProduct($product['Product_Name']) ?></td>
                        <td><?= escapeProduct($product['Category']) ?></td>
                        <td>
                            <?= number_format((float) $product['Price'], 2) ?>
                        </td>
                        <td><?= (int) $product['Stock_Quantity'] ?></td>
                        <td><?= escapeProduct($product['Weight']) ?></td>
                        <td>
                            <span class="badge <?= $isActive ? 'active' : 'inactive' ?>">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <a href="product_form.php?id=<?= (int) $product['Product_ID'] ?>">
    Edit / Update Stock
</a>
<br><br>

                            <form method="post">
                                <input type="hidden" name="csrf"
                                       value="<?= escapeProduct($_SESSION['product_csrf']) ?>">
                                <input type="hidden" name="product_id"
                                       value="<?= (int) $product['Product_ID'] ?>">
                                <input type="hidden" name="active"
                                       value="<?= $isActive ? '0' : '1' ?>">

                                <button type="submit"
                                        class="<?= $isActive ? 'deactivate' : 'activate' ?>">
                                    <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($products->num_rows === 0): ?>
                    <tr>
                        <td colspan="8">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>