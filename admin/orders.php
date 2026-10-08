<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/auth.php';

function orderEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['order_csrf'])) {
    $_SESSION['order_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $started = false;

    try {
        $token = $_POST['csrf'] ?? '';

        if (!is_string($token) ||
            !hash_equals($_SESSION['order_csrf'], $token)) {
            throw new RuntimeException('Invalid request. Refresh the page.');
        }

        $id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            throw new RuntimeException('Invalid order.');
        }

        $conn->begin_transaction();
        $started = true;

$stmt = $conn->prepare(
    'SELECT status, stock_deducted
     FROM orders
     WHERE order_id = ?
     FOR UPDATE'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        if (strtolower($order['status']) === 'accepted') {
            throw new RuntimeException('This order is already accepted.');
        }

        if (strtolower($order['status']) !== 'pending') {
            throw new RuntimeException('Only pending orders can be accepted.');
        }

        if ((int) $order['stock_deducted'] === 1) {
            throw new RuntimeException('Stock was already deducted for this order.');
        }

        $stmt = $conn->prepare(
            'SELECT product_id, SUM(quantity) AS quantity
             FROM order_items WHERE order_id = ?
             GROUP BY product_id ORDER BY product_id'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!$items) {
            throw new RuntimeException('This order has no products.');
        }

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            $stmt = $conn->prepare(
                'SELECT Product_Name, Stock_Quantity FROM product
                 WHERE Product_ID = ? FOR UPDATE'
            );
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product || $quantity < 1) {
                throw new RuntimeException('Invalid product in the order.');
            }

            if ((int) $product['Stock_Quantity'] < $quantity) {
                throw new RuntimeException(
                    'Not enough stock: ' . $product['Product_Name']
                );
            }

            $stmt = $conn->prepare(
                'UPDATE product
                 SET Stock = Stock_Quantity - ?,
                     Stock_Quantity = Stock_Quantity - ?
                 WHERE Product_ID = ? AND Stock_Quantity >= ?'
            );
            $stmt->bind_param(
                'iiii', $quantity, $quantity, $productId, $quantity
            );
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                throw new RuntimeException('Could not update stock.');
            }

            $stmt->close();
        }

       $stmt = $conn->prepare(
    "UPDATE orders
     SET status = 'accepted',
         
         stock_deducted = 1
     WHERE order_id = ?"
);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $started = false;

        $_SESSION['order_message'] =
            "Order #$id accepted. Stock updated.";
    } catch (Throwable $exception) {
        if ($started) {
            $conn->rollback();
        }

        error_log($exception->getMessage());

        $_SESSION['order_message'] =
            $exception instanceof mysqli_sql_exception
            ? 'Could not accept the order. Please try again.'
            : $exception->getMessage();
    }

    header('Location: orders.php');
    exit();
}

$message = $_SESSION['order_message'] ?? '';
unset($_SESSION['order_message']);

$orders = $conn->query(
    'SELECT order_id, first_name, last_name, total_amount,
            payment_status, status, stock_deducted, created_at
     FROM orders ORDER BY order_id DESC'
)->fetch_all(MYSQLI_ASSOC);

$itemStmt = $conn->prepare(
    'SELECT product_name, quantity, price
     FROM order_items WHERE order_id = ? ORDER BY item_id'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Spices Lanka</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6f2;
            color: #183829;
        }

        header {
            background: #0b2216;
            color: white;
            padding: 22px;
        }

        header a { color: white; margin-right: 20px; }

        main { max-width: 1100px; margin: 30px auto; padding: 15px; }

        .order {
            background: white;
            padding: 22px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        button {
            background: #215739;
            color: white;
            padding: 12px 20px;
            border: 0;
            border-radius: 5px;
            margin-top: 15px;
            cursor: pointer;
        }

        .message { background: #e8eedf; padding: 15px; }
    </style>
</head>
<body>
<header>
    <h2>SPICES LANKA · ORDERS</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="products.php">Manage Products</a>
</header>

<main>
    <h1>Manage Orders</h1>

    <?php if ($message !== ''): ?>
        <p class="message"><?= orderEscape($message) ?></p>
    <?php endif; ?>

    <?php if (!$orders): ?>
        <p>No orders yet.</p>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <section class="order">
            <h2>Order #<?= (int) $order['order_id'] ?></h2>

            <p>
                Customer:
                <?= orderEscape($order['first_name'] . ' ' . $order['last_name']) ?>
            </p>

            <p>
                Total: LKR <?= number_format((float) $order['total_amount'], 2) ?>
            </p>

            <p>
                Payment: <?= orderEscape($order['payment_status']) ?>
                | Order: <?= orderEscape(ucfirst($order['status'])) ?>
            </p>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price (LKR)</th>
                    </tr>

                    <?php
                    $id = (int) $order['order_id'];
                    $itemStmt->bind_param('i', $id);
                    $itemStmt->execute();
                    $items = $itemStmt->get_result();
                    ?>

                    <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= orderEscape($item['product_name']) ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><?= number_format((float) $item['price'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <?php if (
                strtolower($order['status']) === 'pending' &&
                (int) $order['stock_deducted'] === 0
            ): ?>
                <form method="post">
                    <input type="hidden" name="csrf"
                           value="<?= orderEscape($_SESSION['order_csrf']) ?>">
                    <input type="hidden" name="order_id"
                           value="<?= (int) $order['order_id'] ?>">
<button type="submit"> Accept Order</button>                </form>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php $itemStmt->close(); ?>
</main>
</body>
</html>