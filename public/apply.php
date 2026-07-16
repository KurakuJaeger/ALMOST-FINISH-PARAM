<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/middleware/authentication.php';
require_once __DIR__ . '/../src/models/philippine-location.php';

ensureSessionStarted();

$errors = [];
$submitted = false;
$roles = ['Customer Service', 'Delivery'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals(csrfToken(), $providedToken)) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    $firstName = trim((string) ($_POST['first_name'] ?? ''));
    $middleName = trim((string) ($_POST['middle_name'] ?? ''));
    $lastName = trim((string) ($_POST['last_name'] ?? ''));
    $suffix = trim((string) ($_POST['suffix'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $requestedRole = trim((string) ($_POST['requested_role'] ?? ''));
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $experience = trim((string) ($_POST['experience'] ?? ''));
    $availability = trim((string) ($_POST['availability'] ?? ''));
    $houseNo = trim((string) ($_POST['house_no'] ?? ''));
    $street = trim((string) ($_POST['street'] ?? ''));
    $regionCode = trim((string) ($_POST['region_code'] ?? ''));
    $provinceId = (int) ($_POST['province_id'] ?? 0);
    $localityId = (int) ($_POST['locality_id'] ?? 0);
    $barangayId = (int) ($_POST['barangay_id'] ?? 0);
    $postalCode = trim((string) ($_POST['postal_code'] ?? ''));

    if ($firstName === '' || $lastName === '') {
        $errors[] = 'First name and last name are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($reason === '') {
        $errors[] = 'Tell us why you want to join PARAM.';
    }
    if ($street === '' || !PhilippineLocation::validHierarchy($regionCode, $provinceId, $localityId, $barangayId)) {
        $errors[] = 'Choose a complete and valid Philippine address.';
    }

    if (!in_array($requestedRole, $roles, true)) {
        $errors[] = 'Choose an available position.';
    }

    if (!$errors) {
        $roleStatement = getDbConnection()->prepare(
            "SELECT role_id
             FROM roles
             WHERE role_name = :role_name
               AND is_publicly_applicable = 1
             LIMIT 1"
        );
        $roleStatement->execute(['role_name' => $requestedRole]);
        $requestedRoleId = (int) $roleStatement->fetchColumn();

        if ($requestedRoleId <= 0) {
            $errors[] = 'The selected position is not currently available.';
        }
    }

    if (!$errors) {
        $duplicateStatement = getDbConnection()->prepare(
            "SELECT COUNT(*)
             FROM staff_applications
             WHERE email = :email
               AND status = 'pending'"
        );
        $duplicateStatement->execute(['email' => $email]);

        if ((int) $duplicateStatement->fetchColumn() > 0) {
            $errors[] = 'A pending application already exists for this email address.';
        }
    }

    if (!$errors) {
        $database = getDbConnection();
        $database->beginTransaction();
        try {
            $statement = $database->prepare(
                "INSERT INTO staff_applications (
                first_name, middle_name, last_name, suffix, email, phone,
                requested_role_id, reason, experience, availability
             ) VALUES (
                :first_name, :middle_name, :last_name, :suffix, :email, :phone,
                :requested_role_id, :reason, :experience, :availability
             )"
            );
            $statement->execute([
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'suffix' => $suffix !== '' ? $suffix : null,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'requested_role_id' => $requestedRoleId,
                'reason' => $reason,
                'experience' => $experience !== '' ? $experience : null,
                'availability' => $availability !== '' ? $availability : null,
            ]);
            $applicationId = (int) $database->lastInsertId();
            $addressStatement = $database->prepare(
                "INSERT INTO application_addresses (
                    application_id, house_no, street, region_code, province_id,
                    locality_id, barangay_id, postal_code
                 ) VALUES (
                    :application_id, :house_no, :street, :region_code, :province_id,
                    :locality_id, :barangay_id, :postal_code
                 )"
            );
            $addressStatement->execute([
                'application_id' => $applicationId, 'house_no' => $houseNo ?: null,
                'street' => $street, 'region_code' => $regionCode, 'province_id' => $provinceId,
                'locality_id' => $localityId, 'barangay_id' => $barangayId,
                'postal_code' => $postalCode ?: null,
            ]);
            $database->commit();
            $submitted = true;
            $_POST = [];
        } catch (Throwable) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            $errors[] = 'Your application could not be submitted. Please try again.';
        }
    }
}

