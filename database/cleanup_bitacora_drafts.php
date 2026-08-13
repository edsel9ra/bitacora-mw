<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/bd/conexion.php';
require_once __DIR__ . '/../public/config/env.php';

$batchSize = 500;
foreach (array_slice($argv, 1) as $argument) {
    if (ctype_digit((string) $argument)) {
        $batchSize = max(1, min(10000, (int) $argument));
    }
}

try {
    $pdo = Conexion::Conectar();
    $delete = $pdo->prepare('
        DELETE FROM bitacora_borradores
        WHERE expires_at <= UTC_TIMESTAMP()
        ORDER BY expires_at
        LIMIT :batch_size
    ');
    $deleted = 0;
    $batches = 0;
    $maxBatches = max(1, min(1000, app_env_int('BITACORA_MAINTENANCE_MAX_BATCHES', 20)));
    do {
        $batches++;
        $delete->bindValue('batch_size', $batchSize, PDO::PARAM_INT);
        $delete->execute();
        $batchDeleted = $delete->rowCount();
        $deleted += $batchDeleted;
    } while ($batchDeleted === $batchSize && $batches < $maxBatches);

    echo 'Borradores expirados eliminados: ' . $deleted . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Error limpiando borradores expirados: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
