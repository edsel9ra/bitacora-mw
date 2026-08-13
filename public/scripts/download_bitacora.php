<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/bitacora_helpers.php';
require_once __DIR__ . '/bitacora_download_helpers.php';
require_once __DIR__ . '/../bd/conexion.php';

app_start_session();

app_require_login();

$token = (string) ($_GET['token'] ?? '');
if ($token !== '') {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        app_fail_request('Token inválido.', 400);
    }

    try {
        $pdo = Conexion::Conectar();
        $stmt = $pdo->prepare('SELECT * FROM bitacora_pdfs WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $pdf = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('No fue posible consultar PDF de bitácora: ' . $e->getMessage());
        app_fail_request('No fue posible consultar el PDF.', 500);
    }

    if ($pdf === false) {
        app_fail_request('Archivo no encontrado.', 404);
    }

    $expiresAt = trim((string) ($pdf['expires_at'] ?? ''));
    if ($expiresAt !== '') {
        $expiresAtUtc = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $expiresAt, new DateTimeZone('UTC'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (!$expiresAtUtc instanceof DateTimeImmutable
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $expiresAtUtc->format('Y-m-d H:i:s') !== $expiresAt) {
            app_fail_request('El enlace de descarga no es válido.', 410);
        }
        if ($expiresAtUtc < new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            app_fail_request('El enlace de descarga expiró.', 410);
        }
    }

    $isOwner = hash_equals((string) ($pdf['created_by'] ?? ''), (string) ($_SESSION['s_usuario'] ?? ''));
    $sameCompany = (int) ($pdf['idEmpresa'] ?? 0) === app_current_empresa_id();
    if (!app_is_admin() && (!$sameCompany || !$isOwner)) {
        app_fail_request('No autorizado para este PDF.', 403);
    }

    $realPath = bit_download_resolve_path((string) ($pdf['relative_path'] ?? ''));
    if ($realPath === null) {
        app_fail_request('Archivo no encontrado.', 404);
    }

    $fileName = basename((string) ($pdf['file_name'] ?? basename($realPath)));
    if ($fileName === '' || !str_ends_with(strtolower($fileName), '.pdf')) {
        $fileName = basename($realPath);
    }

    bit_download_serve_file($realPath, $fileName);
}

if (!app_legacy_bitacora_enabled()) {
    app_fail_request('Parámetro token requerido.', 400);
}

$empresa = (int) ($_GET['empresa'] ?? 0);
$year = (string) ($_GET['year'] ?? '');
$month = (string) ($_GET['month'] ?? '');
$file = (string) ($_GET['file'] ?? '');

if (!app_is_admin() && $empresa !== app_current_empresa_id()) {
    app_fail_request('No autorizado para esta empresa.', 403);
}

if (!preg_match('/^\d{4}$/', $year) || !preg_match('/^\d{2}$/', $month)) {
    app_fail_request('Parámetros inválidos.', 400);
}

if ($file === '' || basename($file) !== $file || !str_ends_with(strtolower($file), '.pdf')) {
    app_fail_request('Archivo inválido.', 400);
}

$baseDir = bit_storage_base_dir() . '/' . $empresa . '/' . $year . '/' . $month;
$path = $baseDir . '/' . $file;
$realBase = realpath($baseDir);
$realPath = realpath($path);

if ($realBase === false || $realPath === false || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR) || !is_file($realPath)) {
    app_fail_request('Archivo no encontrado.', 404);
}

bit_download_serve_file($realPath, $file);
