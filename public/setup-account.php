<?php

require_once __DIR__ . '/../src/models/user.php';

$setupTokenValue = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$errorMessage = '';
$passwordWasSaved = false;
$setupToken = findValidSetupToken($setupTokenValue);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!$setupToken) {
        $errorMessage = 'This setup link is invalid or has expired.';
    } elseif (strlen($password) < 10) {
        $errorMessage = 'Password must contain at least 10 characters.';
    } elseif ($password !== $passwordConfirmation) {
        $errorMessage = 'Passwords do not match.';
    } else {
        saveNewPassword($setupToken, $password);
        $passwordWasSaved = true;
    }
} elseif (!$setupToken) {
    $errorMessage = 'This setup link is invalid or has expired.';
}

function findValidSetupToken(string $plainToken): array|false
{
    if ($plainToken === '') {
        return false;
    }

    $statement = getDbConnection()->prepare(
        "SELECT token_id, user_id
         FROM auth_tokens
         WHERE token_type = 'account_setup'
           AND token_hash = :token_hash
           AND used_at IS NULL
           AND expires_at > NOW()
         LIMIT 1"
    );
    $statement->execute([
        'token_hash' => hash('sha256', $plainToken),
    ]);

    return $statement->fetch();
}

function saveNewPassword(array $setupToken, string $password): void
{
    $database = getDbConnection();
    $database->beginTransaction();

    try {
        User::setPassword((int) $setupToken['user_id'], $password);

        $statement = $database->prepare(
            'UPDATE auth_tokens
             SET used_at = NOW()
             WHERE token_id = :token_id
               AND used_at IS NULL'
        );
        $statement->execute([
            'token_id' => $setupToken['token_id'],
        ]);

        $database->commit();
    } catch (Throwable $error) {
        $database->rollBack();
        throw $error;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PARAM | Set Up Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('setup-account.css') . '?v=' . filemtime(__DIR__ . '/setup-account.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <main class="setup-page container-fluid">
        <a class="back-link" href="<?= htmlspecialchars(appUrl(), ENT_QUOTES, 'UTF-8') ?>">&larr; Back to home</a>

        <section class="setup-card" aria-labelledby="setup-title">
            <div class="brand-panel">
                <img class="brand-logo" src="<?= htmlspecialchars(appUrl('images/logo-header.png'), ENT_QUOTES, 'UTF-8') ?>" alt="PARAM">
                <p class="brand-label">PARAM Account Access</p>
                <h1>Start securely</h1>
                <p class="brand-copy">
                    Create the password you will use to access your assigned
                    PARAM account and workspace.
                </p>
                <ul class="security-list">
                    <li>Private account setup</li>
                    <li>One-time secure link</li>
                    <li>Role-based access</li>
                </ul>
            </div>

            <div class="form-panel">
                <?php if ($passwordWasSaved): ?>
                    <div class="status-panel success-panel">
                        <p class="form-label-top">Account ready</p>
                        <h2 id="setup-title">Password saved</h2>
                        <p class="form-intro">
                            Your account setup is complete. You can now sign in
                            using your email address and new password.
                        </p>
                        <a class="btn setup-button w-100" href="<?= htmlspecialchars(appUrl('login'), ENT_QUOTES, 'UTF-8') ?>">Continue to login</a>
                    </div>
                <?php else: ?>
                    <p class="form-label-top">Account security</p>
                    <h2 id="setup-title">Set your password</h2>
                    <p class="form-intro">
                        Choose a password with at least 10 characters, then
                        enter it again to confirm.
                    </p>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger error-message" role="alert">
                            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($setupToken): ?>
                        <form class="setup-form" method="post">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($setupTokenValue, ENT_QUOTES, 'UTF-8') ?>">

                            <div>
                                <label class="form-label" for="password">New password</label>
                                <div class="password-wrap">
                                    <input class="form-control form-control-lg" id="password" type="password" name="password"
                                           minlength="10" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false">Show</button>
                                </div>
                            </div>

                            <div>
                                <label class="form-label" for="password-confirmation">Confirm password</label>
                                <div class="password-wrap">
                                    <input class="form-control form-control-lg" id="password-confirmation" type="password"
                                           name="password_confirmation" minlength="10" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" aria-controls="password-confirmation" aria-pressed="false">Show</button>
                                </div>
                            </div>

                            <button class="btn setup-button w-100" type="submit">Save password</button>
                        </form>
                    <?php else: ?>
                        <a class="btn setup-button setup-button-outline w-100" href="<?= htmlspecialchars(appUrl('login'), ENT_QUOTES, 'UTF-8') ?>">Return to login</a>
                    <?php endif; ?>

                    <p class="setup-note">This setup link expires after 24 hours and can only be used once.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.password-toggle').forEach(function (toggle) {
                var targetId = toggle.getAttribute('aria-controls');
                var input = document.getElementById(targetId);
                if (!input) return;
                toggle.addEventListener('click', function () {
                    var isVisible = input.type === 'text';
                    input.type = isVisible ? 'password' : 'text';
                    toggle.textContent = isVisible ? 'Show' : 'Hide';
                    toggle.setAttribute('aria-pressed', String(!isVisible));
                });
            });
        });
    </script>
</body>
</html>
