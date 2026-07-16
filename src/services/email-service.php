<?php

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    public static function sendAccountSetup(
        string $recipientEmail,
        string $recipientName,
        string $setupUrl
    ): bool {
        $mailSettings = mailConfig();

        if (!self::hasSmtpCredentials($mailSettings)) {
            return false;
        }

        $email = new PHPMailer(true);
        $email->isSMTP();
        $email->Host = $mailSettings['host'];
        $email->Port = $mailSettings['port'];
        $email->SMTPAuth = true;
        $email->Username = $mailSettings['username'];
        $email->Password = $mailSettings['password'];
        $email->SMTPSecure = $mailSettings['encryption'];
        $email->CharSet = PHPMailer::CHARSET_UTF8;

        $email->setFrom(
            $mailSettings['from_address'],
            $mailSettings['from_name']
        );
        $email->addAddress($recipientEmail, $recipientName);
        $email->isHTML(true);
        $email->Subject = 'Set up your Param account';
        $email->Body = self::buildHtmlMessage($recipientName, $setupUrl);
        $email->AltBody = self::buildPlainTextMessage($recipientName, $setupUrl);

        return $email->send();
    }

    public static function sendCustomerEmailVerification(
        string $recipientEmail,
        string $recipientName,
        string $verificationUrl
    ): bool {
        $mailSettings = mailConfig();

        if (!self::hasSmtpCredentials($mailSettings)) {
            return false;
        }

        $email = self::configuredMailer($mailSettings);
        $email->addAddress($recipientEmail, $recipientName);
        $email->isHTML(true);
        $email->Subject = 'Confirm your PARAM email address';
        $email->Body = self::buildVerificationHtmlMessage($recipientName, $verificationUrl);
        $email->AltBody = self::buildVerificationPlainTextMessage($recipientName, $verificationUrl);

        return $email->send();
    }

    public static function sendPasswordReset(
        string $recipientEmail,
        string $recipientName,
        string $resetUrl
    ): bool {
        $mailSettings = mailConfig();
        if (!self::hasSmtpCredentials($mailSettings)) {
            return false;
        }

        $email = self::configuredMailer($mailSettings);
        $email->addAddress($recipientEmail, $recipientName);
        $email->isHTML(true);
        $email->Subject = 'Reset your PARAM password';
        $email->Body = self::buildPasswordResetHtmlMessage($recipientName, $resetUrl);
        $email->AltBody = self::buildPasswordResetPlainTextMessage($recipientName, $resetUrl);

        return $email->send();
    }

    public static function sendPasswordChangedNotification(
        string $recipientEmail,
        string $recipientName
    ): bool {
        $mailSettings = mailConfig();
        if (!self::hasSmtpCredentials($mailSettings)) {
            return false;
        }

        $email = self::configuredMailer($mailSettings);
        $email->addAddress($recipientEmail, $recipientName);
        $email->isHTML(true);
        $email->Subject = 'Your PARAM password was changed';
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $email->Body = "<p>Hello {$safeName},</p>"
            . '<p>The password for your PARAM account was changed successfully.</p>'
            . '<p>If you did not make this change, contact the site administrator immediately.</p>';
        $email->AltBody = "Hello {$recipientName},\n\n"
            . "The password for your PARAM account was changed successfully.\n\n"
            . 'If you did not make this change, contact the site administrator immediately.';

        return $email->send();
    }

    private static function configuredMailer(array $mailSettings): PHPMailer
    {
        $email = new PHPMailer(true);
        $email->isSMTP();
        $email->Host = $mailSettings['host'];
        $email->Port = $mailSettings['port'];
        $email->SMTPAuth = true;
        $email->Username = $mailSettings['username'];
        $email->Password = $mailSettings['password'];
        $email->SMTPSecure = $mailSettings['encryption'];
        $email->CharSet = PHPMailer::CHARSET_UTF8;
        $email->setFrom($mailSettings['from_address'], $mailSettings['from_name']);

        return $email;
    }

    private static function hasSmtpCredentials(array $mailSettings): bool
    {
        return $mailSettings['host'] !== ''
            && $mailSettings['username'] !== ''
            && $mailSettings['password'] !== '';
    }

    private static function buildHtmlMessage(string $recipientName, string $setupUrl): string
    {
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8');

        return "<p>Hello {$safeName},</p>"
            . '<p>Your Param staff account is ready.</p>'
            . "<p><a href=\"{$safeUrl}\">Choose your password</a></p>"
            . '<p>This one-time link expires in 24 hours.</p>';
    }

    private static function buildPlainTextMessage(
        string $recipientName,
        string $setupUrl
    ): string {
        return "Hello {$recipientName},\n\n"
            . "Set up your account: {$setupUrl}\n\n"
            . 'This one-time link expires in 24 hours.';
    }

    private static function buildVerificationHtmlMessage(string $recipientName, string $verificationUrl): string
    {
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

        return "<p>Hello {$safeName},</p>"
            . '<p>Welcome to PARAM. Confirm your email address to activate your customer account.</p>'
            . "<p><a href=\"{$safeUrl}\">Confirm my email address</a></p>"
            . '<p>This one-time link expires in 24 hours. If you did not create this account, you can ignore this message.</p>';
    }

    private static function buildVerificationPlainTextMessage(string $recipientName, string $verificationUrl): string
    {
        return "Hello {$recipientName},\n\n"
            . "Confirm your PARAM email address: {$verificationUrl}\n\n"
            . 'This one-time link expires in 24 hours. If you did not create this account, you can ignore this message.';
    }

    private static function buildPasswordResetHtmlMessage(string $recipientName, string $resetUrl): string
    {
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        return "<p>Hello {$safeName},</p>"
            . '<p>We received a request to reset your PARAM password.</p>'
            . "<p><a href=\"{$safeUrl}\">Reset my password</a></p>"
            . '<p>This one-time link expires in one hour. If you did not request it, you can ignore this message.</p>';
    }

    private static function buildPasswordResetPlainTextMessage(string $recipientName, string $resetUrl): string
    {
        return "Hello {$recipientName},\n\n"
            . "Reset your PARAM password: {$resetUrl}\n\n"
            . 'This one-time link expires in one hour. If you did not request it, you can ignore this message.';
    }
}
