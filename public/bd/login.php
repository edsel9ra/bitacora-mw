<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/conexion.php';

app_start_session();
header('Content-Type: application/json; charset=UTF-8');

function login_client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function login_rate_limit_status(PDO $pdo, string $usuario, string $ip): array
{
    try {
        $stmt = $pdo->prepare('SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), locked_until)) FROM login_attempts WHERE usuario = :usuario AND ip = :ip LIMIT 1');
        $stmt->execute(['usuario' => $usuario, 'ip' => $ip]);
        $remaining = $stmt->fetchColumn();
        if ($remaining === false || $remaining === null) {
            return [true, 0];
        }

        $remaining = (int) $remaining;
        return $remaining > 0 ? [false, $remaining] : [true, 0];
    } catch (Throwable $e) {
        error_log('No fue posible validar rate limit de login: ' . $e->getMessage());
        return [true, 0];
    }
}

function login_record_failure(PDO $pdo, string $usuario, string $ip): void
{
    try {
        $stmt = $pdo->prepare('
            INSERT INTO login_attempts (usuario, ip, attempts, locked_until)
            VALUES (:usuario, :ip, 1, NULL)
            ON DUPLICATE KEY UPDATE
                attempts = IF(login_attempts.locked_until IS NOT NULL AND login_attempts.locked_until <= NOW(), 1, login_attempts.attempts + 1),
                locked_until = IF(login_attempts.attempts >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
        ');
        $stmt->execute([
            'usuario' => $usuario,
            'ip' => $ip,
        ]);
    } catch (Throwable $e) {
        error_log('No fue posible registrar intento fallido de login: ' . $e->getMessage());
    }
}

function login_clear_failures(PDO $pdo, string $usuario, string $ip): void
{
    try {
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE usuario = :usuario AND ip = :ip');
        $stmt->execute(['usuario' => $usuario, 'ip' => $ip]);
    } catch (Throwable $e) {
        error_log('No fue posible limpiar intentos fallidos de login: ' . $e->getMessage());
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    app_fail_request('Método no permitido.', 405, true);
}

if (!app_csrf_is_valid()) {
    app_fail_request('Token CSRF inválido.', 419, true);
}

$usuario = trim((string) ($_POST['usuario'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$clientIp = login_client_ip();

try {
    $conexion = Conexion::Conectar();

    [$loginAllowed, $retryAfter] = login_rate_limit_status($conexion, $usuario, $clientIp);
    if (!$loginAllowed) {
        app_fail_request('Demasiados intentos fallidos. Intenta nuevamente en ' . max(1, (int) ceil($retryAfter / 60)) . ' minutos.', 429, true);
    }

    $consulta = "
        SELECT
            usuarios_login.id,
            usuarios_login.usuario,
            usuarios_login.password,
            usuarios_login.nombre AS nombre,
            usuarios_login.idEmpresa AS idEmpresa,
            usuarios_login.rol AS rol,
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
    $usedLegacyMd5 = false;
    if ($row !== false) {
        $storedPassword = (string) $row['password'];
        $validPassword = password_verify($password, $storedPassword);

        if (!$validPassword && preg_match('/^[a-f0-9]{32}$/i', $storedPassword)) {
            $validPassword = hash_equals(strtolower($storedPassword), md5($password));
            $usedLegacyMd5 = $validPassword;
        }
    }

    if ($row !== false && $validPassword) {
        login_clear_failures($conexion, $usuario, $clientIp);
        if ($usedLegacyMd5 || password_needs_rehash((string) $row['password'], PASSWORD_DEFAULT)) {
            $rehash = $conexion->prepare('UPDATE usuarios_login SET password = :password WHERE usuario = :usuario');
            $rehash->execute([
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'usuario' => $usuario,
            ]);
        }

        $rol = in_array((string) ($row['rol'] ?? 'usuario'), ['admin', 'usuario'], true) ? (string) $row['rol'] : 'usuario';

        session_regenerate_id(true);
        $sessionTime = time();
        $_SESSION['s_usuario'] = $row['usuario'];
        $_SESSION['s_usuario_id'] = (int) $row['id'];
        $_SESSION['s_nombre'] = $row['nombre'];
        $_SESSION['s_idEmpresa'] = $row['idEmpresa'];
        $_SESSION['s_empresa'] = $row['empresa'];
        $_SESSION['s_rol'] = $rol;
        $_SESSION['s_session_created_at'] = $sessionTime;
        $_SESSION['s_last_activity_at'] = $sessionTime;
        if ($rol === 'admin') {
            $_SESSION['s_admin_empresa_id'] = (int) $row['idEmpresa'];
        } else {
            unset($_SESSION['s_admin_empresa_id']);
        }

        echo json_encode([[
            'nombre' => $row['nombre'],
            'idEmpresa' => $row['idEmpresa'],
            'empresa' => $row['empresa'],
            'rol' => $rol,
        ]]);
        exit;
    }

    if ($usuario !== '') {
        login_record_failure($conexion, $usuario, $clientIp);
    }

    unset($_SESSION['s_usuario'], $_SESSION['s_usuario_id'], $_SESSION['s_nombre'], $_SESSION['s_idEmpresa'], $_SESSION['s_empresa'], $_SESSION['s_rol'], $_SESSION['s_admin_empresa_id'], $_SESSION['s_session_created_at'], $_SESSION['s_last_activity_at']);
    echo json_encode(null);
} catch (Throwable $e) {
    error_log('Error en login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(null);
}
