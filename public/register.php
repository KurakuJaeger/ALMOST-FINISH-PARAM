<?php
require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/models/philippine-location.php';
require_once __DIR__ . '/../src/services/email-service.php';

ensureSessionStarted();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email_address'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $required = ['first_name', 'last_name', 'street', 'region_code', 'province_id', 'locality_id', 'barangay_id', 'zip_code', 'contact_number'];
    $missing = array_filter($required, fn ($field) => trim((string) ($_POST[$field] ?? '')) === '');

    if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Your form session expired. Please try again.';
    } elseif ($missing || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please complete every required field with a valid email address.';
    } elseif (!PhilippineLocation::validHierarchy(
        trim((string) ($_POST['region_code'] ?? '')),
        (int) ($_POST['province_id'] ?? 0),
        (int) ($_POST['locality_id'] ?? 0),
        (int) ($_POST['barangay_id'] ?? 0)
    )) {
        $error = 'Please select a valid Philippine address hierarchy.';
    } elseif (!preg_match('/^\d{4}$/', trim((string) ($_POST['zip_code'] ?? '')))) {
        $error = 'Please enter a valid 4-digit Philippine postal code.';
    } elseif (strlen($password) < 8 || $password !== (string) ($_POST['confirm_password'] ?? '')) {
        $error = 'Passwords must match and contain at least eight characters.';
    } elseif (empty($_POST['terms'])) {
        $error = 'You must accept the Terms of Service and Privacy Policy.';
    } elseif (User::findByEmail($email)) {
        $error = 'An account already exists for this email address.';
    } else {
        try {
            $registration = User::registerCustomer([
                'first_name' => trim((string) $_POST['first_name']),
                'middle_name' => trim((string) ($_POST['middle_name'] ?? '')),
                'last_name' => trim((string) $_POST['last_name']),
                'suffix' => ($_POST['suffix'] ?? '') === 'N/A' ? '' : trim((string) ($_POST['suffix'] ?? '')),
                'email' => $email, 'password' => $password,
                'house_no' => trim((string) ($_POST['house_no'] ?? '')),
                'street' => trim((string) $_POST['street']),
                'region_code' => trim((string) $_POST['region_code']),
                'province_id' => (int) $_POST['province_id'],
                'locality_id' => (int) $_POST['locality_id'],
                'barangay_id' => (int) $_POST['barangay_id'],
                'postal_code' => trim((string) ($_POST['zip_code'] ?? '')),
                'contact_number' => trim((string) $_POST['contact_number']),
            ]);

            $verificationUrl = appAbsoluteUrl('verify-email') . '?token='
                . urlencode($registration['verification_token']);
            $recipientName = trim((string) $_POST['first_name'] . ' ' . (string) $_POST['last_name']);
            $emailSent = false;

            try {
                $emailSent = EmailService::sendCustomerEmailVerification(
                    $email,
                    $recipientName,
                    $verificationUrl
                );
            } catch (Throwable) {
                $emailSent = false;
            }

            $_SESSION['pending_verification_email'] = $email;
            $_SESSION['verification_email_sent'] = $emailSent;
            if ($emailSent) {
                $_SESSION['verification_last_sent_at'] = time();
            }
            redirectTo('verify-pending');
        } catch (Throwable $exception) {
            $error = $exception instanceof PDOException && $exception->getCode() === '23000'
                ? 'That email address is already registered.'
                : 'We could not create your account right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Account - Param Clothing</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('store/css/Signup.css') . '?v=' . filemtime(__DIR__ . '/store/css/Signup.css')) ?>">
</head>

<body>

    <div class="signup-card">
        <div class="signup-brand-row">
            <a href="<?= htmlspecialchars(appUrl()) ?>" aria-label="Return to PARAM home">
                <img src="<?= htmlspecialchars(appUrl('landing/assets/images/logo-header.png')) ?>" alt="PARAM">
            </a>
            <a class="signup-signin-link" href="<?= htmlspecialchars(appUrl('login')) ?>">Sign in</a>
        </div>

        <div class="signup-heading">
            <span>Customer registration</span>
            <h1 class="form-title">Create your PARAM account</h1>
            <p>One account for shopping, favorites, checkout, and order updates.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form action="<?= htmlspecialchars(appUrl('register')) ?>" method="POST" data-location-form data-location-endpoint="<?= htmlspecialchars(appUrl('location-options.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

            <div class="section-label">Complete Name</div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="e.g., Juan"
                        required>
                </div>
                <div class="col-md-3">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name"
                        placeholder="e.g., Dela">
                </div>
                <div class="col-md-4">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="e.g., Cruz"
                        required>
                </div>
                <div class="col-md-2">
                    <label for="suffix" class="form-label">Suffix</label>
                    <select class="form-select" id="suffix" name="suffix">
                        <option value="N/A" selected>N/A</option>
                        <option value="Jr.">Jr.</option>
                        <option value="Sr.">Sr.</option>
                        <option value="II">II</option>
                        <option value="III">III</option>
                        <option value="IV">IV</option>
                    </select>
                </div>
            </div>

            <div class="section-label">Complete Address</div>
            <div class="location-guide" data-location-status role="status" aria-live="polite">Loading Philippine regions…</div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label for="house_no" class="form-label">House No.</label>
                    <input type="text" class="form-control" id="house_no" name="house_no" placeholder="e.g., 123"
                        required>
                </div>
                <div class="col-md-8">
                    <label for="street" class="form-label">Street</label>
                    <input type="text" class="form-control" id="street" name="street" placeholder="e.g., Rizal Street"
                        required>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-sm-6 location-step" data-location-step data-state="loading">
                    <label for="region_code" class="form-label"><span>1</span> Region</label>
                    <select class="form-select" id="region_code" name="region_code" data-selected="<?= htmlspecialchars((string) ($_POST['region_code'] ?? '')) ?>" required><option value="">Loading regions...</option></select>
                </div>
                <div class="col-sm-6 location-step" data-location-step data-state="locked">
                    <label for="province_id" class="form-label"><span>2</span> Province / District</label>
                    <select class="form-select" id="province_id" name="province_id" data-selected="<?= (int) ($_POST['province_id'] ?? 0) ?>" required disabled><option value="">Choose a province/district</option></select>
                </div>
                <div class="col-sm-6 location-step" data-location-step data-state="locked">
                    <label for="locality_id" class="form-label"><span>3</span> City / Municipality</label>
                    <select class="form-select" id="locality_id" name="locality_id" data-selected="<?= (int) ($_POST['locality_id'] ?? 0) ?>" required disabled><option value="">Choose a city/municipality</option></select>
                </div>
                <div class="col-sm-6 location-step" data-location-step data-state="locked">
                    <label for="barangay_id" class="form-label"><span>4</span> Barangay</label>
                    <select class="form-select" id="barangay_id" name="barangay_id" data-selected="<?= (int) ($_POST['barangay_id'] ?? 0) ?>" required disabled><option value="">Choose a barangay</option></select>
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-6 postal-field">
                    <label for="zip_code" class="form-label">Postal Code <span class="automatic-badge">Automatic</span></label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code" placeholder="Select your city and barangay" value="<?= htmlspecialchars((string) ($_POST['zip_code'] ?? '')) ?>" inputmode="numeric" maxlength="4" pattern="\d{4}" readonly required>
                    <small class="postal-status" data-postal-status aria-live="polite">Choose your city and barangay to find the postal code.</small>
                </div>
            </div>

            <div class="section-label">Email Address</div>
            <div class="row g-2">
                <div class="col-12">
                    <label for="email_address" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email_address" name="email_address"
                        placeholder="e.g., juan.delacruz@example.com" required>
                </div>
            </div>

            <div class="section-label">Contact Number</div>
            <div class="row g-2">
                <div class="col-12">
                    <label for="contact_number" class="form-label">Mobile / Phone Number</label>
                    <input type="tel" class="form-control" id="contact_number" name="contact_number"
                        placeholder="e.g., 09123456789" required>
                </div>
            </div>

            <div class="section-label">Password</div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="password-toggle" type="button" aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button class="password-toggle" type="button" aria-controls="confirm_password" aria-pressed="false">Show</button>
                    </div>
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                <label class="form-check-label terms-text" for="terms">
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                </label>
            </div>

            <button type="submit" class="btn btn-signup">Sign Up</button>

            <div class="login-link-container">
                Already have an account? <a href="<?= htmlspecialchars(appUrl('login')) ?>">Login</a>
            </div>

            <div class="login-link-container">
                <a href="<?= htmlspecialchars(appUrl()) ?>">Return to the PARAM main site</a>
            </div>

        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars(appUrl('assets/location-selects.js') . '?v=' . filemtime(__DIR__ . '/assets/location-selects.js')) ?>"></script>
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
