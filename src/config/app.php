<?php

/**
 * Loads a small, dependency-free .env file for local development and shared
 * hosting. Real server environment variables always take precedence.
 */
function loadAppEnvironment(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $file = dirname(__DIR__, 2) . '/.env';
    if (!is_readable($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function appEnv(string $key, mixed $default = null): mixed
{
    loadAppEnvironment();
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function appBasePath(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $configuredUrl = (string) appEnv('APP_URL', '');
    $configuredPath = (string) appEnv('APP_BASE_PATH', '');
    if ($configuredUrl !== '') {
        $configuredPath = (string) (parse_url($configuredUrl, PHP_URL_PATH) ?: '');
    }

    if ($configuredPath === '' && $configuredUrl === '') {
        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $applicationRoot = realpath(dirname(__DIR__, 2));
        if ($documentRoot && $applicationRoot) {
            $documentRoot = str_replace('\\', '/', $documentRoot);
            $applicationRoot = str_replace('\\', '/', $applicationRoot);
            if (str_starts_with(strtolower($applicationRoot), strtolower(rtrim($documentRoot, '/') . '/'))) {
                $configuredPath = substr($applicationRoot, strlen(rtrim($documentRoot, '/')));
            }
        }
    }

    $basePath = '/' . trim(str_replace('\\', '/', $configuredPath), '/');
    if ($basePath === '/') {
        $basePath = '';
    }

    return $basePath;
}

function appUrl(string $path = ''): string
{
    $path = ltrim($path, '/');
    return appBasePath() . ($path === '' ? '/' : '/' . $path);
}

function appAbsoluteUrl(string $path = ''): string
{
    $configuredUrl = rtrim((string) appEnv('APP_URL', ''), '/');
    if ($configuredUrl !== '') {
        return $configuredUrl . ($path === '' ? '/' : '/' . ltrim($path, '/'));
    }

    $forwardedProtocol = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProtocol === 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-z0-9.-]+(?::[0-9]+)?$/i', $host)) {
        $host = 'localhost';
    }
    return ($https ? 'https' : 'http') . '://' . $host . appUrl($path);
}

function redirectTo(string $path, int $status = 302): void
{
    header('Location: ' . appUrl($path), true, $status);
    exit;
}
