<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/bitacora_helpers.php';

header('Content-Type: application/json; charset=UTF-8');
app_require_post_login(8, true);

if (!$_POST) {
    echo json_encode([
        'ok' => false,
        'message' => 'No se recibieron datos.'
    ]);
    exit;
}

function configureMailer(PHPMailer $mail): void
{
    app_configure_mailer($mail);
}

try {
    $empresaId = (int)($_SESSION['s_idEmpresa'] ?? 0);
    $sede      = trim((string)($_POST['sede'] ?? ''));

    $defaults = bit_get_default_texts();
    $config   = bit_get_config($empresaId, $sede);
    $rules    = bit_get_conditional_rules($defaults);

    bit_apply_conditional_defaults($_POST, $rules);
    bit_handle_planta_electrica($_POST, $defaults);

    $data = bit_normalize_data($_POST, $config);
    $html = bit_render_html($data, $config);

    // 1) GENERAR PDF SIEMPRE
    $pdfInfo = bit_build_pdf_info(
        $empresaId,
        $data['sede'] ?? '',
        $data['fecha'] ?? date('d-m-Y'),
        $data['responsable'] ?? 'SIN_RESPONSABLE'
    );

    $pdfGenerado = false;
    $pdfError    = null;

    try {
        bit_generate_pdf($html, $pdfInfo['path']);
        $pdfGenerado = file_exists($pdfInfo['path']);
    } catch (Throwable $e) {
        $pdfGenerado = false;
        $pdfError = $e->getMessage();
        error_log('Error generando PDF bitácora: ' . $e->getMessage());
    }

    // 2) ENVIAR CORREO
    $correoEnviado = false;
    $correoError   = null;

    try {
        $mail = new PHPMailer(true);
        configureMailer($mail);
        bit_add_recipients_by_sede($mail, $data['sede'] ?? '');

        $mail->Subject = 'BITÁCORA SEDE ' . ($data['sede'] ?? '');
        $mail->Body    = $html;

        if ($pdfGenerado && file_exists($pdfInfo['path'])) {
            $mail->addAttachment($pdfInfo['path'], $pdfInfo['fileName']);
        }

        $correoEnviado = $mail->send();
    } catch (Throwable $e) {
        $correoEnviado = false;
        $correoError = $e->getMessage();
        error_log('Error enviando bitácora por correo: ' . $e->getMessage());
    }

    // 3) URL DE DESCARGA
    $downloadUrl = null;
    if ($pdfGenerado) {
        $downloadUrl = 'download_bitacora.php?empresa=' . urlencode((string)$pdfInfo['empresaId']) .
            '&year=' . urlencode($pdfInfo['year']) .
            '&month=' . urlencode($pdfInfo['month']) .
            '&file=' . urlencode($pdfInfo['fileName']);
    }

    // 4) MENSAJE
    if ($correoEnviado && $pdfGenerado) {
        $message = 'La bitácora fue enviada correctamente y el PDF de contingencia se generó con éxito.';
    } elseif (!$correoEnviado && $pdfGenerado) {
        $message = 'No se pudo enviar el correo, pero el PDF de contingencia sí fue generado correctamente.';
    } elseif ($correoEnviado && !$pdfGenerado) {
        $message = 'El correo fue enviado, pero no se pudo generar el PDF de contingencia.';
    } else {
        $message = 'No se pudo enviar el correo ni generar el PDF de contingencia.';
    }

    echo json_encode([
        'ok'            => ($correoEnviado || $pdfGenerado),
        'correoEnviado' => $correoEnviado,
        'pdfGenerado'   => $pdfGenerado,
        'pdfFileName'   => $pdfGenerado ? $pdfInfo['fileName'] : null,
        'downloadUrl'   => $downloadUrl,
        'message'       => $message,
        'debug' => [
            'pdfError'    => $pdfError,
            'correoError' => $correoError
        ]
    ]);
    exit;

} catch (Throwable $e) {
    error_log('Error general bitácora: ' . $e->getMessage());

    echo json_encode([
        'ok' => false,
        'message' => 'Se presentó un error inesperado al procesar la bitácora.'
    ]);
    exit;
}
