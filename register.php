<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/components/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validation Checks
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // 2. Check if Email Already Exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            // 3. Hash Password & Insert User
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $full_name, $email, $password_hash);

            if ($insert_stmt->execute()) {
                $user_id = $insert_stmt->insert_id;

                // Create empty profile record in user_profiles table
                $profile_stmt = $conn->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $profile_stmt->bind_param("i", $user_id);
                $profile_stmt->execute();

                $success = "Registration successful! You can now <a href='login.php'>Login</a>.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Spices Lanka</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <div class="cart-container" style="max-width: 500px; margin: 50px auto;">
        <h1 class="cart-title" style="text-align: center;">Create an Account</h1>

        <?php if (!empty($error)): ?>
            <div style="background: #ffe6e6; color: #cc0000; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: #e6ffe6; color: #008000; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= $success; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Full Name</label>
                <input type="text" name="full_name" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Confirm Password</label>
                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <button type="submit" class="btn-checkout" style="width: 100%; border: none; cursor: pointer;">Register</button>

            <p style="text-align: center; margin-top: 15px; font-size: 14px;">
                Already have an account? <a href="login.php" style="color: #b8860b; font-weight: bold;">Login here</a>
            </p>
        </form>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/cart.js"></script>
</body>
</html>