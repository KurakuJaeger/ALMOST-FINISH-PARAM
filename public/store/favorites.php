<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/includes/db.php';

$customer = requireLoginOrRedirect();

$stmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.image_path, MIN(v.price) as display_price 
    FROM products p
    JOIN favorites f ON p.product_id = f.product_id
    JOIN product_variants v ON p.product_id = v.product_id
    WHERE f.user_id = :user_id
    GROUP BY p.product_id
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Param. | Your Favorites</title>
    <link rel="stylesheet" href="css/style.css?v=<?= (int) filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="css/favorites.css">
</head>

<body>

    <main class="store-container">
        <?php
        $path = '';
        include 'includes/header.php';
        ?>

       <section class="product-section">
                <h2 class="section-title">Your Favorites</h2>

                <div class="product-grid">
                    <?php if (empty($favorites)): ?>
                        <p>You haven't added any favorites yet.</p>
                    <?php else: ?>
                        <?php foreach ($favorites as $item): ?>
                            <div class="product-card">
                                <img src="<?= htmlspecialchars(appUrl($item['image_path'])) ?>"
                                    alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <p class="product-price">₱<?php echo number_format($item['display_price'], 2); ?></p>
                                    <div class="favorite-actions">
                                        <button class="btn-cart" data-id="<?php echo $item['product_id']; ?>">Add to
                                            Cart</button>

                                        <a href="removeFavorites.php?id=<?php echo $item['product_id']; ?>"
                                            class="btn-remove">Remove</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

    </main>

<?php 
$path = ''; 
include 'includes/footer.php'; 
?>
