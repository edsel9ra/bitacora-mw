<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/mailer.php';
require_once __DIR__ . '/../config/bitacora.php';
require_once __DIR__ . '/../config/bitacora_drafts.php';
require_once __DIR__ . '/bitacora_helpers.php';
require_once __DIR__ . '/bitacora_validation_helpers.php';
require_once __DIR__ . '/bitacora_mail_helpers.php';
require_once __DIR__ . '/bitacora_submission_helpers.php';

app_start_session();
if (app_is_admin()) {
    app_require_post_login(null, true);
    $postedEmpresaId = (int) ($_POST['empresa_id'] ?? 0);
    if ($postedEmpresaId > 0) {
        if (app_bitacora_config($postedEmpresaId) === null) {
            app_fail_request('Empresa inválida.', 400, true);
        }
        app_set_admin_empresa_id($postedEmpresaId);
    }
    $empresaId = app_current_empresa_id();
} else {
    $empresaId = app_current_empresa_id();
    app_require_post_login($empresaId, true);
}

header('Content-Type: application/json; charset=UTF-8');

$companyConfig = app_bitacora_config($empresaId);
if ($companyConfig === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Empresa sin configuración de bitácora.']);
    exit;
}

$draftRequired = true;

if (($companyConfig['type'] ?? '') === 'supervision') {
    $draftContext = bit_submission_draft_context($empresaId, 'supervision', $draftRequired);
    bit_handle_supervision($empresaId, $companyConfig, $draftContext);
}

$draftContext = bit_submission_draft_context($empresaId, 'operational', $draftRequired);
bit_handle_operational($empresaId, $draftContext);
