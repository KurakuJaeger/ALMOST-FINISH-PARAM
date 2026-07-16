<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/../../src/services/email-service.php';

$currentUser = requireLoginOrRedirect();
ensureSessionStarted();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('store/Profile.php');
}

$notice = ['type' => 'error', 'message' => 'The password could not be updated.'];

if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
    $notice['message'] = 'Your form session expired. Please try again.';
} else {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['confirm_password'] ?? '');

    if (!User::verifyPassword($currentUser, $currentPassword)) {
        $notice['message'] = 'Your current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $notice['message'] = 'Your new password must contain at least eight characters.';
    } elseif ($newPassword !== $confirmation) {
        $notice['message'] = 'The new password confirmation does not match.';
    } elseif (User::verifyPassword($currentUser, $newPassword)) {
        $notice['message'] = 'Choose a password different from your current password.';
    } else {
        try {
            User::setPassword((int) $currentUser['user_id'], $newPassword);
            $notice = ['type' => 'success', 'message' => 'Your password was updated successfully.'];

            try {
                $recipientName = trim($currentUser['first_name'] . ' ' . $currentUser['last_name']);
                $emailSent = EmailService::sendPasswordChangedNotification($currentUser['email'], $recipientName);
                if (!$emailSent) {
                    $notice['message'] .= ' The security email could not be delivered.';
                }
            } catch (Throwable) {
                $notice['message'] .= ' The security email could not be delivered.';
            }
        } catch (Throwable) {
            $notice['message'] = 'We could not update your password right now. Please try again shortly.';
        }
    }
}

$_SESSION['profile_password_notice'] = $notice;
redirectTo('store/Profile.php?section=password');
