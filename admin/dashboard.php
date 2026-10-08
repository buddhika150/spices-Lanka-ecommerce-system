<?php
require_once __DIR__ . '/auth.php';

$productCount = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM product"
)->fetch_assoc()['total'];

$orderCount = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM orders"
)->fetch_assoc()['total'];

$lowStockCount = (int) $conn->query(
    "SELECT COUNT(*) AS total
     FROM product
     WHERE COALESCE(Stock_Quantity, 0) <= 5"
)->fetch_assoc()['total'];

$recentOrders = $conn->query(
    "SELECT
        o.order_id,
        o.total_amount,
        o.payment_status,
        o.created_at,
        u.full_name
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.user_id
     ORDER BY o.created_at DESC, o.order_id DESC
     LIMIT 10"
);

function adminEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Spices Lanka</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6f2;
            color: #183829;
        }

        .admin-header {
            background: #0b2216;
            color: white;
            padding: 22px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .admin-header h2 {
            margin: 0;
            font-size: 22px;
        }

        .admin-nav {
            display: flex;
            gap: 20px;
        }

        .admin-nav a {
            color: white;
            text-decoration: none;
        }

        .admin-nav a:hover {
            text-decoration: underline;
        }

        .dashboard {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h1 {
            margin-bottom: 10px;
        }

        .welcome p {
            color: #666;
        }

        .connection-status {
            display: inline-block;
            background: #e4f2e7;
            color: #246635;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin-bottom: 35px;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border-top: 4px solid #215739;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        .summary-card h2 {
            font-size: 18px;
            color: #666;
            margin: 0 0 15px;
        }

        .summary-number {
            font-size: 40px;
            font-weight: bold;
            margin: 0;
        }

        .summary-card small {
            display: block;
            color: #777;
            margin-top: 10px;
        }

        .low-stock {
            border-top-color: #c18b20;
        }

        .orders-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 40px;
        }

        .orders-panel h2 {
            margin-top: 0;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f3f5ef;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            background: #f0f0e8;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 14px;
        }

        .empty-message {
            text-align: center;
            color: #777;
            padding: 30px;
        }

        @media (max-width: 700px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .orders-panel {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<header class="admin-header">
    <h2>SPICES LANKA · ADMIN</h2>

    <nav class="admin-nav">
        
        <a href="orders.php">Manage Orders</a>
        <a href="products.php">Manage Products</a>
        <a href="../index.php">View Website</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main class="dashboard">

    <div class="welcome">
        <h1>Admin Dashboard</h1>

        <p>
            Welcome, <?= adminEscape($adminUser['full_name']); ?>!
        </p>
    </div>

    <div class="connection-status">
        Database connected ✓
    </div>

    <section class="summary-grid" aria-label="Store summary">

        <div class="summary-card">
            <h2>Total Products</h2>
            <p class="summary-number"><?= $productCount; ?></p>
            <small>Products in your database</small>
        </div>

        <div class="summary-card">
            <h2>Total Orders</h2>
            <p class="summary-number"><?= $orderCount; ?></p>
            <small>Orders saved in your database</small>
        </div>

        <div class="summary-card low-stock">
            <h2>Low Stock</h2>
            <p class="summary-number"><?= $lowStockCount; ?></p>
            <small>Products with 5 units or fewer, including out of stock</small>
        </div>

    </section>

    <section class="orders-panel">
        <h2>Recent Orders</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total (LKR)</th>
                        <th>Payment Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($recentOrders->num_rows > 0): ?>

                        <?php while ($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    #<?= (int) $order['order_id']; ?>
                                </td>

                                <td>
                                    <?= adminEscape($order['full_name'] ?? 'Unknown customer'); ?>
                                </td>

                                <td>
                                    <?= number_format((float) $order['total_amount'], 2); ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= adminEscape($order['payment_status'] ?? 'Pending'); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= adminEscape($order['created_at']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty-message">
                                No orders saved yet.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>