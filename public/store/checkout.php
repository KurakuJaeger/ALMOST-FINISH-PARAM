<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/services/checkout-service.php';

$customer = requireLoginOrRedirect();
ensureSessionStarted();

$userId = (int) $customer['user_id'];
$variantId = (int) ($_GET['variant_id'] ?? $_POST['variant_id'] ?? 0);
$variantId = $variantId > 0 ? $variantId : null;
$details = CheckoutService::customerDetails($userId);
$error = '';

$values = [
    'email' => (string) ($details['email'] ?? ''),
    'phone' => (string) ($details['contact_number'] ?? ''),
    'first_name' => (string) ($details['first_name'] ?? ''),
    'last_name' => (string) ($details['last_name'] ?? ''),
    'street_address' => trim(implode(' ', array_filter([
        $details['house_no'] ?? '',
        $details['street'] ?? '',
    ]))),
    'barangay' => (string) ($details['barangay_name'] ?? ''),
    'city' => (string) ($details['locality_name'] ?? ''),
    'province' => (string) ($details['province_name'] ?? ''),
    'region' => (string) ($details['region_name'] ?? ''),
    'postal_code' => (string) ($details['postal_code'] ?? ''),
    'payment_method' => 'cod',
    'delivery_address_id' => (int) ($details['address_id'] ?? 0),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $field) {
        if (array_key_exists($field, $_POST)) {
            $values[$field] = is_int($values[$field])
                ? (int) $_POST[$field]
                : trim((string) $_POST[$field]);
        }
    }

    $nonce = (string) ($_POST['checkout_nonce'] ?? '');
    $completedOrders = $_SESSION['completed_checkouts'] ?? [];
    if ($nonce !== '' && isset($completedOrders[$nonce])) {
        redirectTo('store/payment.php?order=' . (int) $completedOrders[$nonce], 303);
    }

    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your checkout session expired. Please refresh the page and try again.';
    } elseif ($nonce === '' || !hash_equals((string) ($_SESSION['checkout_nonce'] ?? ''), $nonce)) {
        $error = 'This checkout form has expired. Please refresh the page before placing your order.';
    } else {
        try {
            $orderId = CheckoutService::placeOrder($userId, $values, $variantId);
            $_SESSION['completed_checkouts'][$nonce] = $orderId;
            if (count($_SESSION['completed_checkouts']) > 10) {
                $_SESSION['completed_checkouts'] = array_slice($_SESSION['completed_checkouts'], -10, null, true);
            }
            unset($_SESSION['checkout_nonce']);
            redirectTo('store/payment.php?order=' . $orderId, 303);
        } catch (InvalidArgumentException|CheckoutException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable) {
            $error = 'We could not place your order right now. No payment was recorded. Please try again.';
        }
    }
}

if (empty($_SESSION['checkout_nonce'])) {
    $_SESSION['checkout_nonce'] = bin2hex(random_bytes(24));
}

$items = CheckoutService::previewItems($userId, $variantId);
$totals = CheckoutService::totals($items);
$hasStockProblem = false;
foreach ($items as $item) {
    if ((int) $item['stock_quantity'] < (int) $item['quantity']) {
        $hasStockProblem = true;
        break;
    }
}

