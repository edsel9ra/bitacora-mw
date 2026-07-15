<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function app_configure_mailer(PHPMailer $mail, string $fromName = 'Bitácora Mister Wings'): void
{
    $host = app_env('SMTP_HOST');
    $user = app_env('SMTP_USER');
    $password = app_env('SMTP_PASSWORD');
    $from = app_env('SMTP_FROM', $user);

    if ($host === null || $user === null || $password === null || $from === null) {
        throw new RuntimeException('Configuración SMTP incompleta. Revisa SMTP_HOST, SMTP_USER, SMTP_PASSWORD y SMTP_FROM.');
    }

    $port = app_env_int('SMTP_PORT', 465);
    $secure = strtolower((string) app_env('SMTP_SECURE', $port === 465 ? 'ssl' : 'tls'));

    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $password;
    $mail->Port = $port;

    if (in_array($secure, ['ssl', 'smtps'], true)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif (in_array($secure, ['tls', 'starttls'], true)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = '';
        $mail->SMTPAutoTLS = false;
    }

    if (!app_env_bool('SMTP_VERIFY_TLS', true)) {
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
