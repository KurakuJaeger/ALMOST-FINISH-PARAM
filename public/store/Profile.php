<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/includes/db.php';

$currentUser = requireLoginOrRedirect();
$userId = (int) $currentUser['user_id'];
$passwordNotice = $_SESSION['profile_password_notice'] ?? null;
unset($_SESSION['profile_password_notice']);
$statement = $pdo->prepare(
  "SELECT u.first_name, u.middle_name, u.last_name, u.email, u.profile_image_path,
          c.contact_number, a.house_no, a.street, a.region_code, a.province_id,
          a.locality_id, a.barangay_id, a.postal_code
   FROM users u
   LEFT JOIN user_contacts c ON c.user_id = u.user_id AND c.is_primary = 1
   LEFT JOIN user_addresses a ON a.user_id = u.user_id AND a.is_default = 1
   WHERE u.user_id = :user_id LIMIT 1"
);
$statement->execute(['user_id' => $userId]);
$profile = $statement->fetch() ?: $currentUser;
$fullName = trim(implode(' ', array_filter([$profile['first_name'] ?? '', $profile['middle_name'] ?? '', $profile['last_name'] ?? ''])));
$profilePic = !empty($profile['profile_image_path']) ? appUrl($profile['profile_image_path']) : appUrl('store/images/user.png');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Profile - Param Clothing</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/Profile.css">
</head>

<body>

  <?php
  $path = '';
  include 'includes/header.php';
  ?>

  <div class="profile-card">

    <div class="profile-sidebar">

      <div class="user-summary">
        <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="user-avatar" id="avatarPreview">
        <h3><?= htmlspecialchars($fullName) ?></h3>
        <p><?= htmlspecialchars($profile['email'] ?? '') ?></p>
      </div>

      <button class="nav-btn active" id="btn-info" onclick="showSection('personal-info')">
        Personal Info
      </button>

      <button class="nav-btn" id="btn-password" onclick="showSection('update-password')">
        Update Password
      </button>

      <a href="<?= htmlspecialchars(appUrl('logout')) ?>" class="nav-btn logout-btn">
        Logout
      </a>
    </div>

    <div class="profile-content">

      <div id="personal-info" class="content-section active">
        <h2>Personal Information</h2>

        <form action="<?= htmlspecialchars(appUrl('store/updateProfile.php')) ?>" method="POST" enctype="multipart/form-data" data-location-form data-location-endpoint="<?= htmlspecialchars(appUrl('location-options.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

          <div class="form-group">
            <label>Upload Profile Picture</label>
            <input type="file" name="profile_picture" accept="image/*">
          </div>

          <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Middle Name</label>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($profile['middle_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label>Contact Number</label>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($profile['contact_number'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label>House No.</label><input type="text" name="house_no" value="<?= htmlspecialchars($profile['house_no'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Street</label><input type="text" name="street" value="<?= htmlspecialchars($profile['street'] ?? '') ?>" required>
          </div>
          <div class="form-group"><label>Region</label><select name="region_code" data-selected="<?= htmlspecialchars($profile['region_code'] ?? '') ?>" required><option value="">Loading regions...</option></select></div>
          <div class="form-group"><label>Province / district</label><select name="province_id" data-selected="<?= (int) ($profile['province_id'] ?? 0) ?>" required disabled><option value="">Choose a province/district</option></select></div>
          <div class="form-group"><label>City / municipality</label><select name="locality_id" data-selected="<?= (int) ($profile['locality_id'] ?? 0) ?>" required disabled><option value="">Choose a city/municipality</option></select></div>
          <div class="form-group"><label>Barangay</label><select name="barangay_id" data-selected="<?= (int) ($profile['barangay_id'] ?? 0) ?>" required disabled><option value="">Choose a barangay</option></select></div>
          <div class="form-group">
            <label>Postal Code</label><input type="text" name="postal_code" value="<?= htmlspecialchars($profile['postal_code'] ?? '') ?>">
          </div>

          <button type="submit" class="btn-save">Save Info</button>
        </form>
      </div>

      <div id="update-password" class="content-section">
        <h2>Update Password</h2>

        <?php if (is_array($passwordNotice)): ?>
          <div class="password-notice password-notice-<?= $passwordNotice['type'] === 'success' ? 'success' : 'error' ?>" role="status">
            <?= htmlspecialchars((string) $passwordNotice['message']) ?>
          </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars(appUrl('store/update_password.php')) ?>" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" autocomplete="current-password" required>
          </div>

          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" minlength="8" autocomplete="new-password" required>
          </div>

          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
          </div>

          <button type="submit" class="btn-save">Update Password</button>
        </form>
      </div>

    </div>

  </div>

  <script>
    function showSection(sectionId) {

      document.getElementById('personal-info').classList.remove('active');
      document.getElementById('update-password').classList.remove('active');


      document.getElementById('btn-info').classList.remove('active');
      document.getElementById('btn-password').classList.remove('active');


      document.getElementById(sectionId).classList.add('active');
      if (sectionId === 'personal-info') {
        document.getElementById('btn-info').classList.add('active');
      } else {
        document.getElementById('btn-password').classList.add('active');
      }
    }

    if (new URLSearchParams(window.location.search).get('section') === 'password') {
      showSection('update-password');
    }
  </script>

  <script src="<?= htmlspecialchars(appUrl('assets/location-selects.js') . '?v=' . filemtime(dirname(__DIR__) . '/assets/location-selects.js')) ?>"></script>
  <?php
  $path = '';
  include 'includes/footer.php';
  ?>
