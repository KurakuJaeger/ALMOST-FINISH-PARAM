<?php
require_once __DIR__ . '/app.php';

function mailConfig(): array
{
    $localConfigFile = __DIR__ . '/mail.local.php';
    if (is_file($localConfigFile)) {
        $localConfig = require $localConfigFile;
        if (is_array($localConfig)) {
            return $localConfig;
        }
    }

    return [
        'host' => appEnv('MAIL_HOST', ''),
        'port' => (int) appEnv('MAIL_PORT', 587),
        'username' => appEnv('MAIL_USERNAME', ''),
        'password' => appEnv('MAIL_PASSWORD', ''),
        'encryption' => appEnv('MAIL_ENCRYPTION', 'tls'),
        'from_address' => appEnv('MAIL_FROM_ADDRESS', 'no-reply@param.test'),
        'from_name' => appEnv('MAIL_FROM_NAME', 'Param Clothing Line'),
    ];
}
