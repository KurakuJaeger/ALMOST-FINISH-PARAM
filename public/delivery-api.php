<?php

require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/middleware/rbacmiddleware.php';
require_once __DIR__ . '/../src/controllers/delivery-controller.php';

header('Content-Type: application/json');

set_exception_handler(function (): void {
    http_response_code(500);
    echo json_encode(['error' => 'Server request failed']);
});

$currentUser = requireLoginOrJson401();
$resource = $_GET['resource'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'];
$deliveryId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
    requireValidCsrfToken();
}

if ($resource === 'summary' && $requestMethod === 'GET') {
    requirePermission($currentUser, 'deliveries.view_assigned');
    echo json_encode(DeliveryController::summary((int) $currentUser['user_id']));
    exit;
}

if ($resource === 'deliveries' && $requestMethod === 'GET') {
    requirePermission($currentUser, 'deliveries.view_assigned');
    requirePermission($currentUser, 'deliveries.view_limited_customer_info');
    echo json_encode(DeliveryController::assignedTo((int) $currentUser['user_id']));
    exit;
}

if ($resource === 'deliveries' && $requestMethod === 'PUT' && $deliveryId) {
    requirePermission($currentUser, 'deliveries.update_status');
    $requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
    echo json_encode(DeliveryController::update(
        $deliveryId,
        (int) $currentUser['user_id'],
        $requestBody
    ));
    exit;
}

if ($resource === 'deliveries' && $requestMethod === 'POST' && $deliveryId) {
    requirePermission($currentUser, 'deliveries.update_status');
    echo json_encode(DeliveryController::claim(
        $deliveryId,
        (int) $currentUser['user_id']
    ));
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Unknown resource']);
