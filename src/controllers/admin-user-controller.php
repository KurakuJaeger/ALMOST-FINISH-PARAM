<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/role.php';
require_once __DIR__ . '/../services/audit-log-service.php';
require_once __DIR__ . '/../services/email-service.php';

class AdminUserController
{
    public static function all(): array
    {
        return User::all();
    }

    public static function roles(): array
    {
        return Role::all();
    }

    public static function create(array $input, int $administratorId): array
    {
        self::validate($input);
        $role = Role::findByName($input['role'] ?? '');
        if (!$role) {
            http_response_code(422);
            return ['error' => 'Unknown role'];
        }

        [$firstName, $lastName] = self::splitName($input['name'] ?? '');
        $userId = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $input['email'] ?? '',
            'password' => bin2hex(random_bytes(8)),
            'role_id' => $role['role_id'],
            'status' => strtolower($input['status'] ?? 'active'),
        ]);

        $plainToken = bin2hex(random_bytes(32));
        self::saveSetupToken($userId, $plainToken);
        $setupUrl = appAbsoluteUrl('setup-account') . '?token=' . urlencode($plainToken);
        [$emailSent, $emailError] = self::sendSetupEmail($input, $setupUrl);

        AuditLogService::record(
            $administratorId,
            'user.create',
            'users',
            $userId,
            sprintf(
                'Created user: %s <%s> (%s)',
                trim((string) ($input['name'] ?? '')),
                $input['email'] ?? '',
                $role['role_name']
            )
        );

        return [
            'user_id' => $userId,
            'email_sent' => $emailSent,
            'email_error' => $emailError,
            'setup_url' => $emailSent ? null : $setupUrl,
        ];
    }

    public static function update(int $userId, array $input, int $administratorId): array
    {
        self::validate($input);
        $role = Role::findByName($input['role'] ?? '');
        if (!$role) {
            http_response_code(422);
            return ['error' => 'Unknown role'];
        }

        [$firstName, $lastName] = self::splitName($input['name'] ?? '');
        User::update($userId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $input['email'] ?? '',
            'role_id' => $role['role_id'],
            'status' => strtolower($input['status'] ?? 'active'),
        ]);

        AuditLogService::record(
            $administratorId,
            'user.update',
            'users',
            $userId,
            sprintf(
                'Updated user: %s <%s> (%s, %s)',
                trim((string) ($input['name'] ?? '')),
                $input['email'] ?? '',
                $role['role_name'],
                strtolower($input['status'] ?? 'active')
            )
        );

        return ['success' => true];
    }

    public static function delete(int $userId, int $administratorId): array
    {
        if ($userId === $administratorId) {
            http_response_code(422);
            return ['error' => 'You cannot delete your own account'];
        }

        $user = User::findById($userId);
        if (!$user) {
            http_response_code(404);
            return ['error' => 'User not found'];
        }

        User::delete($userId);
        AuditLogService::record(
            $administratorId,
            'user.delete',
            'users',
            $userId,
            sprintf('Deleted user: %s %s <%s>', $user['first_name'], $user['last_name'], $user['email'])
        );

        return ['success' => true];
    }

    private static function saveSetupToken(int $userId, string $plainToken): void
    {
        $statement = getDbConnection()->prepare(
            "INSERT INTO auth_tokens (user_id, token_type, token_hash, expires_at)
             VALUES (:user_id, 'account_setup', :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plainToken),
        ]);
    }

    private static function sendSetupEmail(array $input, string $setupUrl): array
    {
        try {
            $sent = EmailService::sendAccountSetup(
                (string) ($input['email'] ?? ''),
                trim((string) ($input['name'] ?? '')),
                $setupUrl
            );
            return [$sent, $sent ? null : 'SMTP is not configured'];
        } catch (Throwable $exception) {
            return [false, $exception->getMessage()];
        }
    }

    private static function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private static function validate(array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $status = strtolower((string) ($input['status'] ?? 'active'));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            throw new InvalidArgumentException('A name and valid email address are required');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            http_response_code(422);
            throw new InvalidArgumentException('Invalid account status');
        }
    }
}
