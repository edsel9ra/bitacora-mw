<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function app_configure_mailer(PHPMailer $mail, string $fromName = 'Bitácora Mister Wings'): void
{
    $host = app_env('SMTP_HOST');
    $user = app_env('SMTP_USER');
    $password = app_env('SMTP_PASSWORD');
    $from = app_env('SMTP_FROM', $user);
    $auth = app_env_bool('SMTP_AUTH', true);

    if ($host === null || $from === null || ($auth && ($user === null || $password === null))) {
        throw new RuntimeException('Configuración SMTP incompleta. Revisa SMTP_HOST, SMTP_FROM y las credenciales de autenticación.');
    }

    $port = app_env_int('SMTP_PORT', 465);
    $secure = strtolower((string) app_env('SMTP_SECURE', $port === 465 ? 'ssl' : 'tls'));
    $verifyTls = app_env_bool('SMTP_VERIFY_TLS', true);
    if (strtolower((string) app_env('APP_ENV', 'development')) === 'production' && ($secure === 'none' || !$verifyTls)) {
        throw new RuntimeException('La configuración SMTP de producción requiere TLS y verificación de certificados.');
    }

    // Avoid Docker hostnames with underscores in the SMTP HELO/EHLO command.
    $mail->Hostname = trim((string) app_env('SMTP_HELO_NAME', $host));

    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = $auth;
    if ($auth) {
        $mail->Username = (string) $user;
        $mail->Password = (string) $password;
    }
    $mail->Port = $port;
    $mail->Timeout = max(1, app_env_int('SMTP_TIMEOUT_SECONDS', 20));

    if (in_array($secure, ['ssl', 'smtps'], true)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif (in_array($secure, ['tls', 'starttls'], true)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($secure === 'none') {
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
    } else {
        throw new RuntimeException('SMTP_SECURE debe ser ssl, tls o none.');
    }

    if (!$verifyTls) {
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    $mail->setFrom($from, $fromName);
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
}
