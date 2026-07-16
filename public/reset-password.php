<?php
require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/services/email-service.php';

ensureSessionStarted();

$plainToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$resetCompletedAt = (int) ($_SESSION['password_reset_complete_at'] ?? 0);
$completedTokenHash = (string) ($_SESSION['password_reset_completed_token_hash'] ?? '');
$recentlyCompletedToken = $plainToken !== ''
    && $resetCompletedAt > time() - 600
    && $completedTokenHash !== ''
    && hash_equals($completedTokenHash, hash('sha256', $plainToken));

if (!isset($_GET['complete']) && $recentlyCompletedToken) {
    redirectTo('reset-password?complete=1', 303);
}

$resetComplete = isset($_GET['complete']) && $resetCompletedAt > time() - 600;
$tokenValid = !$resetComplete && User::hasValidPasswordResetToken($plainToken);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your form session expired. Please refresh the page and try again.';
    } elseif (!$tokenValid) {
        $error = 'This reset link is invalid, expired, or has already been used.';
    } elseif (strlen($password) < 8) {
        $error = 'Your new password must contain at least eight characters.';
    } elseif ($password !== $confirmation) {
        $error = 'The password confirmation does not match.';
    } else {
        try {
            $user = User::resetPasswordWithToken($plainToken, $password);
            if (!$user) {
                $error = 'This reset link is invalid, expired, or has already been used.';
            } else {
                $recipientName = trim($user['first_name'] . ' ' . $user['last_name']);
                try {
                    EmailService::sendPasswordChangedNotification($user['email'], $recipientName);
                } catch (Throwable) {
                    // Password reset remains successful even if the security notice cannot be delivered.
                }
                $_SESSION['password_reset_complete_at'] = time();
                $_SESSION['password_reset_completed_token_hash'] = hash('sha256', $plainToken);
                redirectTo('reset-password?complete=1', 303);
            }
        } catch (Throwable) {
            $error = 'We could not reset your password right now. Please try again shortly.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password | PARAM</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/verification.css')) ?>">
</head>
<body>
    <main class="verification-shell">
        <section class="verification-card" aria-labelledby="reset-title">
            <a class="verification-brand" href="<?= htmlspecialchars(appUrl()) ?>">
                <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
            </a>

            <?php if ($resetComplete): ?>
                <div class="verification-icon verification-icon-success" aria-hidden="true">&#10003;</div>
                <span class="verification-eyebrow">Password updated</span>
                <h1 id="reset-title">You&rsquo;re ready to sign in.</h1>
                <p>Your new password is active. We also attempted to send a security notification to your email.</p>
                <a class="verification-button" href="<?= htmlspecialchars(appUrl('login')) ?>">Continue to sign in</a>
            <?php elseif (!$tokenValid): ?>
                <div class="verification-icon verification-icon-error" aria-hidden="true">!</div>
                <span class="verification-eyebrow">Link unavailable</span>
                <h1 id="reset-title">Request a fresh link.</h1>
                <p><?= htmlspecialchars($error ?: 'This reset link is invalid, expired, or has already been used.') ?></p>
                <a class="verification-button" href="<?= htmlspecialchars(appUrl('forgot-password')) ?>">Request another reset link</a>
            <?php else: ?>
                <div class="verification-icon" aria-hidden="true">&#128274;</div>
                <span class="verification-eyebrow">Secure reset</span>
                <h1 id="reset-title">Choose a new password.</h1>
                <p>Use at least eight characters and avoid reusing a password from another account.</p>
                <?php if ($error !== ''): ?>
                    <div class="verification-notice verification-notice-error" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="verification-form" method="post" id="reset-password-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($plainToken) ?>">
                    <label for="new-password">New password</label>
                    <input id="new-password" type="password" name="password" minlength="8" autocomplete="new-password" required autofocus>
                    <label for="confirm-password">Confirm new password</label>
                    <input id="confirm-password" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                    <button class="verification-button" type="submit">Save new password</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <script>
        document.getElementById('reset-password-form')?.addEventListener('submit', function () {
            const button = this.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Saving password...';
            }
        });
    </script>
</body>
</html>
