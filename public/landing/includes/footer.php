<?php require_once dirname(__DIR__, 3) . '/src/config/app.php'; ?>
<footer class="landing-footer">
    <div class="container landing-footer-grid">
        <div class="landing-footer-brand">
            <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-footer.png')) ?>" alt="PARAM">
            <p>Comfortable, versatile, and timeless clothing for every member of the family.</p>
        </div>

        <div class="landing-footer-column">
            <h3>Explore</h3>
            <a href="<?= htmlspecialchars(appUrl('#featured')) ?>">Featured</a>
            <a href="<?= htmlspecialchars(appUrl('#about')) ?>">About PARAM</a>
            <a href="<?= htmlspecialchars(appUrl('store')) ?>">Storefront</a>
        </div>

        <div class="landing-footer-column">
            <h3>Get started</h3>
            <a href="<?= htmlspecialchars(appUrl('register')) ?>">Create an account</a>
            <a href="<?= htmlspecialchars(appUrl('login')) ?>">Log in</a>
            <a href="<?= htmlspecialchars(appUrl('apply')) ?>">Submit an application</a>
        </div>
    </div>

    <div class="container landing-footer-bottom">
        <span>&copy; <?= date('Y') ?> PARAM. All rights reserved.</span>
        <span>For educational purposes only.</span>
    </div>
</footer>

<script src="<?= htmlspecialchars(appUrl('landing/assets/landing.js') . '?v=' . filemtime(__DIR__ . '/../assets/landing.js')) ?>"></script>
</body>
</html>
