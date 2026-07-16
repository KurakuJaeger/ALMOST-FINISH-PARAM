<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/models/product.php';
require_once __DIR__ . '/includes/db.php';

$customer = requireLoginOrRedirect();

if (!isset($_GET['id'])) {
    echo "<p>Error: No product selected.</p>";
    exit;
}

$id = (int)$_GET['id'];

$variants = Product::variants($id, $pdo);

if (!$variants) {
    echo "<p>Sorry, product details are currently unavailable.</p>";
    exit;
}

$product = $variants[0]; 

function displaySizeWithUnit($size) {
    if (is_numeric($size)) {
        if ($size > 100) {
            return $size . " cm"; 
        } else {
            return $size . " inches"; 
        }
    }
    return $size;
}

$colors = array_unique(array_column($variants, 'color'));
$sizes = array_unique(array_column($variants, 'size'));
$defaultColor = (string) $product['color'];
$defaultSize = (string) $product['size'];
$variantOptions = array_map(
    static fn (array $variant): array => [
        'color' => (string) $variant['color'],
        'size' => (string) $variant['size'],
    ],
    $variants
);

$colorHexMap = [
    'Black' => '#000000', 'White' => '#FFFFFF', 'Off White' => '#f8f8f2',
    'Navy' => '#1a2a40', 'Blue' => '#4A90E2', 'Light Blue' => '#a1c6ea',
    'Red' => '#d32f2f', 'Pink' => '#f48fb1', 'Beige' => '#f5f5dc',
    'Brown' => '#795548', 'Dark Brown' => '#4e342e', 'Green' => '#4caf50',
    'Dark Green' => '#2e7d32', 'Olive' => '#808000', 'Olive Green' => '#556b2f',
    'Gray' => '#9e9e9e', 'Light Gray' => '#e0e0e0', 'Dark Gray' => '#616161',
    'Purple' => '#9c27b0', 'Indigo' => '#3f51b5', 'Natural' => '#eaddcf',
    'Khaki' => '#c3b091', 'Striped' => 'repeating-linear-gradient(45deg, #fff, #fff 5px, #333 5px, #333 10px)'
];
?>

<div style="text-align: center;">
    <img src="<?= htmlspecialchars(appUrl($product['image_path'])) ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 150px; border-radius: 8px;">
    
    <h3 style="color: var(--maroon); margin-top: 15px; margin-bottom: 5px;">
        <?php echo htmlspecialchars($product['product_name']); ?>
    </h3>
    
    <!-- PRICE HERE -->
    <p style="font-size: 1.2rem; font-weight: bold; color: var(--maroon); margin-top: 0; margin-bottom: 20px;">
        ₱<?php echo number_format($product['price'], 2); ?>
    </p>
</div>

<form action="<?= htmlspecialchars(appUrl('store/addToCart.php')) ?>" method="POST" id="product-order-form" data-variants="<?= htmlspecialchars(json_encode($variantOptions), ENT_QUOTES, 'UTF-8') ?>" style="display: flex; flex-direction: column;">
    <input type="hidden" name="product_id" value="<?php echo $id; ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
    
    <div class="variant-grid-container">
        <!-- LEFT COLUMN: COLORS -->
        <div class="variant-column">
            <p class="variant-label">Color: <span data-display-color class="selected-text"><?= htmlspecialchars(strtoupper($defaultColor)) ?></span></p>
            <div class="custom-radio-group">
                <?php foreach ($colors as $color): 
                    $cssColor = $colorHexMap[$color] ?? '#ccc'; 
                ?>
                    <label class="color-swatch-label">
                        <input type="radio" name="color" value="<?php echo htmlspecialchars($color); ?>" <?= $color === $defaultColor ? 'checked' : '' ?> required>
                        <span class="color-swatch" style="background: <?php echo $cssColor; ?>;" title="<?php echo htmlspecialchars($color); ?>"></span>
                        <span class="color-name"><?php echo htmlspecialchars($color); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: SIZES -->
        <div class="variant-column">
            <p class="variant-label">Size: <span data-display-size class="selected-text"><?= htmlspecialchars(strtoupper(displaySizeWithUnit($defaultSize))) ?></span></p>
            <div class="custom-radio-group">
                <?php foreach ($sizes as $size): ?>
                    <label class="size-box-label">
                        <input type="radio" name="size" value="<?php echo htmlspecialchars($size); ?>" <?= $size === $defaultSize ? 'checked' : '' ?> required>
                        <span class="size-box"><?php echo htmlspecialchars(displaySizeWithUnit($size)); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div style="display: flex; gap: 15px; margin-top: 25px;">
        <!-- The Add to Cart button -->
        <button type="submit" name="action" value="cart" class="btn-cart-submit btn-outline" style="flex: 1;">Add to Cart</button>
        
        <!-- The Buy Now button -->
        <button type="submit" name="action" value="checkout" class="btn-cart-submit" style="flex: 1;">Buy Now</button>
    </div>
</form>
