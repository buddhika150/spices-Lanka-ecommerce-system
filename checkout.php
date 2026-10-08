<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}

require_once __DIR__ . '/components/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (empty($_SESSION['checkout_token'])) {

    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));

}

// Login is required before accessing checkout.

if (empty($_SESSION['user_id'])) {

    $_SESSION['redirect_after_login'] = 'checkout.php';

    header('Location: login.php');

    exit();

}

$merchant_id = "1238406";
$merchant_secret = "NzQ2MDg1MTcwMzQ4Mjg0NDc5NDI1NjY2MzY3NDQxNDU5NDczNjM=";
$merchant_secret = trim($merchant_secret);
$merchant_secret_md5 = strtoupper(md5($merchant_secret));

$currency = "LKR";

$error = "";

$cartItems = [];

$total = 0;

// Process payment only when Pay Now is clicked.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {

    $cartItems = json_decode(is_string($_POST['cart_data'] ?? null) ? $_POST['cart_data'] : '[]', true);

    if (!is_array($cartItems) || empty($cartItems)) {

        $error = "Your cart is empty.";

    } else {

        $first_name = trim(is_string($_POST['first_name'] ?? null) ? $_POST['first_name'] : '');

        $last_name = trim(is_string($_POST['last_name'] ?? null) ? $_POST['last_name'] : '');

        $email = trim(is_string($_POST['email'] ?? null) ? $_POST['email'] : '');

        $phone = trim(is_string($_POST['phone'] ?? null) ? $_POST['phone'] : '');

        $city = trim(is_string($_POST['city'] ?? null) ? $_POST['city'] : '');

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

            $checkoutToken = $_POST['checkout_token'] ?? '';
            if (!is_string($checkoutToken) ||
                !hash_equals($_SESSION['checkout_token'], $checkoutToken)) {
                http_response_code(403);
                exit('Invalid checkout request. Refresh checkout.');
            }
            $transactionStarted = false;
            try {
                foreach (['first_name'=>50, 'last_name'=>50, 'email'=>100,
                          'phone'=>20, 'city'=>50] as $field => $limit) {
                    if (mb_strlen($$field) > $limit) {
                        throw new RuntimeException('Customer details are too long.');
                    }
                }
                $quantities = [];
                foreach ($cartItems as $item) {
                    if (!is_array($item)) throw new RuntimeException('Invalid cart.');
                    $pid = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
                    $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
                    if (!$pid || $pid < 1 || !$qty || $qty < 1 || $qty > 10000) {
                        throw new RuntimeException('Invalid product or quantity.');
                    }
                    $quantities[$pid] = ($quantities[$pid] ?? 0) + $qty;
                    if ($quantities[$pid] > 10000) throw new RuntimeException('Quantity is too high.');
                }
                if (!$quantities || count($quantities) > 100) throw new RuntimeException('Invalid cart.');
                ksort($quantities);
                $userId = (int) $_SESSION['user_id'];
                $conn->begin_transaction();
                $transactionStarted = true;
                $stmt = $conn->prepare('SELECT * FROM orders WHERE checkout_token = ? FOR UPDATE');
                $stmt->bind_param('s', $checkoutToken);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($existing) {
                    if ((int)$existing['user_id'] !== $userId || $existing['payment_status'] !== 'Pending') {
                        throw new RuntimeException('This checkout has already been processed.');
                    }
                    $order_id = (string)$existing['order_id'];
                    $amount = number_format((float)$existing['total_amount'], 2, '.', '');
                    $currency = $existing['currency'];
                    // Retry the same saved order with its original customer details.
                    foreach (['first_name','last_name','email','phone','city'] as $field) {
                        $$field = $existing[$field];
                    }
                } else {
                    $verifiedItems = [];
                    $totalCents = 0;
                    $stmt = $conn->prepare('SELECT Product_Name, Price, Stock_Quantity, is_active FROM product WHERE Product_ID = ? FOR UPDATE');
                    foreach ($quantities as $pid => $qty) {
                        $stmt->bind_param('i', $pid);
                        $stmt->execute();
                        $product = $stmt->get_result()->fetch_assoc();
                        if (!$product || (int)$product['is_active'] !== 1) {
                            throw new RuntimeException('A product is unavailable. Update your cart.');
                        }
                        if ((int)$product['Stock_Quantity'] < $qty) {
                            throw new RuntimeException('Not enough stock for ' . $product['Product_Name']);
                        }
                        $cents = (int)round((float)$product['Price'] * 100);
                        if ($cents <= 0) throw new RuntimeException('Invalid product price.');
                        $totalCents += $cents * $qty;
                        $verifiedItems[] = ['id'=>$pid, 'quantity'=>$qty,
                            'name'=>$product['Product_Name'], 'price'=>number_format($cents/100,2,'.','')];
                    }
                    $stmt->close();
                    if ($totalCents <= 0 || $totalCents > 9999999999) throw new RuntimeException('Invalid order total.');
                    $amount = number_format($totalCents/100,2,'.','');
                    $currency = 'LKR';
                    $address = 'Spices Lanka';
                    $customerName = $first_name . ' ' . $last_name;
                    $stmt = $conn->prepare("INSERT INTO orders
                        (user_id,first_name,last_name,email,phone,address,city,total_amount,currency,
                         payment_status,status,payment_method,stock_deducted,checkout_token,customer_name)
                        VALUES (?,?,?,?,?,?,?,?,?,'Pending','pending','payhere',0,?,?)");
                    $stmt->bind_param('issssssssss',$userId,$first_name,$last_name,$email,$phone,
                        $address,$city,$amount,$currency,$checkoutToken,$customerName);
                    $stmt->execute();
                    $databaseOrderId = (int)$conn->insert_id;
                    $stmt->close();
                    $stmt = $conn->prepare('INSERT INTO order_items (order_id,product_id,quantity,price,product_name) VALUES (?,?,?,?,?)');
                    foreach ($verifiedItems as $item) {
                        $stmt->bind_param('iiiss',$databaseOrderId,$item['id'],$item['quantity'],$item['price'],$item['name']);
                        $stmt->execute();
                    }
                    $stmt->close();
                    $order_id = (string)$databaseOrderId;
                }
                $conn->commit();
                $transactionStarted = false;
                $_SESSION['last_order_id'] = (int)$order_id;
                
            } catch (Throwable $exception) {
                if ($transactionStarted) $conn->rollback();
                error_log($exception->getMessage());
                http_response_code(400);
                $message = $exception instanceof RuntimeException && !($exception instanceof mysqli_sql_exception)
                    ? $exception->getMessage() : 'Could not save your order. Please try again.';
                exit(htmlspecialchars($message,ENT_QUOTES,'UTF-8') . '<br><a href="checkout.php">Back to Checkout</a>');
            }

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

            <form

                id="payhereForm"

                method="POST"

                action="https://sandbox.payhere.lk/pay/checkout"

            >

                <input type="hidden" name="merchant_id"

                       value="<?= htmlspecialchars($merchant_id, ENT_QUOTES); ?>">

                <input type="hidden" name="return_url"

                       value="http://localhost/spices_lanka/order_success.php">

                <input type="hidden" name="cancel_url"

                       value="http://localhost/spices_lanka/payment_cancel.php">

            <input type="hidden" name="notify_url"
       value="http://localhost/spices_lanka/payment_notify.php">

                <input type="hidden" name="order_id"

                       value="<?= htmlspecialchars($order_id, ENT_QUOTES); ?>">

                <input type="hidden" name="items"

                       value="Spices Lanka Order">

                <input type="hidden" name="currency"

                       value="<?= htmlspecialchars($currency, ENT_QUOTES); ?>">

                <input type="hidden" name="amount"

                       value="<?= htmlspecialchars($amount, ENT_QUOTES); ?>">

                <input type="hidden" name="first_name"

                       value="<?= htmlspecialchars($first_name, ENT_QUOTES); ?>">

                <input type="hidden" name="last_name"

                       value="<?= htmlspecialchars($last_name, ENT_QUOTES); ?>">

                <input type="hidden" name="email"

                       value="<?= htmlspecialchars($email, ENT_QUOTES); ?>">

                <input type="hidden" name="phone"

                       value="<?= htmlspecialchars($phone, ENT_QUOTES); ?>">

                <input type="hidden" name="address"

                       value="Spices Lanka">

                <input type="hidden" name="city"

                       value="<?= htmlspecialchars($city, ENT_QUOTES); ?>">

                <input type="hidden" name="country"

                       value="Sri Lanka">

                <input type="hidden" name="hash"

                       value="<?= htmlspecialchars($hash, ENT_QUOTES); ?>">

                <noscript>

                    <button type="submit">Continue to PayHere</button>

                </noscript>

            </form>

            <script>

                document.getElementById('payhereForm').submit();

            </script>

            </body>

            </html>

            <?php

            exit();

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Spices Lanka</title>

    <link rel="stylesheet" href="css/style.css?v=1.1">

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

            gap: 15px;

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

        .pay-button:disabled {

            opacity: 0.5;

            cursor: not-allowed;

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

    <h1 class="checkout-title">Checkout</h1>

    <?php if ($error !== ''): ?>

        <div class="error-message">

            <?= htmlspecialchars($error, ENT_QUOTES); ?>

        </div>

    <?php endif; ?>

    <div class="checkout-grid">

        <div class="checkout-box">

            <h2>Customer Details</h2>

            <form method="POST" action="checkout.php" id="checkoutForm">

                <input type="hidden" name="checkout_token"

       value="<?= htmlspecialchars(

           $_SESSION['checkout_token'], ENT_QUOTES, 'UTF-8'

       ); ?>">

                <input type="hidden" name="cart_data" id="cart_data">

                <div class="form-group">

                    <label for="first_name">First Name</label>

                    <input

                        type="text"

                        id="first_name"

                        name="first_name"

                        value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES); ?>"

                        required

                    >

                </div>

                <div class="form-group">

                    <label for="last_name">Last Name</label>

                    <input

                        type="text"

                        id="last_name"

                        name="last_name"

                        value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES); ?>"

                        required

                    >

                </div>

                <div class="form-group">

                    <label for="email">Email</label>

                    <input

                        type="email"

                        id="email"

                        name="email"

                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"

                        required

                    >

                </div>

                <div class="form-group">

                    <label for="phone">Phone</label>

                    <input

                        type="text"

                        id="phone"

                        name="phone"

                        value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES); ?>"

                        required

                    >

                </div>

                <div class="form-group">

                    <label for="city">City</label>

                    <input

                        type="text"

                        id="city"

                        name="city"

                        value="<?= htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES); ?>"

                        required

                    >

                </div>

                <button

                    type="submit"

                    name="pay_now"

                    id="payButton"

                    class="pay-button"

                >

                    Pay Now

                </button>

            </form>

        </div>

        <div class="checkout-box">

            <h2>Order Summary</h2>

            <div id="checkout-items"></div>

            <div class="order-total">

                <span>Grand Total</span>

                <span id="checkout-total">LKR 0.00</span>

            </div>

        </div>

    </div>

