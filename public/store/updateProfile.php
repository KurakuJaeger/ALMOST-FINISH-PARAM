<?php

require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/models/philippine-location.php';
require_once __DIR__ . '/includes/db.php';

$currentUser = requireLoginOrRedirect();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('store/Profile.php');
}
if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Your form session expired.');
}

$userId = (int) $currentUser['user_id'];
$required = ['first_name', 'last_name', 'email', 'contact_number', 'street', 'region_code', 'province_id', 'locality_id', 'barangay_id'];
foreach ($required as $field) {
    if (trim((string) ($_POST[$field] ?? '')) === '') {
        http_response_code(422);
        exit('Please complete every required profile field.');
    }
}

if (!PhilippineLocation::validHierarchy(
    trim((string) $_POST['region_code']),
    (int) $_POST['province_id'],
    (int) $_POST['locality_id'],
    (int) $_POST['barangay_id']
)) {
    http_response_code(422);
    exit('Please select a valid Philippine address hierarchy.');
}

$imagePath = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK || $_FILES['profile_picture']['size'] > 2097152) {
        http_response_code(422);
        exit('Profile images must be no larger than 2 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['profile_picture']['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($types[$mime])) {
        http_response_code(422);
        exit('Profile images must be JPEG, PNG, or WebP files.');
    }
    $directory = dirname(__DIR__) . '/uploads/profiles';
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $filename = sprintf('user_%d_%s.%s', $userId, bin2hex(random_bytes(8)), $types[$mime]);
    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The profile image could not be saved.');
    }
    $imagePath = 'uploads/profiles/' . $filename;
}

$pdo->beginTransaction();
try {
    $sql = "UPDATE users SET first_name=:first_name, middle_name=:middle_name,
            last_name=:last_name, email=:email";
    $values = [
        'first_name' => trim((string) $_POST['first_name']),
        'middle_name' => trim((string) ($_POST['middle_name'] ?? '')) ?: null,
        'last_name' => trim((string) $_POST['last_name']),
        'email' => strtolower(trim((string) $_POST['email'])),
        'user_id' => $userId,
    ];
    if ($imagePath) {
        $sql .= ', profile_image_path=:profile_image_path';
        $values['profile_image_path'] = $imagePath;
    }
    $sql .= ' WHERE user_id=:user_id';
    $pdo->prepare($sql)->execute($values);

    $contactId = $pdo->prepare('SELECT contact_id FROM user_contacts WHERE user_id=? AND is_primary=1 LIMIT 1');
    $contactId->execute([$userId]);
    if ($id = $contactId->fetchColumn()) {
        $pdo->prepare('UPDATE user_contacts SET contact_number=? WHERE contact_id=?')
            ->execute([trim((string) $_POST['contact_number']), $id]);
    } else {
        $pdo->prepare("INSERT INTO user_contacts (user_id, contact_number, contact_type, is_primary) VALUES (?, ?, 'Mobile', 1)")
            ->execute([$userId, trim((string) $_POST['contact_number'])]);
    }

    $addressId = $pdo->prepare('SELECT address_id FROM user_addresses WHERE user_id=? AND is_default=1 LIMIT 1');
    $addressId->execute([$userId]);
    $address = [
        trim((string) ($_POST['house_no'] ?? '')) ?: null, trim((string) $_POST['street']),
        trim((string) $_POST['region_code']), (int) $_POST['province_id'],
        (int) $_POST['locality_id'], (int) $_POST['barangay_id'],
        trim((string) ($_POST['postal_code'] ?? '')) ?: null,
    ];
    if ($id = $addressId->fetchColumn()) {
        $pdo->prepare('UPDATE user_addresses SET house_no=?, street=?, region_code=?, province_id=?, locality_id=?, barangay_id=?, postal_code=? WHERE address_id=?')
            ->execute([...$address, $id]);
    } else {
        $pdo->prepare('INSERT INTO user_addresses (house_no, street, region_code, province_id, locality_id, barangay_id, postal_code, user_id, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)')
            ->execute([...$address, $userId]);
    }
    $pdo->commit();
    redirectTo('store/Profile.php');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($imagePath) {
        @unlink(dirname(__DIR__) . '/' . $imagePath);
    }
    http_response_code(500);
    exit('The profile could not be updated.');
}
