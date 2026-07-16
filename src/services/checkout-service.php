<?php

require_once __DIR__ . '/../config/database.php';

class CheckoutException extends RuntimeException
{
}

class CheckoutService
{
    public const SHIPPING_FEE = 150.00;

    public static function customerDetails(int $userId): array
    {
        $statement = getDbConnection()->prepare(
            "SELECT u.first_name, u.last_name, u.email,
                    c.contact_number,
                    a.address_id, a.house_no, a.street, a.postal_code,
                    r.region_name, p.province_name,
                    l.locality_name, b.barangay_name
             FROM users u
             LEFT JOIN user_contacts c ON c.contact_id = (
                 SELECT contact_id FROM user_contacts
                 WHERE user_id = u.user_id
                 ORDER BY is_primary DESC, contact_id ASC LIMIT 1
             )
             LEFT JOIN user_addresses a ON a.address_id = (
                 SELECT address_id FROM user_addresses
                 WHERE user_id = u.user_id
                 ORDER BY is_default DESC, address_id ASC LIMIT 1
             )
             LEFT JOIN ph_regions r ON r.region_code = a.region_code
             LEFT JOIN ph_provinces p ON p.province_id = a.province_id
             LEFT JOIN ph_localities l ON l.locality_id = a.locality_id
             LEFT JOIN ph_barangays b ON b.barangay_id = a.barangay_id
             WHERE u.user_id = :user_id
             LIMIT 1"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: [];
    }

    public static function previewItems(int $userId, ?int $variantId = null): array
    {
        return self::loadItems(getDbConnection(), $userId, $variantId, false)['items'];
    }

    public static function totals(array $items): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['price'] * (int) $item['quantity'];
        }