function oldApplicationValue(string $key): string
{
    return htmlspecialchars((string) ($_POST[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply to PARAM</title>
    <meta name="description" content="Submit an application for an available PARAM staff role.">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('landing/assets/landing.css') . '?v=' . filemtime(__DIR__ . '/landing/assets/landing.css')) ?>">
</head>
<body>
<?php require __DIR__ . '/landing/includes/header.php'; ?>

<main class="portal-main">
    <div class="container portal-grid">
        <section class="portal-intro">
            <span class="eyebrow">Join the team</span>
            <h1>Build better everyday experiences with PARAM.</h1>
            <p>Applications are reviewed by an administrator. Approved applicants receive an account setup link for their assigned role.</p>
        </section>

        <section class="portal-card" aria-labelledby="application-heading">
            <h2 id="application-heading">Staff application</h2>

            <?php if ($submitted): ?>
                <div class="portal-notice success" role="status">Your application was submitted successfully.</div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="portal-notice error" role="alert">
                    <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" class="portal-form" data-location-form data-location-endpoint="<?= htmlspecialchars(appUrl('location-options.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

                <div class="portal-form-grid">
                    <label>First name<input name="first_name" value="<?= oldApplicationValue('first_name') ?>" required></label>
                    <label>Middle name<input name="middle_name" value="<?= oldApplicationValue('middle_name') ?>"></label>
                    <label>Last name<input name="last_name" value="<?= oldApplicationValue('last_name') ?>" required></label>
                    <label>Suffix<input name="suffix" value="<?= oldApplicationValue('suffix') ?>"></label>
                    <label>Email<input type="email" name="email" value="<?= oldApplicationValue('email') ?>" required></label>
                    <label>Phone<input type="tel" name="phone" value="<?= oldApplicationValue('phone') ?>"></label>
                </div>

                <h3>Home address</h3>
                <div class="portal-form-grid">
                    <label>House no.<input name="house_no" value="<?= oldApplicationValue('house_no') ?>"></label>
                    <label>Street<input name="street" value="<?= oldApplicationValue('street') ?>" required></label>
                    <label>Region<select name="region_code" data-selected="<?= oldApplicationValue('region_code') ?>" required><option value="">Loading regions...</option></select></label>
                    <label>Province / district<select name="province_id" data-selected="<?= (int) ($_POST['province_id'] ?? 0) ?>" required disabled><option value="">Choose a province/district</option></select></label>
                    <label>City / municipality<select name="locality_id" data-selected="<?= (int) ($_POST['locality_id'] ?? 0) ?>" required disabled><option value="">Choose a city/municipality</option></select></label>
                    <label>Barangay<select name="barangay_id" data-selected="<?= (int) ($_POST['barangay_id'] ?? 0) ?>" required disabled><option value="">Choose a barangay</option></select></label>
                    <label>Postal code<input name="postal_code" value="<?= oldApplicationValue('postal_code') ?>"></label>
                </div>

                <label>Position
                    <select name="requested_role" required>
                        <option value="">Choose a role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>" <?= ($_POST['requested_role'] ?? '') === $role ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Why do you want to join PARAM?<textarea name="reason" rows="4" required><?= oldApplicationValue('reason') ?></textarea></label>
                <label>Relevant experience<textarea name="experience" rows="3"><?= oldApplicationValue('experience') ?></textarea></label>
                <label>Availability<input name="availability" value="<?= oldApplicationValue('availability') ?>" placeholder="e.g. Weekdays, 9 AM–5 PM"></label>

                <button class="button" type="submit">Submit Application</button>
            </form>
        </section>
    </div>
</main>

<script src="<?= htmlspecialchars(appUrl('assets/location-selects.js') . '?v=' . filemtime(__DIR__ . '/assets/location-selects.js')) ?>"></script>
<?php require __DIR__ . '/landing/includes/footer.php'; ?>
