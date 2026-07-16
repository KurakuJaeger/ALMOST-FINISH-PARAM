<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/services/checkout-service.php';

$customer = requireLoginOrRedirect();
$userId = (int) $customer['user_id'];
$orderId = (int) ($_GET['order'] ?? $_POST['order_id'] ?? 0);
$error = '';

$order = $orderId > 0 ? CheckoutService::orderForCustomer($orderId, $userId) : null;
if (!$order) {
    http_response_code(404);
}

if ($order && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your payment form expired. Refresh the page and try again.';
    } else {
        try {
            CheckoutService::submitManualPayment($orderId, $userId, $_POST);
            redirectTo('store/payment.php?order=' . $orderId . '&complete=1', 303);
        } catch (InvalidArgumentException|CheckoutException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable) {
            $error = 'We could not record the payment details. No verified payment was created.';
        }
    }
    $order = CheckoutService::orderForCustomer($orderId, $userId);
}

$methodLabels = [
    'cod' => 'Cash on Delivery',
    'gcash' => 'GCash',
    'card' => 'Credit / Debit Card',
];
$submitted = $order && $order['payment_status'] !== 'pending';
$electronic = $order && in_array($order['payment_method'], ['gcash', 'card'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Param. | Manual Payment</title>
    <link rel="stylesheet" href="css/style.css?v=<?= (int) filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="css/payment.css">
</head>
<body>
    <main class="store-container">
        <?php $path = ''; include 'includes/header.php'; ?>

        <section class="payment-section">
            <div class="success-card payment-card-wide">
                <?php if (!$order): ?>
                    <div class="success-icon error-icon" aria-hidden="true">!</div>
                    <h1 class="success-title">Order not found</h1>
                    <p class="success-message">This payment page is unavailable or does not belong to your account.</p>
                    <a href="shop.php" class="btn-continue">Return to the store</a>

                <?php elseif ($submitted): ?>
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <span class="confirmation-eyebrow">Order recorded</span>
                    <h1 class="success-title"><?= $electronic ? 'Payment details submitted.' : 'Cash details saved.' ?></h1>
                    <p class="success-message">
                        <?php if ($electronic): ?>
                            This is a manual classroom submission. PARAM has recorded the details but cannot verify that the electronic payment is legitimate without a payment API.
                        <?php else: ?>
                            Prepare the entered cash amount for the courier. Collection remains pending until delivery.
                        <?php endif; ?>
                    </p>

                    <div class="order-details-box">
                        <div class="detail-row"><span class="detail-label">Order number</span><span class="detail-value">#PRM-<?= str_pad((string) $order['order_id'], 6, '0', STR_PAD_LEFT) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Payment method</span><span class="detail-value"><?= htmlspecialchars($methodLabels[$order['payment_method']] ?? $order['payment_method']) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Amount due</span><span class="detail-value">₱<?= number_format((float) $order['amount_due'], 2) ?></span></div>
                        <div class="detail-row"><span class="detail-label">Amount entered</span><span class="detail-value">₱<?= number_format((float) $order['submitted_amount'], 2) ?></span></div>
                        <div class="detail-row"><span class="detail-label"><?= $order['payment_method'] === 'cod' ? 'Expected change' : 'Excess amount' ?></span><span class="detail-value order-total">₱<?= number_format((float) $order['change_amount'], 2) ?></span></div>
                        <?php if ($order['payment_method'] !== 'cod'): ?><div class="detail-row"><span class="detail-label">Reference</span><span class="detail-value"><?= htmlspecialchars((string) $order['reference_number']) ?></span></div><?php endif; ?>
                        <div class="detail-row"><span class="detail-label">Payment status</span><span class="detail-value status-pill"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_status']))) ?></span></div>
                    </div>
                    <p class="email-notice">Keep the order number for delivery updates or customer-service concerns.</p>
                    <a href="shop.php" class="btn-continue">Continue shopping</a>

                <?php else: ?>
                    <span class="confirmation-eyebrow">Manual payment exercise</span>
                    <h1 class="success-title"><?= htmlspecialchars($methodLabels[$order['payment_method']] ?? 'Payment') ?></h1>
                    <p class="success-message">Enter simple payment details below. The system performs arithmetic and format checks only—no external payment API or legitimacy verification is used.</p>

                    <?php if ($error !== ''): ?><div class="payment-alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <div class="payment-math" data-amount-due="<?= htmlspecialchars((string) $order['amount_due']) ?>">
                        <div><span>Order total</span><strong>₱<?= number_format((float) $order['amount_due'], 2) ?></strong></div>
                        <div><span>Amount entered</span><strong data-entered-display>₱0.00</strong></div>
                        <div class="math-result"><span data-result-label>Remaining balance</span><strong data-result-display>₱<?= number_format((float) $order['amount_due'], 2) ?></strong></div>
                    </div>

                    <form method="post" class="manual-payment-form" id="manual-payment-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                        <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">

                        <?php if ($order['payment_method'] === 'cod'): ?>
                            <label for="submitted_amount">Cash amount you will prepare</label>
                            <div class="money-input"><span>₱</span><input id="submitted_amount" name="submitted_amount" type="number" min="<?= htmlspecialchars((string) $order['amount_due']) ?>" step="0.01" value="<?= htmlspecialchars((string) ($_POST['submitted_amount'] ?? $order['amount_due'])) ?>" required></div>
                            <small>The page calculates the expected courier change.</small>

                        <?php elseif ($order['payment_method'] === 'gcash'): ?>
                            <label for="reference_number">GCash reference number</label>
                            <input id="reference_number" name="reference_number" type="text" minlength="6" maxlength="30" pattern="[A-Za-z0-9-]{6,30}" value="<?= htmlspecialchars((string) ($_POST['reference_number'] ?? '')) ?>" placeholder="Example: 1234567890123" required>
                            <label for="submitted_amount">Amount sent</label>
                            <div class="money-input"><span>₱</span><input id="submitted_amount" name="submitted_amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars((string) ($_POST['submitted_amount'] ?? $order['amount_due'])) ?>" required></div>
                            <small>Reference format is checked, but the transaction itself cannot be verified.</small>

                        <?php else: ?>
                            <div class="demo-warning">Demonstration only. Do not enter a real card number or CVV.</div>
                            <label for="card_last_four">Demonstration card — last four digits</label>
                            <input id="card_last_four" name="card_last_four" type="text" inputmode="numeric" maxlength="4" pattern="\d{4}" value="<?= htmlspecialchars((string) ($_POST['card_last_four'] ?? '')) ?>" placeholder="1234" required>
                            <label for="card_expiry">Demonstration expiry</label>
                            <input id="card_expiry" name="card_expiry" type="text" inputmode="numeric" maxlength="5" pattern="(0[1-9]|1[0-2])/\d{2}" value="<?= htmlspecialchars((string) ($_POST['card_expiry'] ?? '')) ?>" placeholder="MM/YY" required>
                            <input type="hidden" name="submitted_amount" value="<?= htmlspecialchars((string) $order['amount_due']) ?>">
                            <small>Only the last four digits and expiry format are recorded as an unverified classroom reference.</small>
                        <?php endif; ?>

                        <button type="submit" class="btn-continue payment-submit">Record manual payment</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php $path = ''; include 'includes/footer.php'; ?>
    <?php if ($order && !$submitted): ?>
    <script>
        (() => {
            const panel = document.querySelector('.payment-math');
            const amountInput = document.getElementById('submitted_amount');
            if (!panel || !amountInput) return;
            const due = Number(panel.dataset.amountDue || 0);
            const enteredDisplay = panel.querySelector('[data-entered-display]');
            const resultLabel = panel.querySelector('[data-result-label]');
            const resultDisplay = panel.querySelector('[data-result-display]');
            const money = value => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);

            function calculate() {
                const entered = Math.max(0, Number(amountInput.value || 0));
                const difference = entered - due;
                enteredDisplay.textContent = money(entered);
                resultLabel.textContent = difference >= 0
                    ? '<?= $order['payment_method'] === 'cod' ? 'Expected change' : 'Excess amount' ?>'
                    : 'Remaining balance';
                resultDisplay.textContent = money(Math.abs(difference));
                panel.classList.toggle('has-shortfall', difference < 0);
            }
            amountInput.addEventListener('input', calculate);
            calculate();
        })();
    </script>
    <?php endif; ?>
</body>
</html>
