<?php

require_once __DIR__ . '/../config/database.php';

class Permission
{
    public static function forRole(int $roleId): array
    {
        $statement = getDbConnection()->prepare(
            'SELECT permissions.permission_key
             FROM role_permissions
             JOIN permissions
               ON permissions.permission_id = role_permissions.permission_id
             WHERE role_permissions.role_id = :role_id'
        );
        $statement->execute(['role_id' => $roleId]);

        return array_column($statement->fetchAll(), 'permission_key');
    }

    public static function roleHasPermission(
        int $roleId,
        string $permissionKey
    ): bool {
        $statement = getDbConnection()->prepare(
            'SELECT 1
             FROM role_permissions
             JOIN permissions
               ON permissions.permission_id = role_permissions.permission_id
             WHERE role_permissions.role_id = :role_id
               AND permissions.permission_key = :permission_key
             LIMIT 1'
        );
        $statement->execute([
            'role_id' => $roleId,
            'permission_key' => $permissionKey,
        ]);

        return (bool) $statement->fetchColumn();
    }
}
