<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/bitacora.php';
require_once __DIR__ . '/bitacora_helpers.php';

app_start_session();
$empresaId = (int) ($_SESSION['s_idEmpresa'] ?? 0);
app_require_post_login($empresaId, true);

header('Content-Type: application/json; charset=UTF-8');

$companyConfig = app_bitacora_config($empresaId);
if ($companyConfig === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Empresa sin configuración de bitácora.']);
    exit;
}

function bit_json_response(bool $ok, string $message, array $extra = []): void
{
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

function bit_required_fields_present(array $fields): bool
{
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';
        if (is_array($value)) {
            if ($value === []) {
                return false;
            }
            continue;
        }

        if (trim((string) $value) === '') {
            return false;
        }
    }

    return true;
}

function bit_download_url(array $pdfInfo): string
{
    return '../scripts/download_bitacora.php?empresa=' . urlencode((string) $pdfInfo['empresaId']) .
        '&year=' . urlencode($pdfInfo['year']) .
        '&month=' . urlencode($pdfInfo['month']) .
        '&file=' . urlencode($pdfInfo['fileName']);
}

function bit_validate_dynamic_fields(array $fields): array
{
    foreach ($fields as $field) {
        $type = (string) ($field['type'] ?? 'text');
        $name = (string) ($field['name'] ?? '');
        $label = (string) ($field['label'] ?? $name);
        $required = (bool) ($field['required'] ?? false);
        $value = $_POST[$name] ?? '';

        if (is_array($value)) {
            $value = array_filter($value, static fn($v) => trim((string) $v) !== '');
        } else {
            $value = trim((string) $value);
        }

        if ($required && ($value === '' || $value === [])) {
            return [false, 'El campo "' . $label . '" es obligatorio.'];
        }

        if ($value === '' || $value === []) {
            continue;
        }

        if ($type === 'number' && !is_numeric(str_replace(',', '.', (string) $value))) {
            return [false, 'El campo "' . $label . '" debe ser numérico.'];
        }

        if ($type === 'date' && strtotime((string) $value) === false) {
            return [false, 'El campo "' . $label . '" debe ser una fecha válida.'];
        }

        if ($type === 'time' && !preg_match('/^\d{2}:\d{2}$/', (string) $value)) {
            return [false, 'El campo "' . $label . '" debe ser una hora válida.'];
        }

        if ($type === 'select') {
            $allowed = array_map('strval', (array) ($field['options'] ?? []));
            if ($allowed !== [] && !in_array((string) $value, $allowed, true)) {
                return [false, 'El campo "' . $label . '" tiene una opción inválida.'];
            }
        }

        if ($type === 'yes_no' && !in_array((string) $value, ['Si', 'No'], true)) {
            return [false, 'El campo "' . $label . '" debe ser Si o No.'];
        }
    }

    return [true, ''];
}

