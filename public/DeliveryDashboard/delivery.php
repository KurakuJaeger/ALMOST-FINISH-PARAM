<?php

require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/middleware/rbacmiddleware.php';

$currentUser = requireLoginOrRedirect();
requirePermission($currentUser, 'deliveries.view_assigned');

$csrfToken = csrfToken();
$currentUserName = trim(
    $currentUser['first_name'] . ' ' . $currentUser['last_name']
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta
        name="csrf-token"
        content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
    >
    <meta name="app-base-url" content="<?= htmlspecialchars(rtrim(appUrl(), '/'), ENT_QUOTES, 'UTF-8') ?>">
    <title>Param Delivery Dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('AdminDashboard/admin.css') . '?v=' . filemtime(__DIR__ . '/../AdminDashboard/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('DeliveryDashboard/delivery.css') . '?v=' . filemtime(__DIR__ . '/delivery.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?= htmlspecialchars(appUrl('delivery'), ENT_QUOTES, 'UTF-8') ?>">
            <img src="<?= htmlspecialchars(appUrl('images/logo-header.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Param logo">
        </a>

        <nav class="top-nav" aria-label="Main navigation">
            <a href="#deliveries">Delivery Queue</a>
            <a href="<?= htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8') ?>">Logout</a>
        </nav>
    </header>

    <main class="seller-layout">
        <aside class="sidebar">
            <div class="sidebar-heading">
                <h1>Delivery Dashboard</h1>
            </div>

            <div class="admin-form">
                <label for="currentUserName">Currently logged in</label>
                <input
                    id="currentUserName"
                    value="<?= htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8') ?>"
                    readonly
                >
            </div>

            <nav class="side-nav" aria-label="Delivery navigation">
                <a href="#dashboard">Dashboard</a>
                <a href="#deliveries">Delivery Queue</a>
                <a href="<?= htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8') ?>">Logout</a>
            </nav>
        </aside>

        <section class="content">
            <div
                class="notice success"
                id="notice"
                hidden
                aria-live="polite"
            ></div>

            <section id="dashboard" class="page-section active">
                <div class="section-title">
                    <p>Param Delivery</p>
                    <h2>My Assignment Summary</h2>
                </div>

                <div class="summary-grid">
                    <article class="summary-card">
                        <span>My + Available</span>
                        <strong id="total">0</strong>
                    </article>

                    <article class="summary-card">
                        <span>Active</span>
                        <strong id="active">0</strong>
                    </article>

                    <article class="summary-card">
                        <span>Delivered</span>
                        <strong id="delivered">0</strong>
                    </article>

                    <article class="summary-card warning">
                        <span>Failed</span>
                        <strong id="failed">0</strong>
                    </article>
                </div>
            </section>

            <section id="deliveries" class="page-section">
                <div class="section-title">
                    <p>Claim available work</p>
                    <h2>Delivery Queue</h2>
                </div>

                <div id="deliveryList" class="delivery-list"></div>
            </section>
        </section>
    </main>

    <footer class="site-footer">
        <img src="<?= htmlspecialchars(appUrl('images/logo-footer.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Param group logo">
        <p>
            <strong>Disclaimer:</strong>
            This website is for educational purposes only and is a requirement
            for our final project.
        </p>
    </footer>

    <script src="<?= htmlspecialchars(appUrl('DeliveryDashboard/delivery.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
