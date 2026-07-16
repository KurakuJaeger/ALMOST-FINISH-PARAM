<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/includes/db.php';

ensureSessionStarted();

if (!isset($_SESSION['user_id'])) {
    redirectTo('login');
}

if (isset($_GET['id'])) {
    $cart_item_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("

    DELETE FROM cart_items 
        WHERE cart_item_id = :cart_item_id 
        AND cart_id IN (
            SELECT cart_id FROM carts
            WHERE user_id = :user_id AND status = 'active'
        )
    ");
    
    $stmt->execute([
        'cart_item_id' => $cart_item_id,
        'user_id' => $user_id
    ]);
}

header('Location: cart.php');
exit;
?>
