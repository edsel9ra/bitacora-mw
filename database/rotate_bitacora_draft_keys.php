<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/config/bitacora_drafts.php';
require_once __DIR__ . '/../public/bd/conexion.php';

$batchSize = 100;
foreach (array_slice($argv, 1) as $argument) {
    if (ctype_digit((string) $argument)) {
        $batchSize = max(1, min(1000, (int) $argument));
    }
}

try {
    $activeVersion = bit_draft_active_key_version();
    $pdo = Conexion::Conectar();
    $rotated = 0;

    do {
        $find = $pdo->prepare('
            SELECT id
            FROM bitacora_borradores
            WHERE key_version <> :active_version
            ORDER BY id
            LIMIT :batch_size
        ');
        $find->bindValue('active_version', $activeVersion, PDO::PARAM_INT);
        $find->bindValue('batch_size', $batchSize, PDO::PARAM_INT);
        $find->execute();
        $ids = array_map('intval', $find->fetchAll(PDO::FETCH_COLUMN));

        foreach ($ids as $id) {
            $pdo->beginTransaction();
            try {
                $lock = $pdo->prepare('SELECT * FROM bitacora_borradores WHERE id = :id FOR UPDATE');
                $lock->execute(['id' => $id]);
                $row = $lock->fetch(PDO::FETCH_ASSOC);
                if ($row === false || (int) $row['key_version'] === $activeVersion) {
                    $pdo->commit();
                    continue;
                }

                $oldVersion = (int) $row['key_version'];
                $plaintext = bit_draft_decrypt(
                    (string) $row['ciphertext'],
                    (string) $row['iv'],
                    (string) $row['tag'],
                    bit_draft_aad((int) $row['idUsuario'], (int) $row['idEmpresa'], (string) $row['tipo_formulario'], (string) $row['token'], (string) $row['schema_hash'], $oldVersion),
                    bit_draft_key_for_version($oldVersion)
                );
                $encrypted = bit_draft_encrypt(
                    $plaintext,
                    bit_draft_aad((int) $row['idUsuario'], (int) $row['idEmpresa'], (string) $row['tipo_formulario'], (string) $row['token'], (string) $row['schema_hash'], $activeVersion)
                );

                $update = $pdo->prepare('
                    UPDATE bitacora_borradores
                    SET ciphertext = :ciphertext, iv = :iv, tag = :tag, key_version = :key_version,
                        actualizado_en = :actualizado_en
                    WHERE id = :id AND key_version = :old_key_version AND version = :version
                ');
                $update->execute([
                    'ciphertext' => $encrypted['ciphertext'],
                    'iv' => $encrypted['iv'],
                    'tag' => $encrypted['tag'],
                    'key_version' => $encrypted['key_version'],
                    'actualizado_en' => $row['actualizado_en'],
                    'id' => $id,
                    'old_key_version' => $oldVersion,
                    'version' => (int) $row['version'],
                ]);
                $rotated += $update->rowCount();
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    } while (count($ids) === $batchSize);

    echo 'Borradores rotados a clave v' . $activeVersion . ': ' . $rotated . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Error rotando claves de borradores: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
