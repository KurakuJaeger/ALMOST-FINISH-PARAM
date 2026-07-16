<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/includes/db.php';

ensureSessionStarted();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'], $_POST['quantity'])) {
    $user_id = $_SESSION['user_id'];
    $cart_item_id = (int)$_POST['cart_item_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity < 1) {
        echo json_encode(['status' => 'error', 'message' => 'invalid_quantity']);
        exit;
    }

    $update_stmt = $pdo->prepare("
        UPDATE cart_items 
        SET quantity = :quantity 
        WHERE cart_item_id = :cart_item_id AND cart_id IN (
            SELECT cart_id FROM carts WHERE user_id = :user_id AND status = 'active'
        )
    ");
    $update_stmt->execute([
        'quantity' => $quantity,
        'cart_item_id' => $cart_item_id,
        'user_id' => $user_id
    ]);

    $totals_stmt = $pdo->prepare("
        SELECT SUM(ci.quantity * v.price) as subtotal, SUM(ci.quantity) as total_items 
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.cart_id
        JOIN product_variants v ON ci.variant_id = v.variant_id
        WHERE c.user_id = :user_id AND c.status = 'active'
    ");
    $totals_stmt->execute(['user_id' => $user_id]);
    $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'subtotal' => $totals['subtotal'] ? (float)$totals['subtotal'] : 0,
        'total_items' => $totals['total_items'] ? (int)$totals['total_items'] : 0
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'invalid_request']);
?>
