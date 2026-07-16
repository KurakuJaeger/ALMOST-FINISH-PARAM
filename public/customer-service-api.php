<?php

require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/middleware/rbacmiddleware.php';
require_once __DIR__ . '/../src/controllers/customer-service-controller.php';

header('Content-Type: application/json');

set_exception_handler(function (): void {
    http_response_code(500);
    echo json_encode(['error' => 'Server request failed']);
});

$currentUser = requireLoginOrJson401();
$resource = $_GET['resource'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'];
$recordId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$requestBody = json_decode(file_get_contents('php://input'), true) ?? [];

if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
    requireValidCsrfToken();
}

if ($resource === 'summary' && $requestMethod === 'GET') {
    requirePermission($currentUser, 'support.view');
    echo json_encode(CustomerServiceController::summary());
    exit;
}

if ($resource === 'concerns' && $requestMethod === 'GET') {
    requirePermission($currentUser, 'support.view');
    requirePermission($currentUser, 'orders.view_support');
    requirePermission($currentUser, 'customers.view_support_info');
    echo json_encode(CustomerServiceController::concerns());
    exit;
}

if ($resource === 'concerns' && $requestMethod === 'PUT' && $recordId) {
    requirePermission($currentUser, 'support.reply');
    echo json_encode(CustomerServiceController::updateConcern(
        $recordId,
        (int) $currentUser['user_id'],
        $requestBody
    ));
    exit;
}

if ($resource === 'refunds' && $requestMethod === 'GET') {
    requirePermission($currentUser, 'refunds.request');
    echo json_encode(CustomerServiceController::refundsRequestedBy(
        (int) $currentUser['user_id']
    ));
    exit;
}

if ($resource === 'refunds' && $requestMethod === 'POST' && $recordId) {
    requirePermission($currentUser, 'refunds.request');
    echo json_encode(CustomerServiceController::requestRefund(
        $recordId,
        (int) $currentUser['user_id'],
        (string) ($requestBody['reason'] ?? ''),
        (string) ($requestBody['notes'] ?? '')
    ));
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Unknown resource']);
