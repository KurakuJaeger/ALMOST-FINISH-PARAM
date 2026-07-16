<?php
require_once dirname(__DIR__, 3) . '/src/config/app.php';
$isApplyPage = basename($_SERVER['PHP_SELF'] ?? '') === 'apply.php';
?>
<header class="landing-header">
    <div class="container landing-navbar">
        <a class="landing-brand" href="<?= htmlspecialchars(appUrl()) ?>" aria-label="PARAM home">
            <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
        </a>

        <button class="landing-menu-button" type="button" aria-label="Toggle navigation" aria-controls="landing-navigation" aria-expanded="false">&#9776;</button>

        <div class="landing-menu-panel" id="landing-navigation">
            <nav class="landing-nav-links" aria-label="Landing page navigation">
                <a href="<?= htmlspecialchars(appUrl('#featured')) ?>">Featured</a>
                <a href="<?= htmlspecialchars(appUrl('#about')) ?>">About</a>
                <a href="<?= htmlspecialchars(appUrl('#team')) ?>">Team</a>
                <a href="<?= htmlspecialchars(appUrl('#services')) ?>">Why PARAM</a>
                <a href="<?= htmlspecialchars(appUrl('apply')) ?>"<?= $isApplyPage ? ' class="is-active" aria-current="page"' : '' ?>>Apply</a>
            </nav>

            <div class="landing-nav-actions">
                <a class="landing-login-link" href="<?= htmlspecialchars(appUrl('login')) ?>">Log in</a>
                <a class="button-outline compact-button" href="<?= htmlspecialchars(appUrl('register')) ?>">Register</a>
                <a class="button compact-button" href="<?= htmlspecialchars(appUrl('store')) ?>">Shop</a>
            </div>
        </div>
    </div>
</header>
