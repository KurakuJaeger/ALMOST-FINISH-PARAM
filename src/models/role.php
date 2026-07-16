<?php

require_once __DIR__ . '/../config/database.php';

class Role
{
    public static function all(): array
    {
        $query = 'SELECT role_id, role_name, description
                  FROM roles
                  ORDER BY role_id';

        return getDbConnection()->query($query)->fetchAll();
    }

    public static function findByName(string $roleName): ?array
    {
        $statement = getDbConnection()->prepare(
            'SELECT *
             FROM roles
             WHERE role_name = :role_name
             LIMIT 1'
        );
        $statement->execute(['role_name' => $roleName]);
        $role = $statement->fetch();

        return $role ?: null;
    }

    public static function findById(int $roleId): ?array
    {
        $statement = getDbConnection()->prepare(
            'SELECT *
             FROM roles
             WHERE role_id = :role_id
             LIMIT 1'
        );
        $statement->execute(['role_id' => $roleId]);
        $role = $statement->fetch();

        return $role ?: null;
    }
}
