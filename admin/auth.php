<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../components/config.php';

// Login is required.
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check the current role directly from the database.
$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT full_name, role FROM users WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$adminUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adminUser || $adminUser['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied. This page is for administrators only.');
}

$_SESSION['user_role'] = $adminUser['role'];
$_SESSION['user_name'] = $adminUser['full_name'];