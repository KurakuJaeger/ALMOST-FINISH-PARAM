<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/includes/db.php';

ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    redirectTo('login');
}

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $user_id, 'product_id' => $product_id]);
}

header('Location: favorites.php');
exit;
?>