function bit_handle_supervision(int $empresaId, array $companyConfig): void
{
    $fields = ['fechasup', 'horasup', 'sede', 'area', 'responsableb', 'hallazgos', 'ryc', 'tappv', 'pasc', 'actsup'];
    if (!bit_required_fields_present($fields)) {
        bit_json_response(false, 'No puedes dejar campos vacíos, todos los campos son obligatorios.');
    }

    $data = [];
    foreach ($fields as $field) {
        $data[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $timestamp = strtotime($data['fechasup']);
    if ($timestamp !== false) {
        $data['fechasup'] = date('d-m-Y', $timestamp);
    }

    $e = static function ($value): string {
        return nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
    };

    $body = "
        <h2>Información de Supervisión</h2>
        <p><strong>Fecha de supervisión: </strong>{$e($data['fechasup'])}</p>
        <p><strong>Horario de supervisión: </strong>{$e($data['horasup'])}</p>
        <p><strong>Sede: </strong>{$e($data['sede'])}</p>
        <p><strong>Área: </strong>{$e($data['area'])}</p>
        <p><strong>Supervisor y Cargo: </strong>{$e($data['responsableb'])}</p>
        <h2>Resumen de supervisión</h2>
        <ul>
            <li><strong>Hallazgos encontrados: </strong>{$e($data['hallazgos'])}</li>
            <li><strong>Retroalimentaciones: </strong>{$e($data['ryc'])}</li>
            <li><strong>Tareas asignadas para próxima visita: </strong>{$e($data['tappv'])}</li>
            <li><strong>Plan de acción y/o recomendaciones: </strong>{$e($data['pasc'])}</li>
            <li><strong>Otras actividades realizadas por el supervisor: </strong>{$e($data['actsup'])}</li>
        </ul>";

    try {
        $mail = new PHPMailer(true);
        app_configure_mailer($mail, 'Supervisión Mister Wings');
        app_bitacora_add_recipients($mail, $empresaId, $data['sede']);
        $mail->Subject = 'Reporte de Supervisión ' . preg_replace('/[\r\n]+/', ' ', $data['sede']);
        $mail->Body = $body;
        $mail->send();

        bit_json_response(true, 'El reporte de supervisión fue enviado correctamente.', [
            'correoEnviado' => true,
            'pdfGenerado' => false,
            'downloadUrl' => null,
        ]);
    } catch (Throwable $e) {
        error_log('Error enviando reporte de supervisión: ' . $e->getMessage());
        bit_json_response(false, 'No se pudo enviar el reporte de supervisión.');
    }
}

function bit_handle_operational(int $empresaId): void
{
    $action = trim((string) ($_POST['bitacora_action'] ?? 'send'));
    $generatePdfOnly = $action === 'generate_pdf';

    if (!bit_required_fields_present(['fechab', 'sede', 'responsable', 'cargo'])) {
        bit_json_response(false, 'Fecha, sede, responsable y cargo son obligatorios.');
    }

    $sede = trim((string) ($_POST['sede'] ?? ''));
    $defaults = bit_get_default_texts();
    $config = bit_get_config($empresaId, $sede);
    $rules = bit_get_conditional_rules($defaults);

    [$validDynamic, $dynamicMessage] = bit_validate_dynamic_fields($config['dynamic_fields'] ?? []);
    if (!$validDynamic) {
        bit_json_response(false, $dynamicMessage);
    }

    bit_apply_conditional_defaults($_POST, $rules);
    bit_handle_planta_electrica($_POST, $defaults);

    $data = bit_normalize_data($_POST, $config);
    $html = bit_render_html($data, $config);
    $pdfHtml = bit_render_html($data, $config, true);
    $pdfInfo = null;
    $pdfGenerado = false;
    try {
        $pdfInfo = bit_build_pdf_info(
            $empresaId,
            $data['sede'] ?? '',
            $data['fecha'] ?? date('d-m-Y'),
            $data['responsable'] ?? 'SIN_RESPONSABLE'
        );
        bit_generate_pdf($pdfHtml, $pdfInfo['path']);
        $pdfGenerado = is_file($pdfInfo['path']);
    } catch (Throwable $e) {
        error_log('Error generando PDF bitácora unificada: ' . $e->getMessage());
    }

    $downloadUrl = ($pdfGenerado && $pdfInfo !== null) ? bit_download_url($pdfInfo) : null;

    if ($generatePdfOnly) {
        bit_json_response($pdfGenerado, $pdfGenerado ? 'PDF generado correctamente.' : 'No se pudo preparar o generar el PDF.', [
            'correoEnviado' => null,
            'pdfGenerado' => $pdfGenerado,
            'pdfFileName' => ($pdfGenerado && $pdfInfo !== null) ? $pdfInfo['fileName'] : null,
            'downloadUrl' => $downloadUrl,
            'pdfOnly' => true,
        ]);
    }

    $correoEnviado = false;
    try {
        $mail = new PHPMailer(true);
        app_configure_mailer($mail);
        app_bitacora_add_recipients($mail, $empresaId, $data['sede'] ?? '');
        $mail->Subject = 'BITÁCORA SEDE ' . preg_replace('/[\r\n]+/', ' ', (string) ($data['sede'] ?? ''));
        $mail->Body = $html;

        if ($pdfGenerado && $pdfInfo !== null) {
            $mail->addAttachment($pdfInfo['path'], $pdfInfo['fileName']);
        }

        $correoEnviado = $mail->send();
    } catch (Throwable $e) {
        error_log('Error enviando bitácora unificada: ' . $e->getMessage());
    }

    if ($correoEnviado && $pdfGenerado) {
        $message = 'La bitácora fue enviada correctamente y el PDF se generó con éxito.';
    } elseif (!$correoEnviado && $pdfGenerado) {
        $message = 'No se pudo enviar el correo, pero el PDF sí fue generado correctamente.';
    } elseif ($correoEnviado) {
        $message = 'El correo fue enviado, pero no se pudo generar el PDF.';
    } else {
        $message = 'No se pudo enviar el correo ni generar el PDF.';
    }

    bit_json_response(($correoEnviado || $pdfGenerado), $message, [
        'correoEnviado' => $correoEnviado,
        'pdfGenerado' => $pdfGenerado,
        'pdfFileName' => ($pdfGenerado && $pdfInfo !== null) ? $pdfInfo['fileName'] : null,
        'downloadUrl' => $downloadUrl,
    ]);
}

if (($companyConfig['type'] ?? '') === 'supervision') {
    bit_handle_supervision($empresaId, $companyConfig);
}

bit_handle_operational($empresaId);
