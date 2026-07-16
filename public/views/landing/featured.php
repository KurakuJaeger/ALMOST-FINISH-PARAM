<?php
if (empty($featuredProducts)) {
    $featuredProducts = [
        ['product_id' => 1, 'product_name' => 'Kids Pocketable UV Protection Parka', 'category_name' => 'Kids', 'display_price' => 1490, 'image_path' => 'landing/assets/images/prod1.avif'],
        ['product_id' => 2, 'product_name' => 'Nylon Culotte', 'category_name' => 'Unisex', 'display_price' => 1990, 'image_path' => 'landing/assets/images/prod2.avif'],
        ['product_id' => 3, 'product_name' => 'Washed Cotton Boxy T-Shirt', 'category_name' => 'Women', 'display_price' => 590, 'image_path' => 'landing/assets/images/prod3.avif'],
        ['product_id' => 4, 'product_name' => 'Washable 3D Knit Polo', 'category_name' => 'Unisex', 'display_price' => 2490, 'image_path' => 'landing/assets/images/prod4.avif'],
    ];
}
?>
<section class="section featured-section" id="featured">
    <div class="container">
        <div class="section-heading center reveal">
            <span class="eyebrow">Featured clothing</span>
            <h2>Everyday favorites, chosen for you.</h2>
            <p>Start with the PARAM pieces our customers reach for most.</p>
        </div>

        <div class="featured-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <a class="featured-card reveal" href="<?= htmlspecialchars(appUrl('store/shop.php?product=' . (int) $product['product_id'])) ?>" aria-label="Shop <?= htmlspecialchars($product['product_name']) ?>">
                    <div class="featured-image-wrap">
                        <img src="<?= htmlspecialchars(appUrl($product['image_path'])) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                        <span class="featured-tag"><?= htmlspecialchars($product['category_name']) ?></span>
                    </div>
                    <div class="featured-details">
                        <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                        <div class="featured-meta">
                            <span>Available in the storefront</span>
                            <strong>&#8369;<?= number_format((float) $product['display_price'], 2) ?></strong>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="featured-action reveal">
            <a class="button-outline" href="<?= htmlspecialchars(appUrl('store/shop.php')) ?>">Shop All Clothing</a>
        </div>
    </div>
</section>