        $shipping = $items ? self::SHIPPING_FEE : 0.0;
        return [
            'subtotal' => round($subtotal, 2),
            'shipping' => $shipping,
            'total' => round($subtotal + $shipping, 2),
        ];
    }

    public static function placeOrder(
        int $userId,
        array $input,
        ?int $variantId = null
    ): int {
        self::validateCheckoutInput($input);

        $database = getDbConnection();
        $database->beginTransaction();

        try {
            $loaded = self::loadItems($database, $userId, $variantId, true);
            $items = $loaded['items'];
            if (!$items) {
                throw new CheckoutException('Your cart is empty. Add an item before checking out.');
            }

            foreach ($items as $item) {
                if ((int) $item['stock_quantity'] < (int) $item['quantity']) {
                    throw new CheckoutException(
                        $item['product_name'] . ' no longer has enough stock for the requested quantity.'
                    );
                }
            }

            $totals = self::totals($items);
            $addressId = self::ownedAddressId(
                $database,
                $userId,
                (int) ($input['delivery_address_id'] ?? 0)
            );
            $snapshot = self::addressSnapshot($input);

            $order = $database->prepare(
                "INSERT INTO orders (
                    user_id, delivery_address_id, delivery_address_snapshot,
                    order_status, total_amount
                 ) VALUES (
                    :user_id, :delivery_address_id, :delivery_address_snapshot,
                    'pending', :total_amount
                 )"
            );
            $order->execute([
                'user_id' => $userId,
                'delivery_address_id' => $addressId,
                'delivery_address_snapshot' => $snapshot,
                'total_amount' => $totals['total'],
            ]);
            $orderId = (int) $database->lastInsertId();

            $orderItem = $database->prepare(
                'INSERT INTO order_items (
                    order_id, variant_id, product_name_snapshot, size_snapshot,
                    color_snapshot, price_snapshot, quantity
                 ) VALUES (
                    :order_id, :variant_id, :product_name, :size,
                    :color, :price, :quantity
                 )'
            );
            $deductStock = $database->prepare(
                'UPDATE product_variants
                 SET stock_quantity = stock_quantity - :deduct_quantity
                 WHERE variant_id = :variant_id AND stock_quantity >= :minimum_quantity'
            );

            foreach ($items as $item) {
                $orderItem->execute([
                    'order_id' => $orderId,
                    'variant_id' => $item['variant_id'],
                    'product_name' => $item['product_name'],
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
                $deductStock->execute([
                    'deduct_quantity' => $item['quantity'],
                    'minimum_quantity' => $item['quantity'],
                    'variant_id' => $item['variant_id'],
                ]);
                if ($deductStock->rowCount() !== 1) {
                    throw new CheckoutException($item['product_name'] . ' went out of stock during checkout.');
                }
            }

            $payment = $database->prepare(
                "INSERT INTO payments (order_id, payment_method, payment_status, amount)
                 VALUES (:order_id, :payment_method, 'pending', :amount)"
            );
            $payment->execute([
                'order_id' => $orderId,
                'payment_method' => $input['payment_method'],
                'amount' => $totals['total'],
            ]);

            // Every placed order enters the shared delivery queue. A delivery
            // user claims it from the dashboard instead of being hard-coded
            // here, which remains safe when more couriers are added later.
            $delivery = $database->prepare(
                "INSERT INTO deliveries (order_id, delivery_status)
                 VALUES (:order_id, 'pending')"
            );
            $delivery->execute(['order_id' => $orderId]);

            if ($loaded['cart_id'] !== null) {
                $closeCart = $database->prepare(
                    "UPDATE carts SET status = 'checked_out' WHERE cart_id = :cart_id AND user_id = :user_id"
                );
                $closeCart->execute(['cart_id' => $loaded['cart_id'], 'user_id' => $userId]);
            }

            $database->commit();
            return $orderId;
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    public static function orderForCustomer(int $orderId, int $userId): ?array
    {
        $statement = getDbConnection()->prepare(
            "SELECT o.order_id, o.order_status, o.total_amount, o.created_at,
                    o.delivery_address_snapshot,
                    p.payment_id, p.payment_method, p.payment_status,
                    p.amount AS amount_due, p.submitted_amount, p.change_amount,
                    p.reference_number, p.submitted_at,
                    (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) AS item_count
             FROM orders o
             JOIN payments p ON p.order_id = o.order_id
             WHERE o.order_id = :order_id AND o.user_id = :user_id
             ORDER BY p.payment_id DESC
             LIMIT 1"
        );
        $statement->execute(['order_id' => $orderId, 'user_id' => $userId]);
        $order = $statement->fetch();
        return $order ?: null;
    }

    public static function submitManualPayment(int $orderId, int $userId, array $input): array
    {
        $database = getDbConnection();
        $database->beginTransaction();

        try {
            $statement = $database->prepare(
                "SELECT p.payment_id, p.payment_method, p.payment_status, p.amount
                 FROM payments p
                 JOIN orders o ON o.order_id = p.order_id
                 WHERE p.order_id = :order_id AND o.user_id = :user_id
                 ORDER BY p.payment_id DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $statement->execute(['order_id' => $orderId, 'user_id' => $userId]);
            $payment = $statement->fetch();

            if (!$payment) {
                throw new CheckoutException('The payment record could not be found.');
            }
            if ($payment['payment_status'] !== 'pending') {
                $database->commit();
                return $payment;
            }

            $method = (string) $payment['payment_method'];
            $amountDue = round((float) $payment['amount'], 2);
            $reference = null;

            if ($method === 'card') {
                $lastFour = trim((string) ($input['card_last_four'] ?? ''));
                $expiry = trim((string) ($input['card_expiry'] ?? ''));
                if (!preg_match('/^\d{4}$/', $lastFour)) {
                    throw new InvalidArgumentException('Enter the final four digits of the demonstration card.');
                }
                if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
                    throw new InvalidArgumentException('Enter the demonstration expiry as MM/YY.');
                }
                $submittedAmount = $amountDue;
                $reference = 'CARD-****' . $lastFour . '-EXP-' . $expiry;
                $status = 'submitted_unverified';
            } else {
                $rawAmount = trim((string) ($input['submitted_amount'] ?? ''));
                if ($rawAmount === '' || !is_numeric($rawAmount)) {
                    throw new InvalidArgumentException('Enter a valid payment amount.');
                }
                $submittedAmount = round((float) $rawAmount, 2);
                if ($submittedAmount < $amountDue) {
                    throw new InvalidArgumentException(
                        'The entered amount is ₱' . number_format($amountDue - $submittedAmount, 2) . ' short.'
                    );
                }

                if ($method === 'gcash') {
                    $referenceInput = strtoupper(trim((string) ($input['reference_number'] ?? '')));
                    if (!preg_match('/^[A-Z0-9-]{6,30}$/', $referenceInput)) {
                        throw new InvalidArgumentException('Enter a 6–30 character GCash reference number.');
                    }
                    $reference = $referenceInput;
                    $status = 'submitted_unverified';
                } elseif ($method === 'cod') {
                    $reference = 'COD';
                    $status = 'pending_collection';
                } else {
                    throw new InvalidArgumentException('This payment method is not supported.');
                }
            }

            $change = round(max(0, $submittedAmount - $amountDue), 2);
            $update = $database->prepare(
                'UPDATE payments
                 SET submitted_amount = :submitted_amount,
                     change_amount = :change_amount,
                     reference_number = :reference_number,
                     payment_status = :payment_status,
                     submitted_at = NOW()
                 WHERE payment_id = :payment_id'
            );
            $update->execute([
                'submitted_amount' => $submittedAmount,
                'change_amount' => $change,
                'reference_number' => $reference,
                'payment_status' => $status,
                'payment_id' => $payment['payment_id'],
            ]);

            $database->commit();
            return [
                'payment_id' => (int) $payment['payment_id'],
                'payment_method' => $method,
                'payment_status' => $status,
                'amount_due' => $amountDue,
                'submitted_amount' => $submittedAmount,
                'change_amount' => $change,
                'reference_number' => $reference,
            ];
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }

    private static function loadItems(
        PDO $database,
        int $userId,
        ?int $variantId,
        bool $lock
    ): array {
        $suffix = $lock ? ' FOR UPDATE' : '';

        if ($variantId !== null && $variantId > 0) {
            $statement = $database->prepare(
                "SELECT v.variant_id, 1 AS quantity, v.size, v.color, v.price,
                        v.stock_quantity, p.product_name, p.image_path
                 FROM product_variants v
                 JOIN products p ON p.product_id = v.product_id
                 WHERE v.variant_id = :variant_id
                   AND v.status = 'active' AND p.status = 'active'
                 LIMIT 1{$suffix}"
            );
            $statement->execute(['variant_id' => $variantId]);
            $item = $statement->fetch();
            return ['cart_id' => null, 'items' => $item ? [$item] : []];
        }

        $cartQuery = "SELECT cart_id FROM carts
                      WHERE user_id = :user_id AND status = 'active'
                      ORDER BY cart_id DESC LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
        $cartStatement = $database->prepare($cartQuery);
        $cartStatement->execute(['user_id' => $userId]);
        $cartId = $cartStatement->fetchColumn();
        if (!$cartId) {
            return ['cart_id' => null, 'items' => []];
        }

        $statement = $database->prepare(
            "SELECT ci.cart_item_id, ci.quantity, v.variant_id, v.size, v.color,
                    v.price, v.stock_quantity, p.product_name, p.image_path
             FROM cart_items ci
             JOIN product_variants v ON v.variant_id = ci.variant_id
             JOIN products p ON p.product_id = v.product_id
             WHERE ci.cart_id = :cart_id
               AND v.status = 'active' AND p.status = 'active'
             ORDER BY ci.cart_item_id{$suffix}"
        );
        $statement->execute(['cart_id' => $cartId]);
        return ['cart_id' => (int) $cartId, 'items' => $statement->fetchAll()];
    }

    private static function validateCheckoutInput(array $input): void
    {
        $required = [
            'email', 'phone', 'first_name', 'last_name', 'street_address',
            'barangay', 'city', 'province', 'region', 'postal_code',
        ];
        foreach ($required as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                throw new InvalidArgumentException(
                    'Your saved address is incomplete. Update your profile before placing the order.'
                );
            }
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        $phoneDigits = preg_replace('/\D+/', '', (string) $input['phone']);
        if (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15) {
            throw new InvalidArgumentException('Enter a valid contact number.');
        }
        if (!preg_match('/^\d{4}$/', trim((string) $input['postal_code']))) {
            throw new InvalidArgumentException('Enter a valid 4-digit Philippine postal code.');
        }
        if (!in_array($input['payment_method'] ?? '', ['card', 'gcash', 'cod'], true)) {
            throw new InvalidArgumentException('Choose a valid payment method.');
        }
    }

    private static function ownedAddressId(PDO $database, int $userId, int $addressId): ?int
    {
        if ($addressId < 1) {
            return null;
        }
        $statement = $database->prepare(
            'SELECT address_id FROM user_addresses WHERE address_id = :address_id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['address_id' => $addressId, 'user_id' => $userId]);
        $ownedId = $statement->fetchColumn();
        return $ownedId ? (int) $ownedId : null;
    }

    private static function addressSnapshot(array $input): string
    {
        $name = trim($input['first_name'] . ' ' . $input['last_name']);
        $location = implode(', ', array_filter([
            trim((string) $input['street_address']),
            trim((string) $input['barangay'] ?? ''),
            trim((string) $input['city']),
            trim((string) $input['province'] ?? ''),
            trim((string) $input['region'] ?? ''),
            trim((string) $input['postal_code']),
        ]));

        return $name . ' | ' . $location
            . ' | Phone: ' . trim((string) $input['phone'])
            . ' | Email: ' . trim((string) $input['email']);
    }
}
