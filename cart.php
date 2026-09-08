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
    <title>Shopping Cart - Spices Lanka</title>
  <link rel="stylesheet" href="css/style.css?v=1.1">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <div class="cart-container">
        <h1 class="cart-title">Your Shopping Cart</h1>

        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="cart-table-body">
                <!-- js/cart.js මගින් rows එකතු වේ -->
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="cart-subtotal">LKR 0.00</span>
            </div>
            <div class="summary-row total">
                <span>Grand Total</span>
                <span id="cart-total">LKR 0.00</span>
            </div>
            <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/cart.js"></script>
</body>
</html>