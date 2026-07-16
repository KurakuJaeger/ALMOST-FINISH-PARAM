<?php
require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/services/email-service.php';

ensureSessionStarted();

$email = (string) ($_SESSION['pending_verification_email'] ?? '');
$initialSendState = $_SESSION['verification_email_sent'] ?? null;
unset($_SESSION['verification_email_sent']);

$notice = '';
$noticeType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $notice = 'Your form session expired. Please refresh the page and try again.';
        $noticeType = 'error';
    } elseif ($email === '') {
        $notice = 'Sign in with your registered email and password to request another confirmation message.';
        $noticeType = 'error';
    } elseif ((int) ($_SESSION['verification_last_sent_at'] ?? 0) > time() - 60) {
        $notice = 'A confirmation message was sent recently. Please wait one minute before trying again.';
    } else {
        $user = User::findByEmail($email);

        if (!$user || $user['role_name'] !== 'Customer' || $user['status'] !== 'pending_verification') {
            $notice = 'This account no longer needs email confirmation. You can try signing in.';
        } else {
            try {
                $plainToken = User::createEmailVerificationToken((int) $user['user_id']);
                $verificationUrl = appAbsoluteUrl('verify-email') . '?token=' . urlencode($plainToken);
                $recipientName = trim($user['first_name'] . ' ' . $user['last_name']);
                $sent = EmailService::sendCustomerEmailVerification($user['email'], $recipientName, $verificationUrl);

                if ($sent) {
                    $_SESSION['verification_last_sent_at'] = time();
                    $notice = 'A fresh confirmation link has been sent. Please check your inbox and spam folder.';
                    $noticeType = 'success';
                } else {
                    $notice = 'Email delivery is not configured right now. Please contact the site administrator.';
                    $noticeType = 'error';
                }
            } catch (Throwable) {
                $notice = 'We could not send another confirmation message right now. Please try again shortly.';
                $noticeType = 'error';
            }
        }
    }
} elseif ($initialSendState === true) {
    $notice = 'We sent a confirmation link to your email address. It will expire in 24 hours.';
    $noticeType = 'success';
} elseif ($initialSendState === false) {
    $notice = 'Your account was created, but the confirmation email could not be sent. Check the mail configuration, then use resend.';
    $noticeType = 'error';
}

function maskedEmail(string $email): string
{
    if (!str_contains($email, '@')) {
        return '';
    }

    [$local, $domain] = explode('@', $email, 2);
    $visible = substr($local, 0, min(2, strlen($local)));
    return $visible . str_repeat('*', max(2, strlen($local) - strlen($visible))) . '@' . $domain;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check your email | PARAM</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/verification.css')) ?>">
</head>
<body>
    <main class="verification-shell">
        <section class="verification-card" aria-labelledby="verification-title">
            <a class="verification-brand" href="<?= htmlspecialchars(appUrl()) ?>">
                <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
            </a>
            <div class="verification-icon" aria-hidden="true">&#9993;</div>
            <span class="verification-eyebrow">One last step</span>
            <h1 id="verification-title">Check your inbox.</h1>
            <p>
                Confirm your email before signing in<?php if ($email !== ''): ?>. We&rsquo;re sending messages to
                <strong><?= htmlspecialchars(maskedEmail($email)) ?></strong><?php endif; ?>.
            </p>

            <?php if ($notice !== ''): ?>
                <div class="verification-notice verification-notice-<?= htmlspecialchars($noticeType) ?>" role="status">
                    <?= htmlspecialchars($notice) ?>
                </div>
            <?php endif; ?>

            <?php if ($email !== ''): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <button class="verification-button" type="submit">Resend confirmation email</button>
                </form>
            <?php endif; ?>

            <div class="verification-links">
                <a href="<?= htmlspecialchars(appUrl('login')) ?>">Return to sign in</a>
                <a href="<?= htmlspecialchars(appUrl()) ?>">Back to PARAM home</a>
            </div>
        </section>
    </main>
</body>
</html>
