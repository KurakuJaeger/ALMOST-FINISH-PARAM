<?php
require_once __DIR__ . '/../models/permission.php';

// Call after requireLoginOrJson401()/requireLoginOrRedirect() gives you $user.
function requirePermission(array $user, string $permissionKey): void
{
    if (!Permission::roleHasPermission((int) $user['role_id'], $permissionKey)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Forbidden: missing permission ' . $permissionKey]);
        exit;
    }
}
