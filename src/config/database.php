<?php
require_once __DIR__ . '/app.php';

define('DB_HOST', (string) appEnv('DB_HOST', 'localhost'));
define('DB_PORT', (string) appEnv('DB_PORT', '3306'));
define('DB_NAME', (string) appEnv('DB_NAME', 'param_db'));
define('DB_USER', (string) appEnv('DB_USER', 'root'));
define('DB_PASS', (string) appEnv('DB_PASS', ''));

function createDbConnection(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = createDbConnection();
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Database connection failed',
                'detail' => appEnv('APP_DEBUG', 'false') === 'true' ? $e->getMessage() : null,
            ]);
            exit;
        }
    }

    return $pdo;
}

function tryGetDbConnection(): ?PDO
{
    try {
        return createDbConnection();
    } catch (PDOException) {
        return null;
    }
}
