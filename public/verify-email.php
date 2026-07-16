<?php
require_once __DIR__ . '/../src/middleware/authentication.php';

ensureSessionStarted();

$plainToken = trim((string) ($_GET['token'] ?? ''));
$verifiedUser = null;
$verificationError = '';

if ($plainToken === '') {
    $verificationError = 'This confirmation link is incomplete.';
} else {
    try {
        $verifiedUser = User::verifyEmailToken($plainToken);
        if (!$verifiedUser) {
            $verificationError = 'This confirmation link is invalid, expired, or has already been used.';
        } else {
            unset(
                $_SESSION['pending_verification_email'],
                $_SESSION['verification_email_sent'],
                $_SESSION['verification_last_sent_at']
            );
        }
    } catch (Throwable) {
        $verificationError = 'We could not confirm your email right now. Please try again shortly.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm email | PARAM</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/verification.css')) ?>">
</head>
<body>
    <main class="verification-shell">
        <section class="verification-card" aria-labelledby="verification-title">
            <a class="verification-brand" href="<?= htmlspecialchars(appUrl()) ?>">
                <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
            </a>

            <?php if ($verifiedUser): ?>
                <div class="verification-icon verification-icon-success" aria-hidden="true">&#10003;</div>
                <span class="verification-eyebrow">Email confirmed</span>
                <h1 id="verification-title">Your account is ready.</h1>
                <p>Thanks, <?= htmlspecialchars((string) $verifiedUser['first_name']) ?>. You can now sign in and continue to the PARAM storefront.</p>
                <a class="verification-button" href="<?= htmlspecialchars(appUrl('login')) ?>">Continue to sign in</a>
            <?php else: ?>
                <div class="verification-icon verification-icon-error" aria-hidden="true">!</div>
                <span class="verification-eyebrow">Confirmation problem</span>
                <h1 id="verification-title">We couldn&rsquo;t confirm that link.</h1>
                <p><?= htmlspecialchars($verificationError) ?></p>
                <a class="verification-button" href="<?= htmlspecialchars(appUrl('login')) ?>">Return to sign in</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
