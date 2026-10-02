<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Order Success - Spices Lanka</title>

    <link rel="stylesheet"
          href="css/style.css">

    <style>

        .success-container {
            max-width: 600px;
            margin: 60px auto;
            text-align: center;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .success-container h1 {
            color: #28a745;
        }

        .success-message {
            font-size: 18px;
            margin: 20px 0;
        }

        .success-question {
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0 20px;
        }

        .success-btn {
            display: inline-block;
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            margin: 5px;
        }

        .shop-btn {
            background: #0b2216;
        }

        .home-btn {
            background: #777;
        }

    </style>

</head>

<body>

<?php include 'components/header.php'; ?>


<div class="success-container">

    <h1>
        Payment Successful!
    </h1>

    <p class="success-message">
        Thank you for your order.
        Your payment has been processed successfully via PayHere Sandbox.
    </p>

    <p class="success-question">
        Would you like to continue shopping?
    </p>


    <!-- Continue Shopping -->

    <a href="shop.php"
       class="success-btn shop-btn">

        Yes, Continue Shopping

    </a>


    <!-- Go Home -->

    <a href="index.php"
       class="success-btn home-btn">

        No, Go to Home

    </a>

</div>


<?php include 'components/footer.php'; ?>


<script>

    // Clear the cart after successful payment
    localStorage.removeItem('spices_cart');

</script>

</body>

</html>