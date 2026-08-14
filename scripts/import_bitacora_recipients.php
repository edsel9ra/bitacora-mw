<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/config/bitacora.php';
require_once __DIR__ . '/../public/bd/conexion.php';

function recipient_import_key(?int $idSede, string $type, string $email): string
{
    return ($idSede === null ? 'global' : (string) $idSede)
        . "\0"
        . $type
        . "\0"
        . strtolower(trim($email));
}

function recipient_import_sede_map(PDO $pdo, int $empresaId): array
{
    $stmt = $pdo->prepare('SELECT idSede, valor_form FROM empresa_sedes WHERE idEmpresa = :idEmpresa ORDER BY orden, id');
    $stmt->execute(['idEmpresa' => $empresaId]);

    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $value = strtolower(trim((string) ($row['valor_form'] ?? '')));
        if ($value !== '' && !isset($map[$value])) {
            $map[$value] = (int) $row['idSede'];
        }
    }

    return $map;
}

function recipient_import_static_rows(array $config, array $sedeMap): array
{
    $rows = [];
    $globalRecipients = (array) (($config['recipients'] ?? [])['global'] ?? []);
    $ccRecipients = (array) (($config['recipients'] ?? [])['cc'] ?? []);
    $bccRecipients = (array) (($config['recipients'] ?? [])['bcc'] ?? []);

    foreach (['to' => $globalRecipients, 'cc' => $ccRecipients, 'bcc' => $bccRecipients] as $type => $emails) {
        foreach ($emails as $index => $emailRaw) {
            $email = trim((string) $emailRaw);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $rows[] = [
                'idSede' => null,
                'tipo' => $type,
                'email' => $email,
                'orden' => 1000 + (int) $index,
            ];
        }
    }

    foreach ((array) (($config['recipients'] ?? [])['by_sede'] ?? []) as $sede => $emails) {
        $sedeKey = strtolower(trim((string) $sede));
        if (!isset($sedeMap[$sedeKey])) {
            throw new RuntimeException('La sede configurada no existe en empresa_sedes: ' . $sede);
        }

        foreach ((array) $emails as $index => $emailRaw) {
            $email = trim((string) $emailRaw);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $rows[] = [
                'idSede' => $sedeMap[$sedeKey],
                'tipo' => 'to',
                'email' => $email,
                'orden' => 100 + (int) $index,
            ];
        }
    }

    return $rows;
}

function recipient_import_existing(PDO $pdo, int $empresaId): array
{
    $stmt = $pdo->prepare('SELECT id, idSede, tipo, email, activo FROM bitacora_destinatarios WHERE idEmpresa = :idEmpresa ORDER BY id');
    $stmt->execute(['idEmpresa' => $empresaId]);

    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $idSede = $row['idSede'] === null ? null : (int) $row['idSede'];
        $row['idSede'] = $idSede;
        $row['id'] = (int) $row['id'];
        $row['activo'] = (int) $row['activo'];
        $rows[] = $row;
    }

    return $rows;
}

$pdo = Conexion::Conectar();
$configs = app_bitacora_configs();
$importedCompanies = 0;
$importedRows = 0;
$deactivatedDuplicates = 0;

$pdo->beginTransaction();
try {
    $modeStmt = $pdo->prepare('SELECT modo FROM bitacora_destinatarios_config WHERE idEmpresa = :idEmpresa LIMIT 1');
    $ensureMode = $pdo->prepare('INSERT INTO bitacora_destinatarios_config (idEmpresa, modo) VALUES (:idEmpresa, \'php\') ON DUPLICATE KEY UPDATE idEmpresa = VALUES(idEmpresa)');
    $insert = $pdo->prepare('INSERT INTO bitacora_destinatarios (idEmpresa, idSede, tipo, email, orden, activo) VALUES (:idEmpresa, :idSede, :tipo, :email, :orden, 1)');
    $update = $pdo->prepare('UPDATE bitacora_destinatarios SET email = :email, orden = :orden, activo = 1 WHERE id = :id');
    $updateOrder = $pdo->prepare('UPDATE bitacora_destinatarios SET orden = :orden WHERE id = :id');
    $deactivate = $pdo->prepare('UPDATE bitacora_destinatarios SET activo = 0 WHERE id = :id');
    $setDatabaseMode = $pdo->prepare('UPDATE bitacora_destinatarios_config SET modo = \'database\' WHERE idEmpresa = :idEmpresa');

    foreach ($configs as $empresaId => $config) {
        $empresaId = (int) $empresaId;
        $ensureMode->execute(['idEmpresa' => $empresaId]);
        $modeStmt->execute(['idEmpresa' => $empresaId]);
        $mode = (string) ($modeStmt->fetchColumn() ?: 'php');
        if ($mode === 'database') {
            continue;
        }

        $sedeMap = recipient_import_sede_map($pdo, $empresaId);
        $staticRows = recipient_import_static_rows($config, $sedeMap);
        $existingRows = recipient_import_existing($pdo, $empresaId);
        $existingByKey = [];
        foreach ($existingRows as $row) {
            $key = recipient_import_key($row['idSede'], (string) $row['tipo'], (string) $row['email']);
            $existingByKey[$key][] = $row;
        }

        $staticKeys = [];
        foreach ($staticRows as $row) {
            $key = recipient_import_key($row['idSede'], $row['tipo'], $row['email']);
            if (isset($staticKeys[$key])) {
                continue;
            }
            $staticKeys[$key] = true;

            $matches = $existingByKey[$key] ?? [];
            if ($matches === []) {
                $insert->execute([
                    'idEmpresa' => $empresaId,
                    'idSede' => $row['idSede'],
                    'tipo' => $row['tipo'],
                    'email' => $row['email'],
                    'orden' => $row['orden'],
                ]);
                $importedRows++;
                continue;
            }

            $canonical = array_shift($matches);
            $update->execute([
                'email' => $row['email'],
                'orden' => $row['orden'],
                'id' => $canonical['id'],
            ]);
            foreach ($matches as $duplicate) {
                $deactivate->execute(['id' => $duplicate['id']]);
                $deactivatedDuplicates++;
            }
        }

        foreach ($existingByKey as $key => $rows) {
            if (isset($staticKeys[$key])) {
                continue;
            }

            $canonical = array_shift($rows);
            $updateOrder->execute([
                'orden' => 200000 + (int) $canonical['id'],
                'id' => $canonical['id'],
            ]);
            foreach ($rows as $duplicate) {
                $deactivate->execute(['id' => $duplicate['id']]);
                $deactivatedDuplicates++;
            }
        }

        $setDatabaseMode->execute(['idEmpresa' => $empresaId]);
        $importedCompanies++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Error importando destinatarios de bitácora: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Empresas importadas: ' . $importedCompanies . PHP_EOL;
echo 'Destinatarios nuevos: ' . $importedRows . PHP_EOL;
echo 'Duplicados desactivados: ' . $deactivatedDuplicates . PHP_EOL;
