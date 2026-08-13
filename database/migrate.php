<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/bd/conexion.php';

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files, SORT_STRING);

// Migraciones reescritas (DDL idempotente compatible con MySQL 8) que pueden
// re-aplicarse de forma segura en BD ya migradas para actualizar su checksum.
$reapplicable = ['001_unified_bitacora.sql', '003_roles_admin.sql', '006_seed_bitacora_destinatarios.sql', '009_bitacora_pdf_expiration.sql', '014_bitacora_submission_idempotency.sql', '016_bitacora_submission_observability.sql', '017_seed_bitacora_seccion_destinatarios.sql'];
$pdo = null;
$migrationLockAcquired = false;
$exitCode = 0;

try {
    $pdo = Conexion::Conectar();
    $migrationLockAcquired = (int) $pdo->query("SELECT GET_LOCK('bitacora_schema_migrations', 30)")->fetchColumn() === 1;
    if (!$migrationLockAcquired) {
        throw new RuntimeException('Otra ejecución del migrador mantiene el bloqueo de esquema.');
    }

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations (
  migration VARCHAR(255) NOT NULL PRIMARY KEY,
  checksum CHAR(64) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    $stmt = $pdo->query('SELECT migration, checksum FROM schema_migrations');
    $applied = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $applied[(string) $row['migration']] = (string) $row['checksum'];
    }

    $insert = $pdo->prepare('INSERT INTO schema_migrations (migration, checksum) VALUES (:migration, :checksum)');
    $ran = 0;

    foreach ($files as $file) {
        $migration = basename($file);
        $checksum = hash_file('sha256', $file);
        if ($checksum === false) {
            throw new RuntimeException('No fue posible calcular checksum de ' . $migration);
        }

        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException('Migracion vacia o ilegible: ' . $migration);
        }

        if (isset($applied[$migration])) {
            if (!hash_equals($applied[$migration], $checksum)) {
                if (in_array($migration, $reapplicable, true)) {
                    echo 'reapply ' . $migration . PHP_EOL;
                    $pdo->exec($sql);
                    $update = $pdo->prepare('UPDATE schema_migrations SET checksum = :checksum WHERE migration = :migration');
                    $update->execute([
                        'checksum' => $checksum,
                        'migration' => $migration,
                    ]);
                    $ran++;
                    continue;
                }

                throw new RuntimeException('La migracion ' . $migration . ' ya fue aplicada con otro checksum.');
            }
            echo 'skip ' . $migration . PHP_EOL;
            continue;
        }

        echo 'apply ' . $migration . PHP_EOL;
        $pdo->exec($sql);
        $insert->execute([
            'migration' => $migration,
            'checksum' => $checksum,
        ]);
        $ran++;
    }

    echo $ran === 0 ? 'Migraciones al dia.' . PHP_EOL : 'Migraciones aplicadas: ' . $ran . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Error aplicando migraciones: ' . $e->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if ($migrationLockAcquired && $pdo instanceof PDO) {
        try {
            $pdo->query("SELECT RELEASE_LOCK('bitacora_schema_migrations')");
        } catch (Throwable $e) {
            fwrite(STDERR, 'No fue posible liberar el bloqueo del migrador: ' . $e->getMessage() . PHP_EOL);
            $exitCode = 1;
        }
    }
}

exit($exitCode);
