<?php
require_once __DIR__ . '/../src/middleware/authentication.php';

$error = '';
$needsVerification = false;
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Please refresh the page and try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $user = User::findByEmail($email);

        if ($user && User::verifyPassword($user, $password)) {
            if ($user['role_name'] === 'Customer' && $user['status'] === 'pending_verification') {
                $_SESSION['pending_verification_email'] = $user['email'];
                $needsVerification = true;
                $error = 'Please confirm your email address before signing in.';
            } elseif ($user['status'] === 'active') {
                loginUser($user);
                $destinations = [
                    'Customer' => 'store',
                    'Administrator' => 'admin',
                    'Delivery' => 'delivery',
                    'Customer Service' => 'support',
                ];
                redirectTo($destinations[$user['role_name']] ?? '');
            }
        }

        if ($error === '') {
            $error = 'We could not sign you in. Check your email and password, then try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | PARAM</title>
    <meta name="description" content="Sign in to your PARAM customer or staff account.">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/login.css')) ?>">
</head>
<body class="login-page">
    <header class="login-topbar">
        <a class="login-brand" href="<?= htmlspecialchars(appUrl()) ?>" aria-label="Return to PARAM home">
            <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
        </a>
        <div class="login-topbar-action">
            <span>New to PARAM?</span>
            <a href="<?= htmlspecialchars(appUrl('register')) ?>">Create an account</a>
        </div>
    </header>

    <main class="login-layout">
        <section class="login-visual" aria-label="PARAM clothing collection">
            <img src="<?= htmlspecialchars(appUrl('landing/assets/images/hero.jpg')) ?>" alt="Models wearing pieces from the PARAM collection">
            <div class="login-visual-overlay"></div>
            <div class="login-visual-copy">
                <span class="login-kicker">Designed for everyday life</span>
                <h1>One account.<br><em>Every side</em> of PARAM.</h1>
                <p>Shop thoughtfully chosen essentials or continue to your team workspace from one secure entrypoint.</p>
                <div class="login-access-list" aria-label="Account access areas">
                    <span>Customer storefront</span>
                    <span>Team workspaces</span>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <a class="login-back" href="<?= htmlspecialchars(appUrl()) ?>" aria-label="Back to PARAM home">
                    <span aria-hidden="true">←</span> Back to home
                </a>

                <div class="login-heading">
                    <span class="login-eyebrow">Welcome back</span>
                    <h2>Sign in to PARAM</h2>
                    <p>Use the account assigned to you. We’ll take you to the right place automatically.</p>
                </div>

                <?php if ($error): ?>
                    <div class="login-alert" role="alert">
                        <span class="login-alert-icon" aria-hidden="true">!</span>
                        <p>
                            <?= htmlspecialchars($error) ?>
                            <?php if ($needsVerification): ?>
                                <a href="<?= htmlspecialchars(appUrl('verify-pending')) ?>">Resend confirmation email</a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

                    <label for="login-email">Email address</label>
                    <div class="login-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 7l8 6 8-6"/></svg>
                        <input id="login-email" type="email" name="email" autocomplete="email" placeholder="you@example.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                    </div>

                    <div class="login-label-row">
                        <label for="login-password">Password</label>
                        <a class="login-forgot-link" href="<?= htmlspecialchars(appUrl('forgot-password')) ?>">Forgot password?</a>
                    </div>
                    <div class="login-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                        <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <button class="password-toggle" type="button" aria-controls="login-password" aria-pressed="false">Show</button>
                    </div>

                    <button class="login-submit" type="submit">
                        Sign in <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="login-divider"><span>Need a different starting point?</span></div>

                <div class="login-secondary-actions">
                    <a href="<?= htmlspecialchars(appUrl('register')) ?>"><strong>Shop with PARAM</strong><span>Create a customer account</span></a>
                    <a href="<?= htmlspecialchars(appUrl('apply')) ?>"><strong>Join the team</strong><span>Submit a staff application</span></a>
                </div>
            </div>

            <footer class="login-footer">
                <span>&copy; <?= date('Y') ?> PARAM</span>
                <span>Secure account access</span>
            </footer>
        </section>
    </main>

    <script src="<?= htmlspecialchars(appUrl('landing/assets/login.js')) ?>"></script>
</body>
</html>
