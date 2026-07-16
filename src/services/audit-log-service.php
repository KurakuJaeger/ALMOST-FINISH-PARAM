<?php
require_once __DIR__ . '/../config/database.php';

class AuditLogService
{
    public static function recent(int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $statement = getDbConnection()->query(
            "SELECT al.audit_log_id, al.created_at, al.action_name, al.details,
                    COALESCE(CONCAT_WS(' ', u.first_name, u.last_name), 'System') AS actor,
                    COALESCE(r.role_name, 'System') AS actor_role
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.user_id
             LEFT JOIN roles r ON r.role_id = u.role_id
             ORDER BY al.created_at DESC
             LIMIT {$limit}"
        );

        return $statement->fetchAll();
    }

    public static function record(
        ?int $userId,
        string $action,
        string $tableName,
        ?int $recordId,
        string $details,
        ?PDO $database = null
    ): void {
        $statement = ($database ?? getDbConnection())->prepare(
            'INSERT INTO audit_logs (user_id, action_name, table_name, record_id, details)
             VALUES (:user_id, :action_name, :table_name, :record_id, :details)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action_name' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'details' => $details,
        ]);
    }
}
