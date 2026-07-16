<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/middleware/rbacmiddleware.php';
$currentUser = requireLoginOrRedirect();
$csrfToken = csrfToken();
requirePermission($currentUser, 'users.manage');
$adminAssetUrl = static function (string $file): string {
    $version = filemtime(__DIR__ . '/' . $file) ?: time();
    return appUrl('AdminDashboard/' . $file) . '?v=' . $version;
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="app-base-url" content="<?= htmlspecialchars(rtrim(appUrl(), '/'), ENT_QUOTES, 'UTF-8') ?>">
    <title>Param Seller Part</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($adminAssetUrl('admin.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?= htmlspecialchars(appUrl('admin'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Return to the Param seller dashboard">
            <img src="<?= htmlspecialchars(appUrl('images/logo-header.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Param logo">
        </a>

        <nav class="top-nav" aria-label="Main navigation">
            <a href="#users">Admin Users</a>
            <a href="#stocks">Stocks</a>
            <a href="#applications">Applications</a>
            <a href="#refunds">Refunds</a>
            <a href="#reports">Reports</a>
            <a href="#audit">Audit Log</a>
            <a href="<?= htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8') ?>">Logout</a>
        </nav>
    </header>

    <main class="seller-layout">
        <aside class="sidebar" aria-label="Seller navigation">
            <div class="sidebar-heading">
                <h1>Admin Dashboard</h1>
            </div>

            <div class="admin-form">
                <label for="admin_name">Currently logged in</label>
                <div class="inline-fields">
                    <input id="admin_name" value="<?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>" readonly>
                </div>
            </div>

            <nav class="side-nav">
                <a href="#dashboard">Dashboard</a>
                <a href="#users">Admin Users</a>
                <a href="#stocks">Stocks and Prices</a>
                <a href="#applications">Staff Applications</a>
                <a href="#refunds">Refund Reviews</a>
                <a href="#reports">Inventory Report</a>
                <a href="#audit">Audit Log</a>
                <a href="<?= htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8') ?>">Logout</a>
            </nav>
        </aside>

        <section class="content">
            <div class="notice success" id="notice" hidden aria-live="polite"></div>

            <section id="dashboard" class="page-section">
                <div class="section-title">
                    <p>Param Clothing Line</p>
                    <h2>Seller Front Page</h2>
                </div>

                <div class="summary-grid">
                    <article class="summary-card">
                        <span>Total Products</span>
                        <strong id="totalProducts">-</strong>
                    </article>
                    <article class="summary-card">
                        <span>Size / Color Variants</span>
                        <strong id="totalVariants">-</strong>
                    </article>
                    <article class="summary-card">
                        <span>Total Units in Stock</span>
                        <strong id="totalStock">-</strong>
                    </article>
                    <article class="summary-card warning">
                        <span>Low-stock Variants</span>
                        <strong id="lowStock">-</strong>
                    </article>
                    <article class="summary-card">
                        <span>Inventory Value</span>
                        <strong id="inventoryValue">PHP 0.00</strong>
                    </article>
                </div>
            </section>

            <section id="users" class="page-section">
                <div class="section-title">
                    <p>Admin Roles</p>
                    <h2>Add or Modify Users</h2>
                </div>

                <form class="form-panel" id="addUserForm">
                    <div class="form-grid">
                        <label>
                            Complete name
                            <input name="name" required>
                        </label>
                        <label>
                            Email address
                            <input type="email" name="email" required>
                        </label>
                        <label>
                            Admin role
                            <select name="role" id="newUserRole">
                                <!-- admin-users.js loads these options from the roles API. -->
                            </select>
                        </label>
                        <label>
                            Status
                            <select name="status">
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit">Add Admin User</button>
                </form>

                <div class="edit-list" id="userList">
                    <div class="edit-list-head" aria-hidden="true">
                        <span>Name</span>
                        <span>Email</span>
                        <span>Role</span>
                        <span>Status</span>
                        <span>Action</span>
                    </div>

                    <!-- admin-users.js loads these rows from the users API. -->
                </div>
            </section>

            <section id="stocks" class="page-section">
                <div class="section-title">
                    <p>Store Products</p>
                    <h2>Products and Size / Color Variants</h2>
                </div>
                <p class="section-note">
                    A product appears once in the storefront, but it can have several
                    inventory rows—one for every available size and color combination.
                </p>

                <form class="form-panel" id="addStockForm">
                    <div class="form-grid">
                        <label>
                            Product name
                            <input name="name" required>
                        </label>
                        <label>
                            Category
                            <select name="category">
                                <option>Women</option>
                                <option>Men</option>
                                <option>Kids</option>
                                <option>Unisex</option>
                                <option>Accessories</option>
                            </select>
                        </label>
                        <label>
                            Initial size
                            <input name="size" placeholder="Example: M or One Size" maxlength="30" required>
                        </label>
                        <label>
                            Initial color
                            <select name="color" required>
                                <option value="">Choose a color</option>
                                <option>Black</option><option>White</option><option>Off White</option>
                                <option>Navy</option><option>Blue</option><option>Light Blue</option>
                                <option>Red</option><option>Pink</option><option>Beige</option>
                                <option>Brown</option><option>Dark Brown</option><option>Green</option>
                                <option>Dark Green</option><option>Olive</option><option>Olive Green</option>
                                <option>Gray</option><option>Light Gray</option><option>Dark Gray</option>
                                <option>Purple</option><option>Natural</option><option>Khaki</option>
                                <option>Striped</option>
                            </select>
                        </label>
                        <label>
                            Price per variant
                            <input type="number" name="price" min="1" step="0.01" required>
                        </label>
                        <label>
                            Units for this variant
                            <input type="number" name="stock" min="0" step="1" required>
                        </label>
                        <div class="product-image-field">
                            <label for="newProductImage">Product image</label>
                            <div class="image-upload-control">
                                <div id="newProductPlaceholder" class="product-image-placeholder" aria-hidden="true"><span>IMG</span></div>
                                <img id="newProductPreview" class="product-image-preview" alt="Selected product preview" hidden>
                                <div class="image-upload-copy">
                                    <strong id="newProductImageName">Choose a clear product photo</strong>
                                    <span>JPEG, PNG, or WebP · maximum 5 MB</span>
                                </div>
                                <label class="image-picker-button" for="newProductImage">Browse image</label>
                                <input id="newProductImage" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" id="addStockButton">Add Product</button>
                </form>

                <div class="edit-list inventory-list">
                    <div class="edit-list-head stock-list-head" aria-hidden="true">
                        <span>Product</span>
                        <span>Variant</span>
                        <span>Category</span>
                        <span>Price</span>
                        <span>Units</span>
                        <span>Action</span>
                    </div>
                    <div id="stockList"></div>
                </div>
            </section>

            <section id="applications" class="page-section">
                <div class="section-title"><p>Staff Access</p><h2>Review Applications</h2></div>
                <div class="table-wrap"><table>
                    <thead><tr><th>Applicant</th><th>Contact</th><th>Requested Role</th><th>Details</th><th>Status / Action</th></tr></thead>
                    <tbody id="applicationBody"></tbody>
                </table></div>
            </section>

            <section id="refunds" class="page-section">
                <div class="section-title">
                    <p>Manual Payment Workflow</p>
                    <h2>Refund Reviews</h2>
                </div>
                <p class="section-note">
                    Approval records the decision only. Use Mark Refunded after the
                    refund has been handled manually; no payment API is connected.
                </p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Request</th>
                                <th>Customer</th>
                                <th>Order / Payment</th>
                                <th>Reason</th>
                                <th>Status / Action</th>
                            </tr>
                        </thead>
                        <tbody id="refundBody"></tbody>
                    </table>
                </div>
            </section>

            <section id="reports" class="page-section">
                <div class="section-title">
                    <p>Reports</p>
                    <h2>Remaining Inventory</h2>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Category</th>
                                <th>Remaining Items</th>
                                <th>Price</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody id="reportBody">
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="audit" class="page-section">
                <div class="section-title">
                    <p>Reports</p>
                    <h2>Audit Log</h2>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date and Time</th>
                                <th>Logged-in User</th>
                                <th>Role</th>
                                <th>Activity</th>
                            </tr>
                        </thead>
                        <tbody id="auditLog">
                            <!-- admin-operations.js loads these rows from the audit API. -->
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>

    <footer class="site-footer">
        <img src="<?= htmlspecialchars(appUrl('images/logo-footer.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Param group logo">
        <p><strong>Disclaimer:</strong> This website is for educational purposes only and is a requirement for our final project.</p>
    </footer>

    <!-- Shared helper, focused features, then the small page initializer. -->
    <script src="<?= htmlspecialchars($adminAssetUrl('admin.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($adminAssetUrl('admin-users.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($adminAssetUrl('admin-inventory.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($adminAssetUrl('admin-operations.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars($adminAssetUrl('admin-init.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
