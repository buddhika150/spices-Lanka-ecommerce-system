<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';



$merchant_id = "1238406";
$merchant_secret = "NzQ2MDg1MTcwMzQ4Mjg0NDc5NDI1NjY2MzY3NDQxNDU5NDczNjM=";

$currency = "LKR";

$error = "";
$cartItems = [];
$total = 0;

// Get cart data sent from JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {

    $cartItems = json_decode($_POST['cart_data'], true);

    if (!is_array($cartItems) || empty($cartItems)) {
        $error = "Your cart is empty.";
        $cartItems = [];
    } else {

        // Calculate total
        foreach ($cartItems as $item) {

            $price = isset($item['price']) ? (float)$item['price'] : 0;
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;

            if ($price > 0 && $quantity > 0) {
                $total += $price * $quantity;
            }
        }

        $total = number_format($total, 2, '.', '');
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {

    $cart_data = $_POST['cart_data'] ?? '';

    $cartItems = json_decode($cart_data, true);

    if (!is_array($cartItems) || empty($cartItems)) {
        $error = "Your cart is empty.";
    } else {

        $total = 0;

        foreach ($cartItems as $item) {

            $price = isset($item['price']) ? (float)$item['price'] : 0;
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;

            if ($price > 0 && $quantity > 0) {
                $total += $price * $quantity;
            }
        }

        $amount = number_format($total, 2, '.', '');

        if ((float)$amount <= 0) {
            $error = "Invalid payment amount.";
        } else {

          

            $order_id = "SPICES_" . date("YmdHis") . "_" . rand(1000, 9999);

          

            $merchant_secret_md5 = strtoupper(md5($merchant_secret));

            $hash = strtoupper(
                md5(
                    $merchant_id .
                    $order_id .
                    $amount .
                    $currency .
                    $merchant_secret_md5
                )
            );


            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');
            $city       = trim($_POST['city'] ?? '');

        

            if (
                $first_name === '' ||
                $last_name === '' ||
                $email === '' ||
                $phone === '' ||
                $city === ''
            ) {

                $error = "Please fill all customer details.";

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $error = "Please enter a valid email address.";

            } else {

              

                ?>

                <!DOCTYPE html>
                <html lang="en">

                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Redirecting to PayHere</title>
                </head>

                <body>

                    <p style="text-align:center; margin-top:100px;">
                        Redirecting to PayHere...
                    </p>

                    <form id="payhereForm"
                          method="POST"
                          action="https://sandbox.payhere.lk/pay/checkout">

                        <input type="hidden" name="merchant_id"
                               value="<?php echo htmlspecialchars($merchant_id); ?>">

                      <input type="hidden"
       name="return_url"
       value="http://localhost/spices_lanka/order_success.php">

                        <input type="hidden" name="cancel_url"
                               value="http://localhost/spices-lanka/payment_cancel.php">

                        <input type="hidden" name="notify_url"
                               value="http://localhost/spices-lanka/payment_notify.php">

                        <input type="hidden" name="order_id"
                               value="<?php echo htmlspecialchars($order_id); ?>">

                        <input type="hidden" name="items"
                               value="Spices Lanka Order">

                        <input type="hidden" name="currency"
                               value="<?php echo $currency; ?>">

                        <input type="hidden" name="amount"
                               value="<?php echo $amount; ?>">

                        <input type="hidden" name="first_name"
                               value="<?php echo htmlspecialchars($first_name); ?>">

                        <input type="hidden" name="last_name"
                               value="<?php echo htmlspecialchars($last_name); ?>">

                        <input type="hidden" name="email"
                               value="<?php echo htmlspecialchars($email); ?>">

                        <input type="hidden" name="phone"
                               value="<?php echo htmlspecialchars($phone); ?>">

                        <input type="hidden" name="address"
                               value="Spices Lanka">

                        <input type="hidden" name="city"
                               value="<?php echo htmlspecialchars($city); ?>">

                        <input type="hidden" name="country"
                               value="Sri Lanka">

                        <input type="hidden" name="hash"
                               value="<?php echo $hash; ?>">

                    </form>

                    <script>
                        document.getElementById("payhereForm").submit();
                    </script>

                </body>

                </html>

                <?php

                exit;
            }
        }
    }
}



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = "Please access checkout from your shopping cart.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Checkout - Spices Lanka</title>

    <link rel="stylesheet"
          href="css/style.css?v=1.1">

    <style>

        .checkout-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .checkout-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .checkout-box {
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
        }

        .checkout-box h2 {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #333;
        }

        .pay-button {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
        }

        .error-message {
            padding: 15px;
            margin-bottom: 20px;
            background: #ffe5e5;
            color: #b00000;
            border-radius: 5px;
        }

        @media (max-width: 768px) {

            .checkout-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include 'components/header.php'; ?>

<div class="checkout-container">

    <h1 class="checkout-title">
        Checkout
    </h1>

    <?php if ($error !== ''): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <div class="checkout-grid">

        <!-- Customer Details -->

        <div class="checkout-box">

            <h2>
                Customer Details
            </h2>

            <form method="POST"
                  action="checkout.php"
                  id="checkoutForm">

                <!-- JavaScript will put cart data here -->

                <input type="hidden"
                       name="cart_data"
                       id="cart_data">

                <div class="form-group">

                    <label>
                        First Name
                    </label>

                    <input type="text"
                           name="first_name"
                           required>

                </div>

                <div class="form-group">

                    <label>
                        Last Name
                    </label>

                    <input type="text"
                           name="last_name"
                           required>

                </div>

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           required>

                </div>

                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input type="text"
                           name="phone"
                           required>

                </div>

                <div class="form-group">

                    <label>
                        City
                    </label>

                    <input type="text"
                           name="city"
                           required>

                </div>

                <button type="submit"
                        name="pay_now"
                        class="pay-button">

                    Pay Now

                </button>

            </form>

        </div>


        <!-- Order Summary -->

        <div class="checkout-box">

            <h2>
                Order Summary
            </h2>

            <div id="checkout-items">

                <!-- JavaScript will add cart items here -->

            </div>

            <div class="order-total">

                <span>
                    Grand Total
                </span>

                <span id="checkout-total">
                    LKR 0.00
                </span>

            </div>

        </div>

    </div>

</div>

<?php include 'components/footer.php'; ?>


<script>



const checkoutCart =
    JSON.parse(localStorage.getItem('spices_cart')) || [];

const checkoutItems =
    document.getElementById('checkout-items');

const checkoutTotal =
    document.getElementById('checkout-total');

const cartDataInput =
    document.getElementById('cart_data');




if (checkoutCart.length === 0) {

    checkoutItems.innerHTML = `
        <p style="text-align:center;">
            Your cart is empty.
        </p>
    `;

    checkoutTotal.innerText = "LKR 0.00";

} else {

    let total = 0;

    checkoutCart.forEach(item => {

        const itemTotal =
            parseFloat(item.price) *
            parseInt(item.quantity);

        total += itemTotal;

        const row =
            document.createElement('div');

        row.className = 'order-item';

        row.innerHTML = `
            <span>
                ${item.name} × ${item.quantity}
            </span>

            <span>
                LKR ${itemTotal.toFixed(2)}
            </span>
        `;

        checkoutItems.appendChild(row);

    });

    checkoutTotal.innerText =
        `LKR ${total.toFixed(2)}`;

}




cartDataInput.value =
    JSON.stringify(checkoutCart);




document.getElementById('checkoutForm')
    .addEventListener('submit', function(event) {

        if (checkoutCart.length === 0) {

            event.preventDefault();

            alert("Your cart is empty.");

        }

    });

</script>

</body>

</html>