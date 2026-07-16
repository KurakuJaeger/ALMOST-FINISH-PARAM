<?php
require_once dirname(__DIR__, 3) . '/src/config/app.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$cartCount = 0;
$favoriteCount = 0;

if (isset($_SESSION['user_id'], $pdo)) {
    $userId = (int) $_SESSION['user_id'];

    try {
        $cartStatement = $pdo->prepare(
            "SELECT COALESCE(SUM(cart_items.quantity), 0)
             FROM cart_items
             JOIN carts ON carts.cart_id = cart_items.cart_id
             WHERE carts.user_id = :user_id
               AND carts.status = 'active'"
        );
        $cartStatement->execute(['user_id' => $userId]);
        $cartCount = (int) $cartStatement->fetchColumn();
    } catch (PDOException) {
        $cartCount = 0;
    }

    try {
        $favoriteStatement = $pdo->prepare(
            'SELECT COUNT(*) FROM favorites WHERE user_id = :user_id'
        );
        $favoriteStatement->execute(['user_id' => $userId]);
        $favoriteCount = (int) $favoriteStatement->fetchColumn();
    } catch (PDOException) {
        $favoriteCount = 0;
    }
}
?>
<header class="navbar">
    <div class="logo">
        <a href="<?= htmlspecialchars(appUrl('store')) ?>" aria-label="PARAM storefront home">
            <img src="<?= htmlspecialchars(appUrl('store/images/logo-header.png')) ?>" alt="PARAM Store" class="img-logo">
        </a>
    </div>

    <nav class="nav-links" id="store-navigation" aria-label="Storefront navigation">
        <a href="<?= htmlspecialchars(appUrl('store/shop.php')) ?>" <?= $currentPage === 'shop.php' ? 'class="active-link"' : '' ?>>Shop</a>
        <a href="<?= htmlspecialchars(appUrl('store/AboutUs.php')) ?>" <?= $currentPage === 'AboutUs.php' ? 'class="active-link"' : '' ?>>About</a>
        <a href="<?= htmlspecialchars(appUrl('store/ContactUs.php')) ?>" <?= $currentPage === 'ContactUs.php' ? 'class="active-link"' : '' ?>>Contact</a>
        <a href="<?= htmlspecialchars(appUrl()) ?>">Main Site</a>
    </nav>

    <div class="nav-icons">
        <a href="#" title="Search" id="open-search" aria-label="Search products">
            <img src="<?= htmlspecialchars(appUrl('store/images/search.png')) ?>" alt="" class="custom-icon">
        </a>

        <a href="<?= htmlspecialchars(appUrl('store/favorites.php')) ?>" title="Favorites" aria-label="Favorites<?= $favoriteCount ? ' (' . $favoriteCount . ')' : '' ?>" class="icon-wrapper nav-fav-button <?= $currentPage === 'favorites.php' ? 'active-icon' : '' ?>">
            <img src="<?= htmlspecialchars(appUrl('store/images/heart.png')) ?>" alt="" class="custom-icon heart-icon">
            <?php if ($favoriteCount > 0): ?><span class="nav-badge"><?= $favoriteCount ?></span><?php endif; ?>
        </a>

        <a href="<?= htmlspecialchars(appUrl('store/cart.php')) ?>" title="Cart" aria-label="Cart<?= $cartCount ? ' (' . $cartCount . ')' : '' ?>" class="icon-wrapper cart-link <?= $currentPage === 'cart.php' ? 'active-icon' : '' ?>">
            <img src="<?= htmlspecialchars(appUrl('store/images/shopping-cart.png')) ?>" alt="" class="custom-icon">
            <?php if ($cartCount > 0): ?><span class="nav-badge"><?= $cartCount ?></span><?php endif; ?>
        </a>

        <a href="<?= htmlspecialchars(appUrl('store/Profile.php')) ?>" title="Profile" aria-label="Customer profile" class="<?= $currentPage === 'Profile.php' ? 'active-icon' : '' ?>">
            <img src="<?= htmlspecialchars(appUrl('store/images/user.png')) ?>" alt="" class="custom-icon">
        </a>

        <button class="menu-button" type="button" aria-label="Toggle storefront navigation" aria-controls="store-navigation" aria-expanded="false">&#9776;</button>
    </div>
</header>
