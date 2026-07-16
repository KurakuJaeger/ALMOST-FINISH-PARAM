<?php
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../config/app.php';

// Starts the session (if needed) and exposes helpers used by pages/api.php.

function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrfToken(): string
{
    ensureSessionStarted();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function requireValidCsrfToken(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $provided)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

function currentUser(): ?array
{
    ensureSessionStarted();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return User::findById((int) $_SESSION['user_id']);
}

// For page requests (admin.php): send them to login.php if not authenticated.
function requireLoginOrRedirect(?string $loginUrl = null): array
{
    $user = currentUser();
    if (!$user || $user['status'] !== 'active') {
        header('Location: ' . ($loginUrl ?? appUrl('login')));
        exit;
    }
    return $user;
}

// For api.php JSON endpoints: respond 401 instead of redirecting.
function requireLoginOrJson401(): array
{
    $user = currentUser();
    if (!$user || $user['status'] !== 'active') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    return $user;
}

function loginUser(array $user): void
{
    ensureSessionStarted();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role_id'] = $user['role_id'];
}

function logoutUser(): void
{
    ensureSessionStarted();
    $_SESSION = [];
    session_destroy();
}
