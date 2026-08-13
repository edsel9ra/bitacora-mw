<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/bitacora.php';
require_once __DIR__ . '/../config/bitacora_drafts.php';
require_once __DIR__ . '/../bd/conexion.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function bit_draft_response(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bit_draft_request_data(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_starts_with($contentType, 'application/json')) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_json', 'message' => 'El cuerpo JSON es obligatorio.']);
    }
    try {
        $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_json', 'message' => 'El cuerpo JSON no es válido.']);
    }
    if (!is_array($data)) {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_json', 'message' => 'El cuerpo JSON debe ser un objeto.']);
    }
    return $data;
}

function bit_draft_request_int(array $request, string $name, int $default = 0): int
{
    if (!array_key_exists($name, $request) || $request[$name] === '') {
        return $default;
    }
    $value = $request[$name];
    if ((is_int($value) && $value >= 0) || (is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
        return (int) $value;
    }
    bit_draft_response(400, ['ok' => false, 'code' => 'invalid_request', 'message' => 'El campo ' . $name . ' no es válido.']);
}

function bit_draft_request_bool(array $request, string $name): bool
{
    $value = $request[$name] ?? false;
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) && in_array($value, [0, 1], true)) {
        return $value === 1;
    }
    if (is_string($value)) {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($parsed !== null) {
            return $parsed;
        }
    }
    bit_draft_response(400, ['ok' => false, 'code' => 'invalid_request', 'message' => 'El campo ' . $name . ' no es válido.']);
}

function bit_draft_request_token(array $request, bool $required): string
{
    $token = trim((string) ($request['token'] ?? ''));
    if ($token === '' && !$required) {
        return '';
    }
    if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_token', 'message' => 'El token del borrador no es válido.']);
    }
    return $token;
}

function bit_draft_request_payload(array $request): array
{
    if (!array_key_exists('payload', $request)) {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_payload', 'message' => 'El payload es obligatorio.']);
    }
    $payload = $request['payload'];
    if (is_string($payload)) {
        if (strlen($payload) > bit_draft_max_bytes()) {
            bit_draft_response(413, ['ok' => false, 'code' => 'payload_too_large', 'message' => 'El borrador excede el tamaño máximo permitido.']);
        }
        try {
            $payload = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            bit_draft_response(400, ['ok' => false, 'code' => 'invalid_payload', 'message' => 'El payload JSON no es válido.']);
        }
    }
    if (!is_array($payload)) {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_payload', 'message' => 'El payload debe ser un objeto JSON.']);
    }
    return $payload;
}

app_require_post_login(null, true);

$request = bit_draft_request_data();
$empresaId = bit_draft_request_int($request, 'empresa_id');
if ($empresaId <= 0) {
    bit_draft_response(400, ['ok' => false, 'code' => 'invalid_empresa', 'message' => 'La empresa explícita es obligatoria.']);
}

if (!app_is_admin() && (int) ($_SESSION['s_idEmpresa'] ?? 0) !== $empresaId) {
    bit_draft_response(403, ['ok' => false, 'code' => 'invalid_empresa', 'message' => 'No autorizado para esta empresa.']);
}

$userId = (int) ($_SESSION['s_usuario_id'] ?? 0);
if ($userId <= 0) {
    bit_draft_response(403, ['ok' => false, 'code' => 'invalid_owner', 'message' => 'La sesión no tiene un propietario válido.']);
}

$companyConfig = app_bitacora_config($empresaId);
if ($companyConfig === null) {
    bit_draft_response(400, ['ok' => false, 'code' => 'invalid_empresa', 'message' => 'La empresa no tiene un formulario configurado.']);
}
$type = (string) ($companyConfig['type'] ?? '');
if (!in_array($type, ['operational', 'supervision'], true)) {
    bit_draft_response(400, ['ok' => false, 'code' => 'invalid_form_type', 'message' => 'El tipo de formulario configurado no es válido.']);
}
$action = strtolower(trim((string) ($request['action'] ?? '')));
if (!in_array($action, ['load', 'save', 'delete'], true)) {
    bit_draft_response(400, ['ok' => false, 'code' => 'invalid_action', 'message' => 'La acción solicitada no es válida.']);
}

$sections = app_bitacora_form_sections($empresaId, $companyConfig);
$schemaHash = bit_draft_schema_hash($sections);

