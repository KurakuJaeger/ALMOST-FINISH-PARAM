<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/audit-log-service.php';

class DeliveryController
{
    public static function assignedTo(int $deliveryUserId): array
    {
        $query = "SELECT
                    deliveries.delivery_id,
                    deliveries.order_id,
                    deliveries.assigned_to_user_id,
                    CONCAT_WS(' ', customers.first_name, customers.middle_name, customers.last_name, customers.suffix) AS customer_name,
                    orders.delivery_address_snapshot,
                    CASE
                        WHEN contacts.contact_number IS NULL THEN NULL
                        WHEN CHAR_LENGTH(contacts.contact_number) <= 4 THEN contacts.contact_number
                        ELSE CONCAT(
                            REPEAT('*', CHAR_LENGTH(contacts.contact_number) - 4),
                            RIGHT(contacts.contact_number, 4)
                        )
                    END AS masked_phone_number,
                    deliveries.delivery_status,
                    deliveries.delivery_notes,
                    deliveries.proof_image_path,
                    deliveries.assigned_at,
                    deliveries.delivered_at
                  FROM deliveries
                  JOIN orders ON orders.order_id = deliveries.order_id
                  JOIN users customers ON customers.user_id = orders.user_id
                  LEFT JOIN user_contacts contacts ON contacts.contact_id = (
                      SELECT contact_id
                      FROM user_contacts
                      WHERE user_id = customers.user_id
                      ORDER BY is_primary DESC, contact_id ASC
                      LIMIT 1
                  )
                  WHERE deliveries.assigned_to_user_id = :delivery_user_id
                     OR deliveries.assigned_to_user_id IS NULL
                  ORDER BY
                    deliveries.assigned_to_user_id IS NOT NULL DESC,
                    FIELD(
                        deliveries.delivery_status,
                        'pending',
                        'assigned',
                        'picked_up',
                        'in_transit',
                        'delivered',
                        'failed'
                    ),
                    deliveries.created_at DESC";

        $statement = getDbConnection()->prepare($query);
        $statement->execute(['delivery_user_id' => $deliveryUserId]);

        return $statement->fetchAll();
    }

    public static function summary(int $deliveryUserId): array
    {
        $statement = getDbConnection()->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(delivery_status = 'delivered') AS delivered,
                SUM(delivery_status IN ('pending', 'assigned', 'picked_up', 'in_transit')) AS active,
                SUM(delivery_status = 'failed') AS failed
             FROM deliveries
             WHERE assigned_to_user_id = :delivery_user_id
                OR assigned_to_user_id IS NULL"
        );
        $statement->execute(['delivery_user_id' => $deliveryUserId]);

        $summary = $statement->fetch() ?: [
            'total' => 0,
            'delivered' => 0,
            'active' => 0,
            'failed' => 0,
        ];

        return array_map('intval', $summary);
    }

    public static function claim(int $deliveryId, int $deliveryUserId): array
    {
        $database = getDbConnection();
        $database->beginTransaction();

        try {
            $statement = $database->prepare(
                "UPDATE deliveries
                 SET assigned_to_user_id = :delivery_user_id,
                     assigned_at = COALESCE(assigned_at, NOW()),
                     delivery_status = CASE
                         WHEN delivery_status = 'pending' THEN 'assigned'
                         ELSE delivery_status
                     END
                 WHERE delivery_id = :delivery_id
                   AND assigned_to_user_id IS NULL"
            );
            $statement->execute([
                'delivery_id' => $deliveryId,
                'delivery_user_id' => $deliveryUserId,
            ]);

            if ($statement->rowCount() !== 1) {
                if (self::isAssignedToUser($deliveryId, $deliveryUserId, $database)) {
                    $database->commit();
                    return ['success' => true, 'already_claimed' => true];
                }

                $database->rollBack();
                http_response_code(409);
                return ['error' => 'This delivery was already claimed by another delivery user.'];
            }

            $syncOrder = $database->prepare(
                'UPDATE orders
                 SET order_status = :order_status
                 WHERE order_id = (
                     SELECT order_id FROM deliveries WHERE delivery_id = :delivery_id
                 )'
            );
            $syncOrder->execute([
                'order_status' => 'processing',
                'delivery_id' => $deliveryId,
            ]);

            self::recordAudit($deliveryUserId, $deliveryId, 'assigned', $database, 'delivery.claim');
            $database->commit();
            return ['success' => true];
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public static function update(
        int $deliveryId,
        int $deliveryUserId,
        array $input
    ): array {
        $allowedStatuses = [
            'pending',
            'assigned',
            'picked_up',
            'in_transit',
            'delivered',
            'failed',
        ];
        $status = strtolower((string) ($input['status'] ?? ''));

        if (!in_array($status, $allowedStatuses, true)) {
            http_response_code(422);
            return ['error' => 'Invalid delivery status'];
        }

        $database = getDbConnection();
        $database->beginTransaction();
        try {
            $statement = $database->prepare(
                "UPDATE deliveries
                 SET delivery_status = :status,
                     delivery_notes = :notes,
                     proof_image_path = :proof_image_path,
                     delivered_at = CASE
                         WHEN :delivered_status = 'delivered'
                         THEN COALESCE(delivered_at, NOW())
                         ELSE NULL
                     END
                 WHERE delivery_id = :delivery_id
                   AND assigned_to_user_id = :delivery_user_id"
            );
            $statement->execute([
                'status' => $status,
                'notes' => trim((string) ($input['notes'] ?? '')) ?: null,
                'proof_image_path' => trim((string) ($input['proof'] ?? '')) ?: null,
                'delivered_status' => $status,
                'delivery_id' => $deliveryId,
                'delivery_user_id' => $deliveryUserId,
            ]);

            if ($statement->rowCount() === 0
                && !self::isAssignedToUser($deliveryId, $deliveryUserId, $database)
            ) {
                $database->rollBack();
                http_response_code(404);
                return ['error' => 'Assigned delivery not found'];
            }

            $orderStatus = match ($status) {
                'picked_up', 'in_transit' => 'shipped',
                'delivered' => 'delivered',
                'failed' => 'delivery_failed',
                default => 'processing',
            };
            $syncOrder = $database->prepare(
                'UPDATE orders
                 SET order_status = :order_status
                 WHERE order_id = (
                     SELECT order_id FROM deliveries WHERE delivery_id = :delivery_id
                 )'
            );
            $syncOrder->execute([
                'order_status' => $orderStatus,
                'delivery_id' => $deliveryId,
            ]);

            self::recordAudit($deliveryUserId, $deliveryId, $status, $database);
            $database->commit();

            return ['success' => true];
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    private static function isAssignedToUser(
        int $deliveryId,
        int $deliveryUserId,
        ?PDO $database = null
    ): bool
    {
        $statement = ($database ?? getDbConnection())->prepare(
            'SELECT 1
             FROM deliveries
             WHERE delivery_id = :delivery_id
               AND assigned_to_user_id = :delivery_user_id'
        );
        $statement->execute([
            'delivery_id' => $deliveryId,
            'delivery_user_id' => $deliveryUserId,
        ]);

        return (bool) $statement->fetchColumn();
    }

    private static function recordAudit(
        int $deliveryUserId,
        int $deliveryId,
        string $status,
        ?PDO $database = null,
        string $action = 'delivery.update'
    ): void {
        AuditLogService::record(
            $deliveryUserId,
            $action,
            'deliveries',
            $deliveryId,
            'Updated assigned delivery #' . $deliveryId
                . ' to ' . str_replace('_', ' ', $status),
            $database
        );
    }
}
