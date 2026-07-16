<?php require_once dirname(__DIR__, 3) . '/src/config/app.php'; ?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="<?= htmlspecialchars(appUrl('store/images/logo-footer.png')) ?>" alt="PARAM Store">
                <p>The PARAM customer storefront for products, favorites, carts, and orders.</p>
            </div>

            <div class="footer-column">
                <h3>Store</h3>
                <a href="<?= htmlspecialchars(appUrl('store')) ?>">Store Home</a>
                <a href="<?= htmlspecialchars(appUrl('store/shop.php')) ?>">Shop</a>
                <a href="<?= htmlspecialchars(appUrl('store/AboutUs.php')) ?>">About</a>
                <a href="<?= htmlspecialchars(appUrl()) ?>">Main Site</a>
            </div>

            <div class="footer-column">
                <h3>Account</h3>
                <a href="<?= htmlspecialchars(appUrl('login')) ?>">Log in</a>
                <a href="<?= htmlspecialchars(appUrl('register')) ?>">Create account</a>
                <a href="<?= htmlspecialchars(appUrl('apply')) ?>">Staff application</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> PARAM. All rights reserved.</span>
            <span>For educational purposes only.</span>
        </div>
    </div>
</footer>

<div id="search-overlay" class="search-overlay">
    <div class="search-container">
        <button id="close-search" class="btn-close-search" type="button" aria-label="Close search">&times;</button>
        <form action="<?= htmlspecialchars(appUrl('store/shop.php')) ?>" method="GET" class="search-form">
            <input type="search" name="query" class="search-input" value="<?= htmlspecialchars(trim((string) ($_GET['query'] ?? ''))) ?>" placeholder="Search products or categories..." maxlength="100" aria-label="Search products or categories">
            <button type="submit" class="btn-search-submit">Search</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuButton = document.querySelector('.menu-button');
    const navigation = document.querySelector('.nav-links');
    const searchOverlay = document.getElementById('search-overlay');
    const openSearch = document.getElementById('open-search');
    const closeSearch = document.getElementById('close-search');

    if (menuButton && navigation) {
        menuButton.addEventListener('click', function () {
            const isOpen = navigation.classList.toggle('open');
            menuButton.setAttribute('aria-expanded', String(isOpen));
        });
    }

    if (openSearch && searchOverlay) {
        openSearch.addEventListener('click', function (event) {
            event.preventDefault();
            searchOverlay.classList.add('active');
        });
    }

    if (closeSearch && searchOverlay) {
        closeSearch.addEventListener('click', function () {
            searchOverlay.classList.remove('active');
        });
    }
});
</script>
</body>
</html>
