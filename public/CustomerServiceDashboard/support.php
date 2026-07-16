<?php

require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/middleware/rbacmiddleware.php';

$currentUser = requireLoginOrRedirect();
requirePermission($currentUser, 'support.view');

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
    <title>Param Customer Service</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('AdminDashboard/admin.css') . '?v=' . filemtime(__DIR__ . '/../AdminDashboard/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('CustomerServiceDashboard/support.css') . '?v=' . filemtime(__DIR__ . '/support.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?= htmlspecialchars(appUrl('support'), ENT_QUOTES, 'UTF-8') ?>">
            <img src="<?= htmlspecialchars(appUrl('images/logo-header.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Param logo">
        </a>

        <nav class="top-nav" aria-label="Main navigation">
            <a href="#concerns">Concerns</a>
            <a href="#refunds">Refund Requests</a>
            <a href="<?= htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8') ?>">Logout</a>
        </nav>
    </header>

    <main class="seller-layout">
        <aside class="sidebar">
            <div class="sidebar-heading">
                <h1>Customer Service</h1>
            </div>

            <div class="admin-form">
                <label for="currentUserName">Currently logged in</label>
                <input
                    id="currentUserName"
                    value="<?= htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8') ?>"
                    readonly
                >
            </div>

            <nav class="side-nav" aria-label="Customer Service navigation">
                <a href="#dashboard">Dashboard</a>
                <a href="#concerns">Support Concerns</a>
                <a href="#refunds">Refund Requests</a>
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
                    <p>Param Support</p>
                    <h2>Concern Summary</h2>
                </div>

                <div class="summary-grid">
                    <article class="summary-card">
                        <span>Open</span>
                        <strong id="open">0</strong>
                    </article>

                    <article class="summary-card">
                        <span>In Progress</span>
                        <strong id="in_progress">0</strong>
                    </article>

                    <article class="summary-card">
                        <span>Resolved</span>
                        <strong id="resolved">0</strong>
                    </article>

                    <article class="summary-card warning">
                        <span>Pending Refunds</span>
                        <strong id="pending_refunds">0</strong>
                    </article>
                </div>
            </section>

            <section id="concerns" class="page-section">
                <div class="section-title">
                    <p>Customer Assistance</p>
                    <h2>Support Concerns</h2>
                </div>

                <div id="concernList" class="support-list"></div>
            </section>

            <section id="refunds" class="page-section">
                <div class="section-title">
                    <p>Escalations</p>
                    <h2>My Refund Requests</h2>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Request</th>
                                <th>Order</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Requested</th>
                            </tr>
                        </thead>
                        <tbody id="refundBody"></tbody>
                    </table>
                </div>
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

    <script src="<?= htmlspecialchars(appUrl('CustomerServiceDashboard/support.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