try {
    $pdo = Conexion::Conectar();

    if ($action === 'load') {
        $stmt = $pdo->prepare('
            SELECT token, schema_hash, ciphertext, iv, tag, key_version, version, actualizado_en, expires_at
            FROM bitacora_borradores
            WHERE idUsuario = :user_id
              AND idEmpresa = :empresa_id
              AND tipo_formulario = :type
              AND expires_at > UTC_TIMESTAMP()
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId, 'empresa_id' => $empresaId, 'type' => $type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            bit_draft_response(200, ['ok' => true, 'exists' => false, 'payload' => null, 'type' => $type]);
        }
        $token = (string) $row['token'];
        $keyVersion = (int) $row['key_version'];
        $plaintext = bit_draft_decrypt(
            (string) $row['ciphertext'],
            (string) $row['iv'],
            (string) $row['tag'],
            bit_draft_aad($userId, $empresaId, $type, $token, (string) $row['schema_hash'], $keyVersion),
            bit_draft_key_for_version($keyVersion)
        );
        $payload = json_decode($plaintext, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new RuntimeException('El borrador descifrado no es un objeto.');
        }
        $schemaChanged = !hash_equals((string) $row['schema_hash'], $schemaHash);
        $omittedFields = [];
        if ($schemaChanged) {
            [$payload, $omittedFields] = bit_draft_sanitize_payload_compatible($payload, $sections, $companyConfig);
        } else {
            $payload = bit_draft_sanitize_payload($payload, $sections, $companyConfig);
        }

        bit_draft_response(200, [
            'ok' => true,
            'exists' => true,
            'payload' => $payload,
            'token' => $token,
            'version' => (int) $row['version'],
            'type' => $type,
            'schema_hash' => (string) $row['schema_hash'],
            'current_schema_hash' => $schemaHash,
            'schemaChanged' => $schemaChanged,
            'omittedFields' => $omittedFields,
            'updatedAt' => bit_draft_iso_utc((string) $row['actualizado_en']),
            'expiresAt' => bit_draft_iso_utc((string) $row['expires_at']),
        ]);
    }

    if ($action === 'save') {
        $payload = bit_draft_sanitize_payload(bit_draft_request_payload($request), $sections, $companyConfig);
        $plaintext = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($plaintext) > bit_draft_max_bytes()) {
            bit_draft_response(413, ['ok' => false, 'code' => 'payload_too_large', 'message' => 'El borrador excede el tamaño máximo permitido.']);
        }

        $requestToken = bit_draft_request_token($request, false);
        $expectedVersion = bit_draft_request_int($request, 'expected_version');
        $force = bit_draft_request_bool($request, 'force');
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('+' . bit_draft_ttl_days() . ' days')
            ->format('Y-m-d H:i:s');

        $pdo->beginTransaction();
        $deleteExpired = $pdo->prepare('
            DELETE FROM bitacora_borradores
            WHERE idUsuario = :user_id AND idEmpresa = :empresa_id AND tipo_formulario = :type AND expires_at <= UTC_TIMESTAMP()
        ');
        $deleteExpired->execute(['user_id' => $userId, 'empresa_id' => $empresaId, 'type' => $type]);

        $find = $pdo->prepare('
            SELECT id, token, version
            FROM bitacora_borradores
            WHERE idUsuario = :user_id AND idEmpresa = :empresa_id AND tipo_formulario = :type
            LIMIT 1 FOR UPDATE
        ');
        $find->execute(['user_id' => $userId, 'empresa_id' => $empresaId, 'type' => $type]);
        $existing = $find->fetch(PDO::FETCH_ASSOC);

        if ($existing === false) {
            if ($requestToken !== '' || $expectedVersion !== 0) {
                $pdo->rollBack();
                bit_draft_response(409, ['ok' => false, 'code' => 'draft_conflict', 'message' => 'El borrador ya no está disponible. Recarga antes de guardar.']);
            }
            $token = bin2hex(random_bytes(32));
            $encrypted = bit_draft_encrypt($plaintext, bit_draft_aad($userId, $empresaId, $type, $token, $schemaHash));
            $insert = $pdo->prepare('
                INSERT INTO bitacora_borradores
                    (token, idUsuario, idEmpresa, tipo_formulario, schema_hash, ciphertext, iv, tag, key_version, version, creado_en, actualizado_en, expires_at)
                VALUES
                    (:token, :user_id, :empresa_id, :type, :schema_hash, :ciphertext, :iv, :tag, :key_version, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP(), :expires_at)
            ');
            try {
                $insert->execute([
                    'token' => $token,
                    'user_id' => $userId,
                    'empresa_id' => $empresaId,
                    'type' => $type,
                    'schema_hash' => $schemaHash,
                    'ciphertext' => $encrypted['ciphertext'],
                    'iv' => $encrypted['iv'],
                    'tag' => $encrypted['tag'],
                    'key_version' => $encrypted['key_version'],
                    'expires_at' => $expiresAt,
                ]);
            } catch (PDOException $e) {
                if ((string) $e->getCode() !== '23000') {
                    throw $e;
                }
                $pdo->rollBack();
                bit_draft_response(409, ['ok' => false, 'code' => 'draft_conflict', 'message' => 'Ya existe un borrador para este formulario.']);
            }
            $version = 1;
        } else {
            $token = (string) $existing['token'];
            $currentVersion = (int) $existing['version'];
            if ((!$force && $requestToken !== '' && !hash_equals($token, $requestToken)) || $expectedVersion !== $currentVersion) {
                $pdo->rollBack();
                bit_draft_response(409, ['ok' => false, 'code' => 'draft_conflict', 'message' => 'El borrador fue modificado por otra solicitud.', 'current_version' => $currentVersion]);
            }

            $encrypted = bit_draft_encrypt($plaintext, bit_draft_aad($userId, $empresaId, $type, $token, $schemaHash));
            $sql = '
                UPDATE bitacora_borradores
                SET schema_hash = :schema_hash, ciphertext = :ciphertext, iv = :iv, tag = :tag,
                    key_version = :key_version, version = version + 1, actualizado_en = UTC_TIMESTAMP(), expires_at = :expires_at
                WHERE id = :id AND version = :expected_version';
            $update = $pdo->prepare($sql);
            $params = [
                'schema_hash' => $schemaHash,
                'ciphertext' => $encrypted['ciphertext'],
                'iv' => $encrypted['iv'],
                'tag' => $encrypted['tag'],
                'key_version' => $encrypted['key_version'],
                'expires_at' => $expiresAt,
                'id' => (int) $existing['id'],
                'expected_version' => $expectedVersion,
            ];
            $update->execute($params);
            if ($update->rowCount() !== 1) {
                $pdo->rollBack();
                bit_draft_response(409, ['ok' => false, 'code' => 'draft_conflict', 'message' => 'El borrador fue modificado por otra solicitud.']);
            }
            $version = $currentVersion + 1;
        }

        $pdo->commit();
        bit_draft_response(200, [
            'ok' => true,
            'token' => $token,
            'version' => $version,
            'type' => $type,
            'schema_hash' => $schemaHash,
            'updatedAt' => gmdate('Y-m-d\TH:i:s\Z'),
            'expiresAt' => bit_draft_iso_utc($expiresAt),
        ]);
    }

    $token = bit_draft_request_token($request, true);
    $expectedVersion = bit_draft_request_int($request, 'expected_version');
    if ($expectedVersion <= 0) {
        bit_draft_response(400, ['ok' => false, 'code' => 'invalid_version', 'message' => 'La versión del borrador es obligatoria.']);
    }
    $delete = $pdo->prepare('
        DELETE FROM bitacora_borradores
        WHERE idUsuario = :user_id AND idEmpresa = :empresa_id AND tipo_formulario = :type
          AND token = :token AND version = :version
    ');
    $delete->execute([
        'user_id' => $userId,
        'empresa_id' => $empresaId,
        'type' => $type,
        'token' => $token,
        'version' => $expectedVersion,
    ]);
    if ($delete->rowCount() !== 1) {
        bit_draft_response(409, ['ok' => false, 'code' => 'draft_conflict', 'message' => 'El token o la versión del borrador no coincide.']);
    }
    bit_draft_response(200, ['ok' => true, 'deleted' => true, 'type' => $type]);
} catch (InvalidArgumentException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    bit_draft_response(422, ['ok' => false, 'code' => 'invalid_payload', 'message' => $e->getMessage()]);
} catch (JsonException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    bit_draft_response(422, ['ok' => false, 'code' => 'invalid_stored_draft', 'message' => 'El borrador almacenado no es válido.']);
} catch (RuntimeException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error de configuración o autenticación criptográfica de borradores: ' . $e->getMessage());
    bit_draft_response(503, ['ok' => false, 'code' => 'draft_unavailable', 'message' => 'El servicio de borradores no está disponible.']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error interno del servicio de borradores: ' . get_class($e));
    bit_draft_response(500, ['ok' => false, 'code' => 'internal_error', 'message' => 'No fue posible procesar el borrador.']);
}
