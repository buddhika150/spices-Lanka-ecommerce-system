<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['currency'])) {
    $_SESSION['currency'] = $_GET['currency'] === 'USD' ? 'USD' : 'LKR';
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    
    $query_params = $_GET;
    unset($query_params['currency']);
    if (!empty($query_params)) {
        $redirect_url .= '?' . http_build_query($query_params);
    }
    header("Location: " . $redirect_url);
    exit();
}

$current_currency = $_SESSION['currency'] ?? 'LKR';
?>

<header class="header-container">
    <div class="header-main">
        <div class="header-logo">
            <a href="index.php">
                <img src="assets/logo.png" alt="Spices Lanka" onerror="this.src='assets/chili.jpg';">
            </a>
        </div>

        <nav class="header-nav">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="shop.php" class="<?= $current_page == 'shop.php' ? 'active' : ''; ?>">Shop</a>
            <a href="shop.php">Categories</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
        </nav>

        <div class="header-right">
            <div class="currency-toggle">
                <a href="?<?= http_build_query(array_merge($_GET, ['currency' => 'LKR'])) ?>" class="btn-toggle <?= $current_currency === 'LKR' ? 'active' : ''; ?>">LK Sri Lanka</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['currency' => 'USD'])) ?>" class="btn-toggle <?= $current_currency === 'USD' ? 'active' : ''; ?>">✈ Foreign</a>
            </div>

            <div class="header-icons">
                <button type="button" class="icon-btn" onclick="document.getElementById('navSearchForm').submit();" title="Search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2979FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>

                <a href="login.php" class="icon-btn" title="Account">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="#4A148C"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                </a>

                <a href="cart.php" class="icon-btn cart-wrapper" title="Cart">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="#FF9800"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                   <span id="cart-badge" class="cart-badge">0</span>
                </a>
            </div>
        </div>
    </div>

    <div class="header-search-container">
        <form id="navSearchForm" action="shop.php" method="GET" style="margin:0; padding:0; width:100%;">
            <input type="text" name="search" class="search-input" placeholder="Search spices..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </form>
    </div>
</header>