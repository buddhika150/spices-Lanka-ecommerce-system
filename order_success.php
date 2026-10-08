<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';
require_once __DIR__ . '/components/payhere_api.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$orderId = (int) ($_SESSION['last_order_id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    'SELECT order_id, total_amount, currency, payment_status, status
     FROM orders WHERE order_id = ? AND user_id = ?'
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

$verificationFailed = false;

if (
    $order &&
    strtolower((string) $order['payment_status']) === 'pending'
) {
    try {
        $paymentId = findVerifiedPayherePayment(
            $orderId,
            $order['total_amount'],
            $order['currency']
        );

        if ($paymentId !== null) {
            $stmt = $conn->prepare(
                "UPDATE orders
                 SET payment_status = 'Paid',
                     payment_method = 'payhere',
                     payment_id = ?
                 WHERE order_id = ? AND user_id = ?
                   AND payment_status = 'Pending'"
            );
            $stmt->bind_param('sii', $paymentId, $orderId, $userId);
            $stmt->execute();
            $stmt->close();

            // Reload the saved payment status.
            $stmt = $conn->prepare(
                'SELECT order_id, total_amount, currency,
                        payment_status, status
                 FROM orders WHERE order_id = ? AND user_id = ?'
            );
            $stmt->bind_param('ii', $orderId, $userId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } catch (Throwable $exception) {
        $verificationFailed = true;
        error_log('PayHere payment verification failed for order ' . $orderId);
    }
}

$paid = $order &&
        strtolower((string) $order['payment_status']) === 'paid';

$pending = $order &&
           strtolower((string) $order['payment_status']) === 'pending';

$purchasedItems = [];

if ($paid) {
    $title = 'Payment Successful!';
    $message = strtolower((string) $order['status']) === 'accepted'
        ? 'Thank you! Your order has been confirmed.'
        : 'Thank you for your payment. A confirmation message will appear in your account once your order is accepted.';

    $stmt = $conn->prepare(
        'SELECT product_id AS id, SUM(quantity) AS quantity
         FROM order_items WHERE order_id = ?
         GROUP BY product_id'
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $purchasedItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    unset($_SESSION['checkout_token']);
} elseif ($pending) {
    $title = 'Payment Confirmation Pending';
    $message = $verificationFailed
        ? 'We could not check your payment right now. Please try again.'
        : 'Payment confirmation is not available yet. Please check again shortly.';
} elseif ($order) {
    $title = 'Payment Status';
    $message = 'Your payment has not been confirmed as successful.';
} else {
    $title = 'Order Not Found';
    $message = 'Please return to the shop and place an order.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - Spices Lanka</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .success-container {
            max-width: 650px;
            margin: 60px auto;
            text-align: center;
            background: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .success-container h1 {
            color: <?= $paid ? '#215739' : '#9a6a15' ?>;
            margin-bottom: 20px;
        }

        .success-container p {
            margin: 15px 0;
        }

        .success-question {
            font-size: 18px;
            font-weight: bold;
            margin-top: 25px;
        }

        .success-btn {
            display: inline-block;
            background: #0b2216;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin: 8px;
        }

        .home-btn {
            background: #777;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/components/header.php'; ?>

<div class="success-container">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>

    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($order): ?>
        <p>
            Order #<?= (int) $order['order_id'] ?>
            · LKR <?= number_format((float) $order['total_amount'], 2) ?>
        </p>
    <?php endif; ?>

    <p class="success-question">
        Would you like to continue shopping?
    </p>
<?php if ($pending): ?>
    <a class="success-btn" href="order_success.php">
        Check Again
    </a>
<?php endif; ?>
    <a class="success-btn" href="shop.php">
        Yes, Continue Shopping
    </a>

    <a class="success-btn home-btn" href="index.php">
        No, Go to Home
    </a>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>

<?php if ($paid): ?>
<script>
try {
    const clearedKey = 'spices_order_cleared_<?= $orderId ?>';

    if (!localStorage.getItem(clearedKey)) {
        let currentCart = JSON.parse(
            localStorage.getItem('spices_cart') || '[]'
        );

        const purchasedItems = <?= json_encode(
            $purchasedItems,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;

        if (Array.isArray(currentCart)) {
            for (const bought of purchasedItems) {
                let remaining = Number(bought.quantity);

                for (const item of currentCart) {
                    if (
                        item &&
                        String(item.id) === String(bought.id) &&
                        remaining > 0
                    ) {
                        const quantity = Math.max(
                            0, Number(item.quantity) || 0
                        );
                        const remove = Math.min(quantity, remaining);

                        item.quantity = quantity - remove;
                        remaining -= remove;
                    }
                }
            }

            currentCart = currentCart.filter(
                item => item && Number(item.quantity) > 0
            );

            localStorage.setItem(
                'spices_cart', JSON.stringify(currentCart)
            );
            localStorage.setItem(clearedKey, '1');

            const badge = document.getElementById('cart-badge');

            if (badge) {
                badge.textContent = currentCart.reduce(
                    (sum, item) => sum + Number(item.quantity),
                    0
                );
            }
        }
    }
} catch (error) {
    console.warn('Could not update the cart.');
}
</script>
<?php endif; ?>

</body>
</html>