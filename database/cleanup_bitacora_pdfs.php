<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/bd/conexion.php';
require_once __DIR__ . '/../public/scripts/bitacora_helpers.php';

$limit = 500;
foreach (array_slice($argv, 1) as $argument) {
    if (ctype_digit((string) $argument)) {
        $limit = max(1, min(10000, (int) $argument));
    }
}
$removeMissing = in_array('--missing', $argv, true);

function cleanup_resolve_pdf_path(string $relativePath): ?string
{
    return bit_storage_resolve_path($relativePath);
}

try {
    $pdo = Conexion::Conectar();
    if (!$removeMissing && !bit_pdfs_have_expires_at($pdo)) {
        throw new RuntimeException('La columna expires_at no existe. Aplica migraciones primero.');
    }

    $delete = $pdo->prepare('DELETE FROM bitacora_pdfs WHERE id = :id');
    $deletedFiles = 0;
    $deletedRows = 0;
    $failedFiles = 0;
    $lastId = 0;
    $lastExpiresAt = '1970-01-01 00:00:00';
    $batches = 0;
    $maxBatches = max(1, min(1000, app_env_int('BITACORA_MAINTENANCE_MAX_BATCHES', 20)));

    do {
        $batches++;
        $query = $removeMissing
            ? 'SELECT id, relative_path FROM bitacora_pdfs WHERE id > :last_id ORDER BY id LIMIT :limit'
            : 'SELECT id, relative_path, expires_at FROM bitacora_pdfs
               WHERE expires_at IS NOT NULL AND expires_at < UTC_TIMESTAMP()
                 AND (expires_at > :last_expires_at OR (expires_at = :same_expires_at AND id > :last_id))
               ORDER BY expires_at, id LIMIT :limit';
        $stmt = $pdo->prepare($query);
        $stmt->bindValue('last_id', $lastId, PDO::PARAM_INT);
        if (!$removeMissing) {
            $stmt->bindValue('last_expires_at', $lastExpiresAt);
            $stmt->bindValue('same_expires_at', $lastExpiresAt);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $lastId = max($lastId, (int) $row['id']);
            if (!$removeMissing) {
                $lastExpiresAt = (string) $row['expires_at'];
                $lastId = (int) $row['id'];
            }
            $path = cleanup_resolve_pdf_path((string) ($row['relative_path'] ?? ''));
            if ($removeMissing) {
                if ($path !== null) {
                    continue;
                }
            } elseif ($path !== null) {
                if (!unlink($path)) {
                    $failedFiles++;
                    error_log('No fue posible eliminar el PDF expirado: ' . $path);
                    continue;
                }
                $deletedFiles++;
            }

            $delete->execute(['id' => (int) $row['id']]);
            $deletedRows++;
        }
    } while (count($rows) === $limit && $batches < $maxBatches);

    if ($failedFiles > 0) {
        throw new RuntimeException('No fue posible eliminar ' . $failedFiles . ' archivos PDF expirados.');
    }

    echo 'PDFs eliminados: ' . $deletedFiles . PHP_EOL;
    echo 'Registros eliminados: ' . $deletedRows . PHP_EOL;
    echo 'Archivos con error: ' . $failedFiles . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Error limpiando PDFs expirados: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
