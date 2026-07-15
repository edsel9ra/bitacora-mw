<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/conexion.php';

app_start_session();
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    app_fail_request('Método no permitido.', 405, true);
}

if (!app_csrf_is_valid()) {
    app_fail_request('Token CSRF inválido.', 419, true);
}

$usuario = trim((string) ($_POST['usuario'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

try {
    $conexion = Conexion::Conectar();
    $consulta = "
        SELECT
            usuarios_login.usuario,
            usuarios_login.password,
            usuarios_login.nombre AS nombre,
            usuarios_login.idEmpresa AS idEmpresa,
            razones_sociales.empresa AS empresa
        FROM usuarios_login
        JOIN razones_sociales ON usuarios_login.idEmpresa = razones_sociales.id
        WHERE usuarios_login.usuario = :usuario
        LIMIT 1
    ";

    $resultado = $conexion->prepare($consulta);
    $resultado->execute(['usuario' => $usuario]);
    $row = $resultado->fetch(PDO::FETCH_ASSOC);

    $validPassword = false;
    if ($row !== false) {
        $storedPassword = (string) $row['password'];
        $validPassword = password_verify($password, $storedPassword);

        if (!$validPassword && preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
            $validPassword = hash_equals(strtolower($storedPassword), md5($password));
        }
    }

    if ($row !== false && $validPassword) {
        session_regenerate_id(true);
        $_SESSION['s_usuario'] = $row['usuario'];
        $_SESSION['s_nombre'] = $row['nombre'];
        $_SESSION['s_idEmpresa'] = $row['idEmpresa'];
        $_SESSION['s_empresa'] = $row['empresa'];

        echo json_encode([[
            'nombre' => $row['nombre'],
            'idEmpresa' => $row['idEmpresa'],
            'empresa' => $row['empresa'],
        ]]);
        exit;
    }

    unset($_SESSION['s_usuario'], $_SESSION['s_nombre'], $_SESSION['s_idEmpresa'], $_SESSION['s_empresa']);
    echo json_encode(null);
} catch (Throwable $e) {
    error_log('Error en login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(null);
}
