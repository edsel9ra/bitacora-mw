<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/bitacora_drafts.php';
require_once __DIR__ . '/bd/conexion.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = Conexion::Conectar();
    $pdo->query('SELECT 1')->fetchColumn();
    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    foreach (glob(__DIR__ . '/../database/migrations/*.sql') ?: [] as $migrationPath) {
        if (!in_array(basename($migrationPath), $applied, true)) {
            throw new RuntimeException('El esquema no está actualizado.');
        }
    }

    $storage = rtrim((string) app_env('BITACORA_STORAGE_PATH', __DIR__ . '/../storage/bitacoras_pdf'), '/\\');
    if (!is_dir($storage) || !is_writable($storage)) {
        throw new RuntimeException('El almacenamiento no está disponible.');
    }
    $probe = $storage . '/.readiness-' . bin2hex(random_bytes(8));
    if (file_put_contents($probe, 'ok', LOCK_EX) !== 2 || file_get_contents($probe) !== 'ok' || !unlink($probe)) {
        @unlink($probe);
        throw new RuntimeException('El almacenamiento no supera la prueba de escritura.');
    }

    $keyring = bit_draft_keyring();
    bit_draft_active_key_version();
    foreach ($keyring as $version => $key) {
        $aad = bit_draft_aad(1, 1, 'operational', str_repeat('0', 64), str_repeat('0', 64), (int) $version);
        $encrypted = bit_draft_encrypt('health', $aad, $key);
        if (bit_draft_decrypt($encrypted['ciphertext'], $encrypted['iv'], $encrypted['tag'], $aad, $key) !== 'health') {
            throw new RuntimeException('El keyring no supera la prueba criptográfica.');
        }
    }
    $versions = $pdo->query('SELECT DISTINCT key_version FROM bitacora_borradores WHERE expires_at > UTC_TIMESTAMP()')->fetchAll(PDO::FETCH_COLUMN);
    $sample = $pdo->prepare('
        SELECT token, idUsuario, idEmpresa, tipo_formulario, schema_hash, ciphertext, iv, tag
        FROM bitacora_borradores
        WHERE key_version = :key_version AND expires_at > UTC_TIMESTAMP()
        ORDER BY id
        LIMIT 1
    ');
    foreach ($versions as $version) {
        $version = (int) $version;
        $key = bit_draft_key_for_version($version);
        $sample->execute(['key_version' => $version]);
        $row = $sample->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            bit_draft_decrypt(
                (string) $row['ciphertext'],
                (string) $row['iv'],
                (string) $row['tag'],
                bit_draft_aad((int) $row['idUsuario'], (int) $row['idEmpresa'], (string) $row['tipo_formulario'], (string) $row['token'], (string) $row['schema_hash'], $version),
                $key
            );
        }
    }

    echo "ok\n";
} catch (Throwable $e) {
    error_log('Readiness falló: ' . $e->getMessage());
    http_response_code(503);
    echo "unavailable\n";
}
