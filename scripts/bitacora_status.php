<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/config/bitacora_drafts.php';
require_once __DIR__ . '/../public/bd/conexion.php';

try {
    $pdo = Conexion::Conectar();
    $queue = $pdo->query('SELECT estado, COUNT(*) total, MIN(creado_en) oldest FROM bitacora_email_queue GROUP BY estado')->fetchAll(PDO::FETCH_ASSOC);
    $drafts = $pdo->query('SELECT key_version, COUNT(*) total, MIN(expires_at) next_expiration FROM bitacora_borradores GROUP BY key_version')->fetchAll(PDO::FETCH_ASSOC);
    $expiredDrafts = (int) $pdo->query('SELECT COUNT(*) FROM bitacora_borradores WHERE expires_at <= UTC_TIMESTAMP()')->fetchColumn();
    $staleLocks = (int) $pdo->query("SELECT COUNT(*) FROM bitacora_email_queue WHERE estado = 'procesando' AND locked_at < UTC_TIMESTAMP() - INTERVAL 30 MINUTE")->fetchColumn();
    $submissionClaims = $pdo->query("SELECT
        SUM(delivery_started_at IS NULL) unstarted,
        SUM(delivery_started_at IS NOT NULL) delivery_unknown,
        MIN(creado_en) oldest
        FROM bitacora_envios
        WHERE submission_key IS NOT NULL AND response_json IS NULL")->fetch(PDO::FETCH_ASSOC);
    $submissionClaims = [
        'unstarted' => (int) ($submissionClaims['unstarted'] ?? 0),
        'delivery_unknown' => (int) ($submissionClaims['delivery_unknown'] ?? 0),
        'oldest' => $submissionClaims['oldest'] ?? null,
    ];
    $storage = rtrim((string) app_env('BITACORA_STORAGE_PATH', __DIR__ . '/../storage/bitacoras_pdf'), '/\\');

    echo json_encode([
        'generatedAt' => gmdate('Y-m-d\TH:i:s\Z'),
        'draftActiveKeyVersion' => bit_draft_active_key_version(),
        'draftsByKeyVersion' => $drafts,
        'expiredDrafts' => $expiredDrafts,
        'emailQueueByState' => $queue,
        'staleEmailLocks' => $staleLocks,
        'unfinishedSubmissionClaims' => $submissionClaims,
        'storageFreeBytes' => is_dir($storage) ? disk_free_space($storage) : false,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'No fue posible consultar el estado operativo: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
