<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/components/config.php';

// Route Protection
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Profile Update & Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $shipping_address = trim($_POST['shipping_address']);
    $billing_address = trim($_POST['billing_address']);

    if (empty($full_name)) {
        $error = "Full Name cannot be empty.";
    } else {
        // Fetch current user details
        $stmt = $conn->prepare("SELECT avatar FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $current_avatar = $stmt->get_result()->fetch_assoc()['avatar'] ?? 'default_avatar.png';

        $avatar_filename = $current_avatar;

        // Image Upload Processing
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_exts)) {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
            } elseif ($file_size > 2 * 1024 * 1024) { // 2MB Limit
                $error = "File size exceeds 2MB limit.";
            } else {
                $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
                $upload_dir = __DIR__ . '/uploads/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    // Remove old profile picture if it's not default
                    if ($current_avatar !== 'default_avatar.png' && file_exists($upload_dir . $current_avatar)) {
                        unlink($upload_dir . $current_avatar);
                    }
                    $avatar_filename = $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if (empty($error)) {
            // Update users table
            $update_user = $conn->prepare("UPDATE users SET full_name = ?, avatar = ? WHERE user_id = ?");
            $update_user->bind_param("ssi", $full_name, $avatar_filename, $user_id);
            $update_user->execute();
            $_SESSION['user_name'] = $full_name;

            // Update user_profiles table
            $check_prof = $conn->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
            $check_prof->bind_param("i", $user_id);
            $check_prof->execute();

            if ($check_prof->get_result()->num_rows > 0) {
                $update_prof = $conn->prepare("UPDATE user_profiles SET phone = ?, shipping_address = ?, billing_address = ? WHERE user_id = ?");
                $update_prof->bind_param("sssi", $phone, $shipping_address, $billing_address, $user_id);
                $update_prof->execute();
            } else {
                $insert_prof = $conn->prepare("INSERT INTO user_profiles (user_id, phone, shipping_address, billing_address) VALUES (?, ?, ?, ?)");
                $insert_prof->bind_param("isss", $user_id, $phone, $shipping_address, $billing_address);
                $insert_prof->execute();
            }

            $message = "Profile updated successfully!";
        }
    }
}

// Fetch Profile Data
$stmt = $conn->prepare("
    SELECT u.full_name, u.email, u.avatar, u.role, u.created_at, 
           p.phone, p.shipping_address, p.billing_address 
    FROM users u 
    LEFT JOIN user_profiles p ON u.user_id = p.user_id 
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

$avatar_path = (!empty($user_data['avatar']) && file_exists(__DIR__ . '/uploads/' . $user_data['avatar']))
    ? 'uploads/' . $user_data['avatar']
    : 'https://via.placeholder.com/120?text=User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Spices Lanka</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'components/header.php'; ?>

    <div class="cart-container" style="max-width: 700px; margin: 40px auto; padding: 20px;">
        <h1 class="cart-title" style="text-align: center;">My Profile Dashboard</h1>

        <?php if (!empty($message)): ?>
            <div style="background: #e6ffe6; color: #008000; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: #ffe6e6; color: #cc0000; padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST" enctype="multipart/form-data" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            
            <!-- Avatar Display and Upload -->
            <div style="text-align: center; margin-bottom: 25px;">
                <img src="<?= $avatar_path; ?>" alt="Profile Picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #b8860b; margin-bottom: 10px;">
                <div>
                    <label style="display: inline-block; font-size: 14px; font-weight: bold; color: #0b2216; cursor: pointer;">
                        Change Profile Picture
                        <input type="file" name="profile_image" accept="image/*" style="display: block; margin: 8px auto; font-weight: normal;">
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Email Address (Read-only)</label>
                <input type="email" value="<?= htmlspecialchars($user_data['email']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user_data['full_name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="e.g. +94 77 123 4567" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Shipping Address</label>
                <textarea name="shipping_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?= htmlspecialchars($user_data['shipping_address'] ?? ''); ?></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 5px;">Billing Address</label>
                <textarea name="billing_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?= htmlspecialchars($user_data['billing_address'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-checkout" style="flex: 1; border: none; cursor: pointer;">Save Changes</button>
                <a href="logout.php" style="background: #d32f2f; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 5px; font-weight: bold; text-align: center;">Logout</a>
            </div>
        </form>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="js/cart.js"></script>
</body>
</html>