</div>

<?php include 'components/footer.php'; ?>

<script>

    let checkoutCart = [];

    try {

        const storedCart = JSON.parse(

            localStorage.getItem('spices_cart') || '[]'

        );

        if (Array.isArray(storedCart)) {

            checkoutCart = storedCart.filter(item =>

                item &&

                Number.isFinite(Number(item.price)) &&

                Number(item.price) > 0 &&

                Number.isInteger(Number(item.quantity)) &&

                Number(item.quantity) > 0

            );

        }

    } catch (error) {

        checkoutCart = [];

    }

    const checkoutItems = document.getElementById('checkout-items');

    const checkoutTotal = document.getElementById('checkout-total');

    const cartDataInput = document.getElementById('cart_data');

    const payButton = document.getElementById('payButton');

    if (checkoutCart.length === 0) {

        const message = document.createElement('p');

        message.textContent = 'Your cart is empty.';

        message.style.textAlign = 'center';

        checkoutItems.appendChild(message);

        checkoutTotal.textContent = 'LKR 0.00';

        payButton.disabled = true;

    } else {

        let total = 0;

        checkoutCart.forEach(item => {

            const quantity = Number(item.quantity);

            const itemTotal = Number(item.price) * quantity;

            total += itemTotal;

            const row = document.createElement('div');

            row.className = 'order-item';

            const name = document.createElement('span');

            name.textContent = `${item.name} × ${quantity}`;

            const price = document.createElement('span');

            price.textContent = `LKR ${itemTotal.toFixed(2)}`;

            row.appendChild(name);

            row.appendChild(price);

            checkoutItems.appendChild(row);

        });

        checkoutTotal.textContent = `LKR ${total.toFixed(2)}`;

    }

    cartDataInput.value = JSON.stringify(checkoutCart);

    document.getElementById('checkoutForm').addEventListener(

        'submit',

        function (event) {

            if (checkoutCart.length === 0) {

                event.preventDefault();

                alert('Your cart is empty.');

            }

        }

    );

</script>

</body>

</html>