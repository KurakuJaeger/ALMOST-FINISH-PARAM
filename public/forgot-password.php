<?php
require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/services/email-service.php';

ensureSessionStarted();

$submitted = false;
$error = '';
$email = strtolower(trim((string) ($_POST['email'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your form session expired. Please refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif ((int) ($_SESSION['password_reset_requested_at'] ?? 0) > time() - 60) {
        $submitted = true;
    } else {
        $user = User::findByEmail($email);

        if ($user && $user['status'] === 'active') {
            try {
                $plainToken = User::createPasswordResetToken((int) $user['user_id']);
                $resetUrl = appAbsoluteUrl('reset-password') . '?token=' . urlencode($plainToken);
                $recipientName = trim($user['first_name'] . ' ' . $user['last_name']);
                EmailService::sendPasswordReset($user['email'], $recipientName, $resetUrl);
            } catch (Throwable) {
                // Keep the public response generic so account and SMTP state are not disclosed.
            }
        }

        $_SESSION['password_reset_requested_at'] = time();
        $submitted = true;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password | PARAM</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/verification.css')) ?>">
</head>
<body>
    <main class="verification-shell">
        <section class="verification-card" aria-labelledby="forgot-title">
            <a class="verification-brand" href="<?= htmlspecialchars(appUrl()) ?>">
                <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
            </a>
            <div class="verification-icon" aria-hidden="true">?</div>
            <span class="verification-eyebrow">Account recovery</span>
            <h1 id="forgot-title">Reset your password.</h1>

            <?php if ($submitted): ?>
                <p>If an active account matches that email, we sent a one-time reset link. It expires in one hour.</p>
                <div class="verification-notice verification-notice-success" role="status">
                    Check your inbox and spam folder before requesting another message.
                </div>
            <?php else: ?>
                <p>Enter the email address connected to your PARAM account.</p>
                <?php if ($error !== ''): ?>
                    <div class="verification-notice verification-notice-error" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="verification-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <label for="recovery-email">Email address</label>
                    <input id="recovery-email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" autocomplete="email" required autofocus>
                    <button class="verification-button" type="submit">Send reset link</button>
                </form>
            <?php endif; ?>

            <div class="verification-links">
                <a href="<?= htmlspecialchars(appUrl('login')) ?>">Return to sign in</a>
            </div>
        </section>
    </main>
</body>
</html>
