<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/bitacora_helpers.php';

app_start_session();

if (empty($_SESSION['s_usuario'])) {
    app_fail_request('No autorizado.', 403);
}

$empresa = (int) ($_GET['empresa'] ?? 0);
$year = (string) ($_GET['year'] ?? '');
$month = (string) ($_GET['month'] ?? '');
$file = (string) ($_GET['file'] ?? '');

if ($empresa !== (int) ($_SESSION['s_idEmpresa'] ?? 0)) {
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

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . rawurlencode($file) . '"');
header('Content-Length: ' . filesize($realPath));
readfile($realPath);
exit;
