<?php

require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/services/audit-log-service.php';
require_once __DIR__ . '/includes/db.php';

$currentUser = requireLoginOrRedirect();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('store/ContactUs.php');
}
if ($currentUser['role_name'] !== 'Customer') {
    http_response_code(403);
    exit('Only customer accounts can submit storefront support concerns.');
}
if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Your form session expired. Refresh the page and try again.');
}

$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$orderId = (int) ($_POST['order_id'] ?? 0);

if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
    $_SESSION['support_notice'] = [
        'type' => 'error',
        'message' => 'Use a subject between 3 and 150 characters.',
    ];
    redirectTo('store/ContactUs.php#my-support-requests');
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
    $_SESSION['support_notice'] = [
        'type' => 'error',
        'message' => 'Describe your concern using 10 to 3,000 characters.',
    ];
    redirectTo('store/ContactUs.php#my-support-requests');
}

$ownedOrderId = null;
if ($orderId > 0) {
    $ownedOrder = $pdo->prepare(
        'SELECT order_id FROM orders
         WHERE order_id = :order_id AND user_id = :user_id
         LIMIT 1'
    );
    $ownedOrder->execute([
        'order_id' => $orderId,
        'user_id' => $currentUser['user_id'],
    ]);
    $ownedOrderId = $ownedOrder->fetchColumn();
    if (!$ownedOrderId) {
        http_response_code(403);
        exit('That order does not belong to your customer account.');
    }
}

$pdo->beginTransaction();
try {
    $insert = $pdo->prepare(
        "INSERT INTO support_concerns (
            customer_id, order_id, subject, message, status
         ) VALUES (
            :customer_id, :order_id, :subject, :message, 'open'
         )"
    );
    $insert->execute([
        'customer_id' => $currentUser['user_id'],
        'order_id' => $ownedOrderId ?: null,
        'subject' => $subject,
        'message' => $message,
    ]);
    $concernId = (int) $pdo->lastInsertId();

    AuditLogService::record(
        (int) $currentUser['user_id'],
        'support.submit',
        'support_concerns',
        $concernId,
        'Submitted customer support concern #' . $concernId,
        $pdo
    );

    $pdo->commit();
    $_SESSION['support_notice'] = [
        'type' => 'success',
        'message' => 'Concern #' . $concernId . ' was sent to Customer Service.',
    ];
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

redirectTo('store/ContactUs.php#my-support-requests');
