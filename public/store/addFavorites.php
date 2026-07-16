<?php
session_start();
require_once 'includes/db.php';
$pdo = getDbConnection();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'not_logged_in']);
    exit;
}

if (isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];

    $check_stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?");
    $check_stmt->execute([$user_id, $product_id]);
    $already_favorited = $check_stmt->fetch();

    if ($already_favorited) {
        $delete_stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        $delete_stmt->execute([$user_id, $product_id]);
        $is_now_favorited = false; // Tell JavaScript it is now off
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $product_id]);
        $is_now_favorited = true; // Tell JavaScript it is now on
    }

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $new_count = $count_stmt->fetchColumn() ?: 0;

    echo json_encode([
        'status' => 'success', 
        'new_count' => $new_count,
        'is_favorited' => $is_now_favorited
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'invalid_request']);
?>