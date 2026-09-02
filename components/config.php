<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "spices_lanka";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Currency Switcher Logic
if (isset($_GET['currency'])) {
    if ($_GET['currency'] === 'USD') {
        $_SESSION['currency'] = 'USD';
    } else {
        $_SESSION['currency'] = 'LKR';
    }
} elseif (!isset($_SESSION['currency'])) {
    $_SESSION['currency'] = 'LKR';
}

function displayPrice($priceLKR, $is_usd = false, $exchange_rate = 300) {
    if (isset($_SESSION['currency']) && $_SESSION['currency'] === 'USD') {
        return '$ ' . number_format((float)$priceLKR / $exchange_rate, 2);
    }
    return 'Rs. ' . number_format((float)$priceLKR, 2);
}
?>