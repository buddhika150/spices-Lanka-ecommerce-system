<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/components/config.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter both email and password.";
    } else {
        // Fetch user record by email
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, password_hash, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {

            // Regenerate session ID after successful login
            session_regenerate_id(true);

            // Set session data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // Return to checkout if login started from checkout
            if (($_SESSION['redirect_after_login'] ?? '') === 'checkout.php') {
    $redirect = 'checkout.php';
} elseif ($user['role'] === 'admin') {
    $redirect = 'admin/dashboard.php';
} else {
    $redirect = 'profile.php';
}

            unset($_SESSION['redirect_after_login']);

            header('Location: ' . $redirect);
            exit();

        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Spices Lanka</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <div class="cart-container" style="max-width: 450px; margin: 50px auto;">

        <h1 class="cart-title" style="text-align: center;">
            Account Login
        </h1>

        <?php if (($_SESSION['redirect_after_login'] ?? '') === 'checkout.php'): ?>
            <p style="text-align: center; color: #0b2216; margin-bottom: 15px;">
                Please log in to continue with your purchase.
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div style="background: #ffe6e6; color: #cc0000; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form
            action="login.php"
            method="POST"
            style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
        >
            <div style="margin-bottom: 15px;">
                <label
                    for="email"
                    style="display: block; margin-bottom: 5px; font-weight: bold;"
                >
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="username"
                    required
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                >
            </div>

            <div style="margin-bottom: 20px;">
                <label
                    for="password"
                    style="display: block; margin-bottom: 5px; font-weight: bold;"
                >
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                >
            </div>

            <button
                type="submit"
                class="btn-checkout"
                style="width: 100%; border: none; cursor: pointer;"
            >
                Sign In
            </button>

            <p style="text-align: center; margin-top: 15px; font-size: 14px;">
                Don't have an account?
                <a
                    href="register.php"
                    style="color: #b8860b; font-weight: bold;"
                >
                    Register here
                </a>
            </p>
        </form>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/cart.js"></script>

</body>
</html>