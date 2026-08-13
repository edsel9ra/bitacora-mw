<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../public/config/mailer.php';

$recipient = (string) app_env('BITACORA_SMOKE_MAIL_TO', 'test@localhost.test');
if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "BITACORA_SMOKE_MAIL_TO no es una dirección válida.\n");
    exit(1);
}

try {
    $mail = new PHPMailer(true);
    app_configure_mailer($mail, 'Bitácora Smoke Test');
    $mail->addAddress($recipient);
    $mail->Subject = 'Prueba SMTP de Bitácora';
    $mail->Body = '<p>La configuración SMTP respondió correctamente.</p>';
    $mail->AltBody = 'La configuración SMTP respondió correctamente.';
    $mail->send();
    echo 'Correo de prueba enviado a ' . $recipient . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Falló la prueba SMTP: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
