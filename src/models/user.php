<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    public static function findByEmail(string $email): ?array
    {
        $statement = getDbConnection()->prepare(
            'SELECT users.*, roles.role_name
             FROM users
             JOIN roles ON roles.role_id = users.role_id
             WHERE users.email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function findById(int $userId): ?array
    {
        $statement = getDbConnection()->prepare(
            'SELECT users.*, roles.role_name
             FROM users
             JOIN roles ON roles.role_id = users.role_id
             WHERE users.user_id = :user_id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function all(): array
    {
        $query = 'SELECT
                    users.user_id,
                    users.first_name,
                    users.last_name,
                    users.email,
                    users.status,
                    roles.role_name
                  FROM users
                  JOIN roles ON roles.role_id = users.role_id
                  ORDER BY users.created_at DESC';

        return getDbConnection()->query($query)->fetchAll();
    }

    public static function create(array $userData): int
    {
        $database = getDbConnection();
        $statement = $database->prepare(
            'INSERT INTO users (
                first_name,
                last_name,
                email,
                password_hash,
                role_id,
                status
             ) VALUES (
                :first_name,
                :last_name,
                :email,
                :password_hash,
                :role_id,
                :status
             )'
        );
        $statement->execute([
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'password_hash' => password_hash(
                $userData['password'],
                PASSWORD_DEFAULT
            ),
            'role_id' => $userData['role_id'],
            'status' => $userData['status'] ?? 'active',
        ]);

        return (int) $database->lastInsertId();
    }

    public static function registerCustomer(array $data): array
    {
        $database = getDbConnection();
        $database->beginTransaction();
        try {
            $role = $database->query("SELECT role_id FROM roles WHERE role_name = 'Customer' LIMIT 1")->fetchColumn();
            if (!$role) {
                throw new RuntimeException('The Customer role is not configured.');
            }
            $user = $database->prepare(
                "INSERT INTO users (first_name, middle_name, last_name, suffix, email, password_hash, role_id, status)
                 VALUES (:first_name, :middle_name, :last_name, :suffix, :email, :password_hash, :role_id, 'pending_verification')"
            );
            $user->execute([
                'first_name' => $data['first_name'], 'middle_name' => $data['middle_name'] ?: null,
                'last_name' => $data['last_name'], 'suffix' => $data['suffix'] ?: null,
                'email' => $data['email'], 'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role_id' => (int) $role,
            ]);
            $userId = (int) $database->lastInsertId();
            $address = $database->prepare(
                "INSERT INTO user_addresses (
                    user_id, house_no, street, region_code, province_id,
                    locality_id, barangay_id, postal_code, is_default
                 ) VALUES (
                    :user_id, :house_no, :street, :region_code, :province_id,
                    :locality_id, :barangay_id, :postal_code, 1
                 )"
            );
            $address->execute([
                'user_id' => $userId, 'house_no' => $data['house_no'] ?: null, 'street' => $data['street'],
                'region_code' => $data['region_code'], 'province_id' => $data['province_id'],
                'locality_id' => $data['locality_id'], 'barangay_id' => $data['barangay_id'],
                'postal_code' => $data['postal_code'] ?: null,
            ]);
            $contact = $database->prepare(
                "INSERT INTO user_contacts (user_id, contact_number, contact_type, is_primary)
                 VALUES (:user_id, :contact_number, 'Mobile', 1)"
            );
            $contact->execute(['user_id' => $userId, 'contact_number' => $data['contact_number']]);

            $plainToken = bin2hex(random_bytes(32));
            $token = $database->prepare(
                "INSERT INTO auth_tokens (user_id, token_type, token_hash, expires_at)
                 VALUES (:user_id, 'email_verification', :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
            );
            $token->execute([
                'user_id' => $userId,
                'token_hash' => hash('sha256', $plainToken),
            ]);

            $database->commit();
            return ['user_id' => $userId, 'verification_token' => $plainToken];
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public static function createEmailVerificationToken(int $userId): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $insert = getDbConnection()->prepare(
            "INSERT INTO auth_tokens (user_id, token_type, token_hash, expires_at)
             VALUES (:user_id, 'email_verification', :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
        );
        $insert->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return $plainToken;
    }

    public static function verifyEmailToken(string $plainToken): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
            return null;
        }

        $database = getDbConnection();
        $database->beginTransaction();

        try {
            $statement = $database->prepare(
                "SELECT token_id, user_id
                 FROM auth_tokens
                 WHERE token_type = 'email_verification'
                   AND token_hash = :token_hash
                   AND used_at IS NULL
                   AND expires_at > NOW()
                 LIMIT 1
                 FOR UPDATE"
            );
            $statement->execute(['token_hash' => hash('sha256', $plainToken)]);
            $token = $statement->fetch();

            if (!$token) {
                $database->rollBack();
                return null;
            }

            $verify = $database->prepare(
                "UPDATE users
                 SET email_verified_at = COALESCE(email_verified_at, NOW()),
                     status = CASE WHEN status = 'pending_verification' THEN 'active' ELSE status END
                 WHERE user_id = :user_id"
            );
            $verify->execute(['user_id' => $token['user_id']]);

            $consume = $database->prepare(
                "UPDATE auth_tokens
                 SET used_at = NOW()
                 WHERE user_id = :user_id
                   AND token_type = 'email_verification'
                   AND used_at IS NULL"
            );
            $consume->execute(['user_id' => $token['user_id']]);

            $database->commit();
            return self::findById((int) $token['user_id']);
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public static function createPasswordResetToken(int $userId): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $insert = getDbConnection()->prepare(
            "INSERT INTO auth_tokens (user_id, token_type, token_hash, expires_at)
             VALUES (:user_id, 'password_reset', :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        $insert->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return $plainToken;
    }

    public static function hasValidPasswordResetToken(string $plainToken): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
            return false;
        }

        $statement = getDbConnection()->prepare(
            "SELECT 1
             FROM auth_tokens
             WHERE token_type = 'password_reset'
               AND token_hash = :token_hash
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1"
        );
        $statement->execute(['token_hash' => hash('sha256', $plainToken)]);
        return (bool) $statement->fetchColumn();
    }

    public static function resetPasswordWithToken(string $plainToken, string $password): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
            return null;
        }

        $database = getDbConnection();
        $database->beginTransaction();

        try {
            $statement = $database->prepare(
                "SELECT token_id, user_id
                 FROM auth_tokens
                 WHERE token_type = 'password_reset'
                   AND token_hash = :token_hash
                   AND used_at IS NULL
                   AND expires_at > NOW()
                 LIMIT 1
                 FOR UPDATE"
            );
            $statement->execute(['token_hash' => hash('sha256', $plainToken)]);
            $token = $statement->fetch();

            if (!$token) {
                $database->rollBack();
                return null;
            }

            $update = $database->prepare(
                'UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE user_id = :user_id'
            );
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'user_id' => $token['user_id'],
            ]);

            $consume = $database->prepare(
                "UPDATE auth_tokens
                 SET used_at = NOW()
                 WHERE user_id = :user_id
                   AND token_type = 'password_reset'
                   AND used_at IS NULL"
            );
            $consume->execute(['user_id' => $token['user_id']]);

            $database->commit();
            return self::findById((int) $token['user_id']);
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public static function update(int $userId, array $userData): bool
    {
        $statement = getDbConnection()->prepare(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 email = :email,
                 role_id = :role_id,
                 status = :status
             WHERE user_id = :user_id'
        );

        return $statement->execute([
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'role_id' => $userData['role_id'],
            'status' => $userData['status'],
            'user_id' => $userId,
        ]);
    }

    public static function delete(int $userId): bool
    {
        $statement = getDbConnection()->prepare(
            'DELETE FROM users WHERE user_id = :user_id'
        );

        return $statement->execute(['user_id' => $userId]);
    }

    public static function setPassword(int $userId, string $password): bool
    {
        $statement = getDbConnection()->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 must_change_password = 0
             WHERE user_id = :user_id'
        );

        return $statement->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'user_id' => $userId,
        ]);
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }
}
