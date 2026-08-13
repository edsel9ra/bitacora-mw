<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/config/bitacora_drafts.php';
require_once __DIR__ . '/../public/scripts/bitacora_helpers.php';
require_once __DIR__ . '/../public/scripts/bitacora_submission_helpers.php';
require_once __DIR__ . '/../public/bd/conexion.php';

function integration_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function integration_insert_draft(PDO $pdo, int $userId, string $token, int $version = 1): void
{
    $schemaHash = hash('sha256', 'integration-schema');
    $keyVersion = bit_draft_active_key_version();
    $encrypted = bit_draft_encrypt(
        '{"sede":"PANCE"}',
        bit_draft_aad($userId, 8, 'operational', $token, $schemaHash, $keyVersion)
    );
    $stmt = $pdo->prepare('
        INSERT INTO bitacora_borradores
            (token, idUsuario, idEmpresa, tipo_formulario, schema_hash, ciphertext, iv, tag, key_version, version, expires_at)
        VALUES
            (:token, :user_id, 8, \'operational\', :schema_hash, :ciphertext, :iv, :tag, :key_version, :version, UTC_TIMESTAMP() + INTERVAL 1 DAY)
    ');
    $stmt->execute([
        'token' => $token,
        'user_id' => $userId,
        'schema_hash' => $schemaHash,
        'ciphertext' => $encrypted['ciphertext'],
        'iv' => $encrypted['iv'],
        'tag' => $encrypted['tag'],
        'key_version' => $encrypted['key_version'],
        'version' => $version,
    ]);
}

$pdo = Conexion::Conectar();
$suffix = bin2hex(random_bytes(5));
$username = 'int_' . $suffix;
$submissionKeys = [];
$userId = 0;

try {
    $insertUser = $pdo->prepare('
        INSERT INTO usuarios_login (nombre, usuario, email, password, idEmpresa, rol, fecha_creado, idSede)
        VALUES (\'Integration\', :usuario, :email, :password, 8, \'usuario\', UTC_TIMESTAMP(), 2)
    ');
    $insertUser->execute([
        'usuario' => $username,
        'email' => $username . '@example.test',
        'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();

    $token = bin2hex(random_bytes(32));
    integration_insert_draft($pdo, $userId, $token);
    $context = [
        'token' => $token,
        'version' => 1,
        'user_id' => $userId,
        'empresa_id' => 8,
        'type' => 'operational',
        'submission_key' => hash('sha256', 'accepted-' . $suffix),
        'request_hash' => hash('sha256', 'request-a'),
    ];
    $submissionKeys[] = $context['submission_key'];

    $pdo->beginTransaction();
    $envioId = bit_submission_claim_envio($pdo, 8, 'PANCE', '2026-08-03', 'Integration', $username, 'operational', $context);
    bit_enqueue_email($envioId, 8, 'PANCE', $username, 'Integration', '<p>Integration</p>', ['to' => ['integration@example.test'], 'cc' => [], 'bcc' => []], [], $pdo);
    $response = bit_submission_complete($pdo, $envioId, $context, [
        'estado' => 'pendiente',
        'correo_enviado' => false,
        'pdf_generado' => false,
    ], true, 'Aceptado', ['correoEnviado' => null, 'correoEncolado' => true]);
    $pdo->commit();

    integration_assert($response['draftFinalized'] === true, 'La transacción no finalizó el borrador exacto.');
    integration_assert((int) $pdo->query('SELECT COUNT(*) FROM bitacora_borradores WHERE idUsuario = ' . $userId)->fetchColumn() === 0, 'El borrador aceptado sigue almacenado.');
    integration_assert((int) $pdo->query('SELECT COUNT(*) FROM bitacora_email_queue WHERE idEnvio = ' . $envioId)->fetchColumn() === 1, 'La cola aceptada no quedó registrada.');
    $storedResponse = $pdo->query('SELECT response_json FROM bitacora_envios WHERE id = ' . $envioId)->fetchColumn();
    integration_assert(is_string($storedResponse) && json_decode($storedResponse, true, 64, JSON_THROW_ON_ERROR) === $response, 'La respuesta idempotente almacenada cambió.');

    $duplicateRejected = false;
    $pdo->beginTransaction();
    try {
        bit_submission_claim_envio($pdo, 8, 'PANCE', '2026-08-03', 'Integration', $username, 'operational', $context);
    } catch (PDOException $e) {
        $duplicateRejected = (string) $e->getCode() === '23000';
    } finally {
        $pdo->rollBack();
    }
    integration_assert($duplicateRejected, 'La clave idempotente permitió un segundo envío.');

    $rollbackToken = bin2hex(random_bytes(32));
    integration_insert_draft($pdo, $userId, $rollbackToken, 2);
    $rollbackContext = [
        'token' => $rollbackToken,
        'version' => 2,
        'user_id' => $userId,
        'empresa_id' => 8,
        'type' => 'operational',
        'submission_key' => hash('sha256', 'rollback-' . $suffix),
        'request_hash' => hash('sha256', 'request-b'),
    ];
    $submissionKeys[] = $rollbackContext['submission_key'];

    integration_assert(bit_finalize_submission_draft(array_merge($rollbackContext, ['user_id' => $userId + 1])) === false, 'Otro usuario pudo finalizar el borrador.');
    integration_assert(bit_finalize_submission_draft(array_merge($rollbackContext, ['version' => 1])) === false, 'Una versión obsoleta pudo finalizar el borrador.');

    $pdo->beginTransaction();
    $rollbackEnvioId = bit_submission_claim_envio($pdo, 8, 'PANCE', '2026-08-03', 'Integration', $username, 'operational', $rollbackContext);
    bit_enqueue_email($rollbackEnvioId, 8, 'PANCE', $username, 'Rollback', '<p>Rollback</p>', ['to' => ['integration@example.test'], 'cc' => [], 'bcc' => []], [], $pdo);
    integration_assert(bit_finalize_submission_draft($rollbackContext, $pdo), 'No se pudo eliminar el borrador dentro de la transacción de prueba.');
    $pdo->rollBack();

    $checkDraft = $pdo->prepare('SELECT COUNT(*) FROM bitacora_borradores WHERE token = :token AND version = 2');
    $checkDraft->execute(['token' => $rollbackToken]);
    integration_assert((int) $checkDraft->fetchColumn() === 1, 'El rollback no restauró el borrador.');
    $checkRollback = $pdo->prepare('SELECT COUNT(*) FROM bitacora_envios WHERE submission_key = :submission_key');
    $checkRollback->execute(['submission_key' => $rollbackContext['submission_key']]);
    integration_assert((int) $checkRollback->fetchColumn() === 0, 'El rollback dejó una reserva de envío.');

    $staleContext = [
        'submission_key' => hash('sha256', 'stale-claim-' . $suffix),
        'request_hash' => hash('sha256', 'request-c'),
    ];
    $submissionKeys[] = $staleContext['submission_key'];
    $pdo->beginTransaction();
    $staleEnvioId = bit_submission_claim_envio($pdo, 8, 'PANCE', '2026-08-03', 'Integration', $username, 'operational', $staleContext);
    $pdo->commit();
    $pdo->prepare('UPDATE bitacora_envios SET creado_en = UTC_TIMESTAMP() - INTERVAL 10 MINUTE WHERE id = :id')->execute(['id' => $staleEnvioId]);
    integration_assert(!$pdo->inTransaction(), 'La conexión quedó en una transacción antes de recuperar la reserva.');
    integration_assert(bit_submission_replay_existing($staleContext, $pdo) === false, 'Una reserva no iniciada expirada no fue recuperable.');
    $checkRollback->execute(['submission_key' => $staleContext['submission_key']]);
    integration_assert((int) $checkRollback->fetchColumn() === 0, 'La reserva no iniciada expirada siguió bloqueada.');

    echo "Integration tests OK\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($submissionKeys !== []) {
        $placeholders = implode(',', array_fill(0, count($submissionKeys), '?'));
        $findEnvios = $pdo->prepare('SELECT id FROM bitacora_envios WHERE submission_key IN (' . $placeholders . ')');
        $findEnvios->execute($submissionKeys);
        $envioIds = array_map('intval', $findEnvios->fetchAll(PDO::FETCH_COLUMN));
        if ($envioIds !== []) {
            $idPlaceholders = implode(',', array_fill(0, count($envioIds), '?'));
            $pdo->prepare('DELETE FROM bitacora_email_queue WHERE idEnvio IN (' . $idPlaceholders . ')')->execute($envioIds);
            $pdo->prepare('DELETE FROM bitacora_envios WHERE id IN (' . $idPlaceholders . ')')->execute($envioIds);
        }
    }
    if ($userId > 0) {
        $pdo->prepare('DELETE FROM usuarios_login WHERE id = :id')->execute(['id' => $userId]);
    }
}
