<?php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/product.php';

$landingSections = __DIR__ . '/views/landing';
$featuredProducts = [];
$publicDatabase = tryGetDbConnection();
if ($publicDatabase) {
    try {
        $featuredProducts = Product::featured(4, $publicDatabase);
    } catch (PDOException) {
        $featuredProducts = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PARAM | Designed for everyday life</title>
    <meta name="description" content="PARAM creates comfortable, timeless clothing for women, men, and kids.">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/landing.css') . '?v=' . filemtime(__DIR__ . '/landing/assets/landing.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/landing/includes/header.php'; ?>

    <main>
        <?php require $landingSections . '/hero.php'; ?>
        <?php require $landingSections . '/featured.php'; ?>
        <?php require $landingSections . '/about.php'; ?>
        <?php require $landingSections . '/services.php'; ?>
        <?php require $landingSections . '/benefits.php'; ?>
        <?php require $landingSections . '/cta.php'; ?>
    </main>

    <?php require __DIR__ . '/landing/includes/footer.php'; ?>
