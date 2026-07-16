<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/audit-log-service.php';

class CustomerServiceController
{
    public static function summary(): array
    {
        $database = getDbConnection();

        return [
            'open' => self::countConcernsByStatus($database, 'open'),
            'in_progress' => self::countConcernsByStatus($database, 'in_progress'),
            'resolved' => self::countConcernsByStatus($database, 'resolved'),
            'pending_refunds' => (int) $database
                ->query("SELECT COUNT(*) FROM refund_requests WHERE status = 'pending'")
                ->fetchColumn(),
        ];
    }

    public static function concerns(): array
    {
        $query = "SELECT
                    concerns.concern_id,
                    concerns.order_id,
                    CONCAT_WS(' ', customers.first_name, customers.middle_name, customers.last_name, customers.suffix) AS customer_name,
                    customers.email,
                    (
                        SELECT contact_number
                        FROM user_contacts
                        WHERE user_id = customers.user_id
                        ORDER BY is_primary DESC, contact_id
                        LIMIT 1
                    ) AS phone,
                    concerns.subject,
                    concerns.message,
                    concerns.response,
                    concerns.status,
                    concerns.created_at,
                    orders.order_status,
                    orders.total_amount
                  FROM support_concerns concerns
                  JOIN users customers ON customers.user_id = concerns.customer_id
                  LEFT JOIN orders ON orders.order_id = concerns.order_id
                  ORDER BY
                    FIELD(concerns.status, 'open', 'in_progress', 'resolved', 'closed'),
                    concerns.created_at DESC";

        return getDbConnection()->query($query)->fetchAll();
    }

    public static function updateConcern(int $concernId, int $staffUserId, array $input): array
    {
        $status = strtolower((string) ($input['status'] ?? ''));
        $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

        if (!in_array($status, $allowedStatuses, true)) {
            http_response_code(422);
            return ['error' => 'Invalid concern status'];
        }

        $statement = getDbConnection()->prepare(
            'UPDATE support_concerns
             SET response = :response,
                 status = :status,
                 assigned_to_user_id = :staff_user_id
             WHERE concern_id = :concern_id'
        );
        $statement->execute([
            'response' => trim((string) ($input['response'] ?? '')) ?: null,
            'status' => $status,
            'staff_user_id' => $staffUserId,
            'concern_id' => $concernId,
        ]);

        if ($statement->rowCount() === 0) {
            http_response_code(404);
            return ['error' => 'Concern not found'];
        }

        AuditLogService::record(
            $staffUserId,
            'support.reply',
            'support_concerns',
            $concernId,
            'Updated support concern #' . $concernId . ' to ' . str_replace('_', ' ', $status)
        );

        return ['success' => true];
    }

    public static function requestRefund(
        int $concernId,
        int $staffUserId,
        string $reason,
        string $notes
    ): array {
        $database = getDbConnection();
        $orderStatement = $database->prepare(
            'SELECT order_id FROM support_concerns WHERE concern_id = :concern_id'
        );
        $orderStatement->execute(['concern_id' => $concernId]);
        $orderId = $orderStatement->fetchColumn();

        if (!$orderId) {
            http_response_code(422);
            return ['error' => 'This concern is not linked to an order'];
        }

        $existingRequest = $database->prepare(
            "SELECT 1
             FROM refund_requests
             WHERE order_id = :order_id
               AND status IN ('pending', 'approved')
             LIMIT 1"
        );
        $existingRequest->execute(['order_id' => $orderId]);

        if ($existingRequest->fetchColumn()) {
            http_response_code(409);
            return ['error' => 'An active refund request already exists for this order'];
        }

        $insertStatement = $database->prepare(
            'INSERT INTO refund_requests (
                order_id,
                requested_by_user_id,
                reason,
                customer_service_notes
             ) VALUES (
                :order_id,
                :staff_user_id,
                :reason,
                :notes
             )'
        );
        $insertStatement->execute([
            'order_id' => $orderId,
            'staff_user_id' => $staffUserId,
            'reason' => trim($reason),
            'notes' => trim($notes) ?: null,
        ]);

        $refundRequestId = (int) $database->lastInsertId();
        AuditLogService::record(
            $staffUserId,
            'refund.request',
            'refund_requests',
            $refundRequestId,
            'Requested refund review for order #' . $orderId
        );

        return ['refund_request_id' => $refundRequestId];
    }

    public static function refundsRequestedBy(int $staffUserId): array
    {
        $statement = getDbConnection()->prepare(
            'SELECT
                refund_request_id,
                order_id,
                reason,
                customer_service_notes,
                admin_notes,
                status,
                requested_at
             FROM refund_requests
             WHERE requested_by_user_id = :staff_user_id
             ORDER BY requested_at DESC'
        );
        $statement->execute(['staff_user_id' => $staffUserId]);

        return $statement->fetchAll();
    }

    private static function countConcernsByStatus(PDO $database, string $status): int
    {
        $statement = $database->prepare(
            'SELECT COUNT(*) FROM support_concerns WHERE status = :status'
        );
        $statement->execute(['status' => $status]);

        return (int) $statement->fetchColumn();
    }

}
