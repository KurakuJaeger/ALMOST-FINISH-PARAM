<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/models/product.php';
require_once __DIR__ . '/includes/db.php';

ensureSessionStarted();

// --- GET USER'S FAVORITED PRODUCTS ---
$user_favorites = [];
if (isset($_SESSION['user_id'])) {
    $fav_stmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $fav_stmt->execute([$_SESSION['user_id']]);
    $user_favorites = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);
}

$cat = isset($_GET['cat']) ? max(0, (int) $_GET['cat']) : 0;
$requestedSort = (string) ($_GET['sort'] ?? 'featured');
$sort = in_array($requestedSort, ['featured', 'price_asc', 'price_desc'], true) ? $requestedSort : 'featured';
$searchTerm = trim((string) ($_GET['query'] ?? ''));
$searchTerm = substr($searchTerm, 0, 100);
$products = Product::catalog($cat, $sort, $pdo, $searchTerm);
$categories = Product::categories($pdo);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Param. | Ultimate Fashion Destination</title>
    <link rel="stylesheet" href="css/style.css?v=<?= (int) filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="css/shop.css?v=<?= (int) filemtime(__DIR__ . '/css/shop.css') ?>">
</head>

<body>

    <main class="store-container">
        <?php
        $path = '';
        include 'includes/header.php';
        ?>

        <section class="product-section">
            <h2 class="section-title">Our Products</h2>
            <?php if ($searchTerm !== ''): ?>
                <p class="search-results-label">
                    <?= count($products) ?> result<?= count($products) === 1 ? '' : 's' ?> for
                    <strong>&ldquo;<?= htmlspecialchars($searchTerm) ?>&rdquo;</strong>
                </p>
            <?php endif; ?>

            <div class="shop-controls">
                <div class="filter-options">
                    <span class="control-label">Filter:</span>
                    <?php
                    $active_cat = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
                    ?>

                    <a href="?<?= htmlspecialchars(http_build_query(['cat' => 0, 'sort' => $sort, 'query' => $searchTerm])) ?>" class="filter-pill <?= $active_cat === 0 ? 'active' : '' ?>">All</a>
                    <?php foreach ($categories as $category): ?>
                        <a href="?<?= htmlspecialchars(http_build_query(['cat' => (int) $category['category_id'], 'sort' => $sort, 'query' => $searchTerm])) ?>"
                            class="filter-pill <?= $active_cat === (int) $category['category_id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($category['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sort-options">
                    <span class="control-label">Sort:</span>
                    <select class="sort-dropdown" id="product-sort">
                        <option value="featured">Featured</option>
                        <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <div class="product-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-search-results">
                        <h3>No matching products</h3>
                        <p>Try another product name or category.</p>
                        <a href="shop.php">View all products</a>
                    </div>
                <?php endif; ?>
                <?php foreach ($products as $item): ?>
                    <?php
                    $is_faved = in_array($item['product_id'], $user_favorites);
                    ?>

                    <div class="product-card">
                        <div class="product-image-container">
                            <img src="<?= htmlspecialchars(appUrl($item['image_path'])) ?>"
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">

                            <button class="btn-favorite-card <?php echo $is_faved ? 'is-favorited' : ''; ?>"
                                title="Add to Favorites" data-id="<?php echo $item['product_id']; ?>">
                                <img src="images/<?php echo $is_faved ? 'love.png' : 'heart.png'; ?>" alt="Favorite"
                                    class="heart-icon">
                            </button>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <p class="product-price">₱<?php echo number_format($item['display_price'], 2); ?></p>
                            <button class="btn-cart" data-id="<?php echo $item['product_id']; ?>">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </section>

    </main>

    <?php
    $path = '';
    include 'includes/footer.php';
    ?>

    <div id="cartModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <div id="modalBody">
            </div>
        </div>
    </div>

    <script>
        document.getElementById('product-sort')?.addEventListener('change', function () {
            const params = new URLSearchParams(window.location.search);
            params.set('cat', <?= json_encode((string) $cat) ?>);
            params.set('sort', this.value);
            window.location.href = 'shop.php?' + params.toString();
        });

        document.querySelectorAll('.btn-favorite-card').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.getAttribute('data-id');
                const btn = this;

                fetch('addFavorites.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + productId
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {

                            const heartIcon = btn.querySelector('.heart-icon');

                            if (data.is_favorited) {
                                btn.classList.add('is-favorited');
                                if (heartIcon) heartIcon.src = 'images/love.png';
                            } else {
                                btn.classList.remove('is-favorited');
                                if (heartIcon) heartIcon.src = 'images/heart.png';
                            }

                            const favLink = document.querySelector('a[title="Favorites"]');
                            if (favLink) {
                                let badge = favLink.querySelector('.nav-badge');

                                if (data.new_count > 0) {
                                    if (!badge) {
                                        badge = document.createElement('span');
                                        badge.className = 'nav-badge';
                                        favLink.appendChild(badge);
                                    }
                                    badge.innerText = data.new_count;
                                } else {
                                    if (badge) {
                                        badge.remove();
                                    }
                                }

                                // Header icon remains the black heart for consistent navbar styling.
                            }

                            if (heartIcon) {
                                heartIcon.style.transform = 'scale(1.3)';
                                setTimeout(() => { heartIcon.style.transform = 'scale(1)'; }, 200);
                            }

                        } else if (data.status === 'error' && data.message === 'not_logged_in') {
                            window.location.href = <?= json_encode(appUrl('login')) ?>;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('cartModal');
            const closeBtn = document.querySelector('.close-btn');

            document.querySelectorAll('.btn-cart').forEach(button => {
                button.addEventListener('click', function () {
                    const productId = this.getAttribute('data-id');

                    fetch('getProductDetails.php?id=' + productId)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('modalBody').innerHTML = html;
                            modal.style.display = 'block';
                            initializeProductForm();
                        });
                });
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            }

            window.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });

            function initializeProductForm() {
                const form = document.getElementById('product-order-form');
                if (!form) return;

                let variants = [];
                try {
                    variants = JSON.parse(form.dataset.variants || '[]');
                } catch (error) {
                    console.error('Invalid product variant data.', error);
                }

                const colorInputs = Array.from(form.querySelectorAll('input[name="color"]'));
                const sizeInputs = Array.from(form.querySelectorAll('input[name="size"]'));
                const colorDisplay = form.querySelector('[data-display-color]');
                const sizeDisplay = form.querySelector('[data-display-size]');

                function syncSizes() {
                    const selectedColor = form.querySelector('input[name="color"]:checked')?.value || '';
                    const availableSizes = new Set(
                        variants.filter(variant => variant.color === selectedColor).map(variant => variant.size)
                    );

                    sizeInputs.forEach(input => {
                        const available = availableSizes.has(input.value);
                        input.disabled = !available;
                        input.closest('.size-box-label')?.classList.toggle('is-unavailable', !available);
                    });

                    let selectedSize = form.querySelector('input[name="size"]:checked:not(:disabled)');
                    if (!selectedSize) {
                        selectedSize = sizeInputs.find(input => !input.disabled) || null;
                        if (selectedSize) selectedSize.checked = true;
                    }

                    if (colorDisplay) colorDisplay.textContent = selectedColor.toUpperCase();
                    if (sizeDisplay) {
                        sizeDisplay.textContent = selectedSize
                            ? selectedSize.nextElementSibling.textContent.toUpperCase()
                            : 'UNAVAILABLE';
                    }
                }

                colorInputs.forEach(input => input.addEventListener('change', syncSizes));
                sizeInputs.forEach(input => input.addEventListener('change', function () {
                    if (sizeDisplay) sizeDisplay.textContent = this.nextElementSibling.textContent.toUpperCase();
                }));
                syncSizes();
            }
        });
    </script>
