<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/audit-log-service.php';

class RefundController
{
    public static function all(): array
    {
        return getDbConnection()->query(
            "SELECT rr.refund_request_id, rr.order_id, rr.reason,
                    rr.customer_service_notes, rr.admin_notes, rr.status,
                    rr.requested_at, rr.reviewed_at, rr.executed_at,
                    o.order_status, o.total_amount,
                    CONCAT_WS(' ', customer.first_name, customer.last_name) AS customer_name,
                    customer.email AS customer_email,
                    CONCAT_WS(' ', requester.first_name, requester.last_name) AS requested_by,
                    p.payment_method, p.payment_status, p.amount AS payment_amount
             FROM refund_requests rr
             JOIN orders o ON o.order_id = rr.order_id
             JOIN users customer ON customer.user_id = o.user_id
             JOIN users requester ON requester.user_id = rr.requested_by_user_id
             LEFT JOIN payments p ON p.payment_id = (
                 SELECT payment_id FROM payments
                 WHERE order_id = rr.order_id
                 ORDER BY payment_id DESC LIMIT 1
             )
             ORDER BY
                FIELD(rr.status, 'pending', 'approved', 'refunded', 'rejected'),
                rr.requested_at DESC"
        )->fetchAll();
    }

    public static function review(int $refundRequestId, int $administratorId, array $input): array
    {
        $decision = strtolower(trim((string) ($input['decision'] ?? '')));
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            http_response_code(422);
            return ['error' => 'Choose approve or reject'];
        }

        $database = getDbConnection();
        $database->beginTransaction();
        try {
            $status = self::lockedStatus($database, $refundRequestId);
            if ($status === false) {
                $database->rollBack();
                http_response_code(404);
                return ['error' => 'Refund request not found'];
            }
            if ($status !== 'pending') {
                $database->rollBack();
                http_response_code(409);
                return ['error' => 'Only pending refund requests can be reviewed'];
            }

            $statement = $database->prepare(
                'UPDATE refund_requests
                 SET status = :status,
                     reviewed_by_user_id = :administrator_id,
                     reviewed_at = NOW(),
                     admin_notes = :notes
                 WHERE refund_request_id = :refund_request_id'
            );
            $statement->execute([
                'status' => $decision,
                'administrator_id' => $administratorId,
                'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
                'refund_request_id' => $refundRequestId,
            ]);

            AuditLogService::record(
                $administratorId,
                'refund.review',
                'refund_requests',
                $refundRequestId,
                ucfirst($decision) . ' refund request #' . $refundRequestId,
                $database
            );
            $database->commit();
            return ['success' => true, 'status' => $decision];
        } catch (Throwable $exception) {
            self::rollBack($database);
            throw $exception;
        }
    }

    public static function execute(int $refundRequestId, int $administratorId): array
    {
        $database = getDbConnection();
        $database->beginTransaction();
        try {
            $refund = self::lockedRefund($database, $refundRequestId);
            if (!$refund) {
                $database->rollBack();
                http_response_code(404);
                return ['error' => 'Refund request not found'];
            }
            if ($refund['status'] !== 'approved') {
                $database->rollBack();
                http_response_code(409);
                return ['error' => 'The refund must be approved before it is marked refunded'];
            }

            self::markRefundComplete($database, $refundRequestId, $administratorId);
            self::markOrderAndPaymentRefunded($database, (int) $refund['order_id']);
            AuditLogService::record(
                $administratorId,
                'refund.execute',
                'refund_requests',
                $refundRequestId,
                'Marked approved refund request #' . $refundRequestId
                    . ' as manually refunded for order #' . $refund['order_id'],
                $database
            );

            $database->commit();
            return ['success' => true, 'status' => 'refunded'];
        } catch (Throwable $exception) {
            self::rollBack($database);
            throw $exception;
        }
    }

    private static function lockedStatus(PDO $database, int $refundRequestId): string|false
    {
        $statement = $database->prepare(
            'SELECT status FROM refund_requests
             WHERE refund_request_id = :refund_request_id FOR UPDATE'
        );
        $statement->execute(['refund_request_id' => $refundRequestId]);
        return $statement->fetchColumn();
    }

    private static function lockedRefund(PDO $database, int $refundRequestId): array|false
    {
        $statement = $database->prepare(
            'SELECT order_id, status FROM refund_requests
             WHERE refund_request_id = :refund_request_id FOR UPDATE'
        );
        $statement->execute(['refund_request_id' => $refundRequestId]);
        return $statement->fetch();
    }

    private static function markRefundComplete(PDO $database, int $refundRequestId, int $administratorId): void
    {
        $statement = $database->prepare(
            "UPDATE refund_requests
             SET status = 'refunded', executed_by_user_id = :administrator_id, executed_at = NOW()
             WHERE refund_request_id = :refund_request_id"
        );
        $statement->execute([
            'administrator_id' => $administratorId,
            'refund_request_id' => $refundRequestId,
        ]);
    }

    private static function markOrderAndPaymentRefunded(PDO $database, int $orderId): void
    {
        $payment = $database->prepare(
            "UPDATE payments SET payment_status = 'refunded' WHERE order_id = :order_id"
        );
        $payment->execute(['order_id' => $orderId]);

        $order = $database->prepare(
            "UPDATE orders SET order_status = 'refunded' WHERE order_id = :order_id"
        );
        $order->execute(['order_id' => $orderId]);
    }

    private static function rollBack(PDO $database): void
    {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
    }
}
