<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../public/config/env.php';
require_once __DIR__ . '/../public/bd/conexion.php';

$username = trim((string) app_env('BITACORA_ADMIN_USERNAME', ''));
$password = (string) app_env('BITACORA_ADMIN_PASSWORD', '');
$name = trim((string) app_env('BITACORA_ADMIN_NAME', 'Administrador'));
$email = trim((string) app_env('BITACORA_ADMIN_EMAIL', ''));
$empresaId = app_env_int('BITACORA_ADMIN_EMPRESA_ID', 6);
$sedeId = app_env_int('BITACORA_ADMIN_SEDE_ID', 11);

if ($username === '' || strlen($username) > 25 || !preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
    fwrite(STDERR, "BITACORA_ADMIN_USERNAME es obligatorio y debe ser un identificador válido de máximo 25 caracteres.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "BITACORA_ADMIN_PASSWORD debe tener al menos 12 caracteres.\n");
    exit(1);
}
if ($email === '' || strlen($email) > 50 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "BITACORA_ADMIN_EMAIL debe ser una dirección válida de máximo 50 caracteres.\n");
    exit(1);
}
if ($name === '' || strlen($name) > 50) {
    fwrite(STDERR, "BITACORA_ADMIN_NAME debe tener entre 1 y 50 caracteres.\n");
    exit(1);
}

try {
    $pdo = Conexion::Conectar();
    $exists = $pdo->prepare('SELECT 1 FROM usuarios_login WHERE usuario = :usuario OR email = :email LIMIT 1');
    $exists->execute(['usuario' => $username, 'email' => $email]);
    if ($exists->fetchColumn() !== false) {
        throw new RuntimeException('Ya existe un usuario con ese nombre o correo.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO usuarios_login (nombre, usuario, email, password, idEmpresa, rol, fecha_creado, idSede)
         VALUES (:nombre, :usuario, :email, :password, :idEmpresa, 'admin', NOW(), :idSede)"
    );
    $stmt->execute([
        'nombre' => $name,
        'usuario' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'idEmpresa' => $empresaId,
        'idSede' => $sedeId,
    ]);

    echo 'Administrador creado: ' . $username . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'No fue posible crear el administrador: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