function checkoutValue(array $values, string $key): string
{
    return htmlspecialchars((string) ($values[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Param. | Secure Checkout</title>
    <link rel="stylesheet" href="css/style.css?v=<?= (int) filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <main class="store-container">
        <?php $path = ''; include 'includes/header.php'; ?>

        <section class="checkout-section">
            <div class="checkout-heading">
                <span class="checkout-eyebrow">Secure checkout</span>
                <h1 class="section-title">Review and place your order</h1>
                <div class="checkout-steps" aria-label="Checkout progress">
                    <span class="complete">Cart</span><span class="active">Checkout</span><span>Confirmation</span>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="checkout-alert" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="checkout-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="checkout_nonce" value="<?= htmlspecialchars($_SESSION['checkout_nonce']) ?>">
                <input type="hidden" name="variant_id" value="<?= (int) ($variantId ?? 0) ?>">
                <input type="hidden" name="delivery_address_id" value="<?= (int) $values['delivery_address_id'] ?>">
                <input type="hidden" name="barangay" value="<?= checkoutValue($values, 'barangay') ?>">
                <input type="hidden" name="province" value="<?= checkoutValue($values, 'province') ?>">
                <input type="hidden" name="region" value="<?= checkoutValue($values, 'region') ?>">

                <div class="checkout-layout">
                    <div class="checkout-form-area">
                        <section class="form-section">
                            <div class="form-section-heading"><span>1</span><div><h2 class="form-title">Contact information</h2><p>We&rsquo;ll use this for order and delivery updates.</p></div></div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email address</label>
                                    <input type="email" id="email" name="email" class="form-input" value="<?= checkoutValue($values, 'email') ?>" autocomplete="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Contact number</label>
                                    <input type="tel" id="phone" name="phone" class="form-input" value="<?= checkoutValue($values, 'phone') ?>" autocomplete="tel" placeholder="09123456789" required>
                                </div>
                            </div>
                        </section>

                        <section class="form-section">
                            <div class="form-section-heading"><span>2</span><div><h2 class="form-title">Delivery address</h2><p>Your normalized location comes from your saved profile. Update it from My Profile if you need a different city or barangay.</p></div></div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First name</label>
                                    <input type="text" id="first_name" name="first_name" class="form-input" value="<?= checkoutValue($values, 'first_name') ?>" autocomplete="given-name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last name</label>
                                    <input type="text" id="last_name" name="last_name" class="form-input" value="<?= checkoutValue($values, 'last_name') ?>" autocomplete="family-name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="street_address">House number and street</label>
                                <input type="text" id="street_address" name="street_address" class="form-input" value="<?= checkoutValue($values, 'street_address') ?>" autocomplete="street-address" required>
                            </div>
                            <?php if ($values['barangay'] || $values['province'] || $values['region']): ?>
                                <p class="saved-location">
                                    <strong>Saved location:</strong>
                                    <?= htmlspecialchars(implode(', ', array_filter([$values['barangay'], $values['city'], $values['province'], $values['region']]))) ?>
                                    · <a href="Profile.php">Edit in My Profile</a>
                                </p>
                            <?php endif; ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City / municipality</label>
                                    <input type="text" id="city" name="city" class="form-input" value="<?= checkoutValue($values, 'city') ?>" autocomplete="address-level2" readonly required>
                                </div>
                                <div class="form-group form-group-small">
                                    <label for="postal_code">Postal code</label>
                                    <input type="text" id="postal_code" name="postal_code" class="form-input" value="<?= checkoutValue($values, 'postal_code') ?>" inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="postal-code" required>
                                </div>
                            </div>
                        </section>

                        <section class="form-section payment-section-form">
                            <div class="form-section-heading"><span>3</span><div><h2 class="form-title">Payment method</h2><p>No live payment API is connected yet. Your selection will be recorded as pending.</p></div></div>
                            <?php
                            $paymentOptions = [
                                'cod' => ['Cash on Delivery', 'Pay when your parcel arrives.'],
                                'gcash' => ['GCash', 'Manual payment confirmation will be required.'],
                                'card' => ['Credit / Debit Card', 'Card processing will be connected in a later phase.'],
                            ];
                            foreach ($paymentOptions as $method => [$label, $description]):
                            ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="<?= $method ?>" <?= $values['payment_method'] === $method ? 'checked' : '' ?> required>
                                    <span class="payment-radio" aria-hidden="true"></span>
                                    <span><strong><?= htmlspecialchars($label) ?></strong><small><?= htmlspecialchars($description) ?></small></span>
                                </label>
                            <?php endforeach; ?>
                        </section>
                    </div>

                    <aside class="checkout-summary">
                        <h2 class="summary-title">Order summary</h2>
                        <div class="summary-items">
                            <?php if (!$items): ?>
                                <div class="empty-checkout">
                                    <strong>Your cart is empty.</strong>
                                    <a href="shop.php">Continue shopping</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <article class="summary-item">
                                        <img src="<?= htmlspecialchars(appUrl($item['image_path'])) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="summary-item-img">
                                        <div class="summary-item-details">
                                            <p class="summary-item-name"><?= htmlspecialchars($item['product_name']) ?></p>
                                            <p class="summary-item-meta"><?= htmlspecialchars($item['color']) ?> · <?= htmlspecialchars($item['size']) ?> · Qty <?= (int) $item['quantity'] ?></p>
                                            <?php if ((int) $item['stock_quantity'] < (int) $item['quantity']): ?><p class="stock-warning">Insufficient stock</p><?php endif; ?>
                                        </div>
                                        <p class="summary-item-price">₱<?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($totals['subtotal'], 2) ?></span></div>
                        <div class="summary-row"><span>Standard shipping</span><span>₱<?= number_format($totals['shipping'], 2) ?></span></div>
                        <div class="summary-total"><span>Total</span><span>₱<?= number_format($totals['total'], 2) ?></span></div>
                        <button type="submit" class="btn-place-order" <?= !$items || $hasStockProblem ? 'disabled' : '' ?>>Place order securely</button>
                        <p class="checkout-assurance">Inventory and pricing are checked again before the order is created.</p>
                    </aside>
                </div>
            </form>
        </section>
    </main>

    <?php $path = ''; include 'includes/footer.php'; ?>
    <script>
        document.getElementById('checkout-form')?.addEventListener('submit', function () {
            const button = this.querySelector('.btn-place-order');
            if (button) {
                button.disabled = true;
                button.textContent = 'Placing order...';
            }
        });
    </script>
</body>
</html>
