<?php
// Single JSON entry point for the Admin Dashboard front-end.
// Example calls the JS makes:
//   GET  /api.php?resource=summary
//   GET  /api.php?resource=users
//   POST /api.php?resource=users            body: {name,email,role,status}
//   PUT  /api.php?resource=users&id=3        body: {name,email,role,status}
//   DELETE /api.php?resource=users&id=3
//   GET/POST/DELETE  /api.php?resource=stock(&id=)
//   GET  /api.php?resource=report
//   GET/POST/DELETE  /api.php?resource=audit(&id=)

require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/middleware/rbacmiddleware.php';
require_once __DIR__ . '/../src/controllers/admin-user-controller.php';
require_once __DIR__ . '/../src/controllers/refund-controller.php';
require_once __DIR__ . '/../src/controllers/product-controller.php';
require_once __DIR__ . '/../src/controllers/application-controller.php';
require_once __DIR__ . '/../src/services/audit-log-service.php';
require_once __DIR__ . '/../src/services/product-image-service.php';

header('Content-Type: application/json');
set_exception_handler(function (Throwable $exception): void {
    if (http_response_code() < 400) {
        http_response_code($exception instanceof InvalidArgumentException ? 422 : 500);
    }
    echo json_encode(['error' => $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Server request failed']);
});

$user     = requireLoginOrJson401();
$resource = $_GET['resource'] ?? '';
$method   = $_SERVER['REQUEST_METHOD'];
$id       = isset($_GET['id']) ? (int) $_GET['id'] : null;
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$input = str_contains($contentType, 'multipart/form-data')
    ? $_POST
    : (json_decode(file_get_contents('php://input'), true) ?? []);

if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
    requireValidCsrfToken();
}

switch ($resource) {
    case 'summary':
        requirePermission($user, 'reports.inventory.view');
        echo json_encode(ProductController::summary());
        break;

    case 'users':
        if ($method === 'GET') {
            requirePermission($user, 'users.manage');
            echo json_encode(AdminUserController::all());
        } elseif ($method === 'POST') {
            requirePermission($user, 'users.manage');
            echo json_encode(AdminUserController::create($input, (int) $user['user_id']));
        } elseif ($method === 'PUT' && $id) {
            requirePermission($user, 'users.manage');
            echo json_encode(AdminUserController::update($id, $input, (int) $user['user_id']));
        } elseif ($method === 'DELETE' && $id) {
            requirePermission($user, 'users.manage');
            echo json_encode(AdminUserController::delete($id, (int) $user['user_id']));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
        }
        break;

    case 'roles':
        requirePermission($user, 'users.manage');
        echo json_encode(AdminUserController::roles());
        break;

    case 'stock':
        if ($method === 'GET') {
            requirePermission($user, 'inventory.manage');
            echo json_encode(ProductController::listStock());
        } elseif ($method === 'POST') {
            requirePermission($user, 'inventory.manage');
            $imagePath = null;
            try {
                $imagePath = ProductImageService::storeUploaded($_FILES['image'] ?? []);
                $input['image_path'] = $imagePath;
                $result = ProductController::createStockItem($input, (int) $user['user_id']);
                if (isset($result['error'])) {
                    ProductImageService::deleteManaged($imagePath);
                }
                echo json_encode($result);
            } catch (Throwable $exception) {
                ProductImageService::deleteManaged($imagePath);
                throw $exception;
            }
        } elseif ($method === 'DELETE' && $id) {
            requirePermission($user, 'inventory.manage');
            echo json_encode(ProductController::deleteStockItem($id, (int) $user['user_id']));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
        }
        break;

    case 'variants':
        if ($method === 'PUT' && $id) {
            requirePermission($user, 'inventory.manage');
            requirePermission($user, 'prices.manage');
            echo json_encode(ProductController::updateVariant($id, $input, (int) $user['user_id']));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
        }
        break;

    case 'applications':
        requirePermission($user, 'applications.review');
        if ($method === 'GET') echo json_encode(ApplicationController::all());
        elseif ($method === 'PUT' && $id) {
            $reviewStatus = strtolower((string) ($input['status'] ?? ''));
            echo json_encode(ApplicationController::review(
                $id,
                $reviewStatus,
                (int) $user['user_id']
            ));
        }
        else { http_response_code(400); echo json_encode(['error' => 'Bad request']); }
        break;

    case 'refunds':
        if ($method === 'GET') {
            requirePermission($user, 'refunds.review');
            echo json_encode(RefundController::all());
        } elseif ($method === 'PUT' && $id) {
            requirePermission($user, 'refunds.review');
            echo json_encode(RefundController::review(
                $id,
                (int) $user['user_id'],
                $input
            ));
        } elseif ($method === 'POST' && $id) {
            requirePermission($user, 'refunds.execute');
            echo json_encode(RefundController::execute(
                $id,
                (int) $user['user_id']
            ));
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
        }
        break;

    case 'report':
        requirePermission($user, 'reports.inventory.view');
        echo json_encode(ProductController::inventoryReport());
        break;

    case 'audit':
        if ($method === 'GET') {
            requirePermission($user, 'reports.audit_logs.view');
            echo json_encode(AuditLogService::recent());
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Bad request']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown resource']);
}
