<?php

/**
 * SMTP configuration helper.
 * Priority:
 * 1) Environment variables
 * 2) Hardcoded fallback defaults below
 *
 * NOTE: Replace fallback values before production use.
 */
function getSmtpConfig(): array
{
    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int)(getenv('SMTP_PORT') ?: 587);
    $username = getenv('SMTP_USERNAME') ?: '';
    $password = getenv('SMTP_PASSWORD') ?: '';
    $encryption = strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls'); // tls | ssl
    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: $username;
    $fromName = getenv('SMTP_FROM_NAME') ?: 'Navis HR System';

    return [
        'host' => $host,
        'port' => $port,
        'username' => $username,
        'password' => $password,
        'encryption' => $encryption,
        'from_email' => $fromEmail,
        'from_name' => $fromName,
    ];
}
