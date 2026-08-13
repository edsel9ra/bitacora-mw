<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

function app_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function app_session_same_site(bool $secure): string
{
    $sameSite = strtolower(app_env('SESSION_SAMESITE', 'Lax') ?? 'Lax');
    $allowed = [
        'lax' => 'Lax',
        'strict' => 'Strict',
        'none' => 'None',
    ];

    $sameSite = $allowed[$sameSite] ?? 'Lax';
    if ($sameSite === 'None' && !$secure) {
        return 'Lax';
    }

    return $sameSite;
}

function app_session_timeout_seconds(string $envName): int
{
    return max(0, app_env_int($envName, 0));
}

function app_enforce_session_lifetime(): void
{
    if (empty($_SESSION['s_usuario'])) {
        return;
    }

    $now = time();
    $createdAt = (int) ($_SESSION['s_session_created_at'] ?? $now);
    $lastActivityAt = (int) ($_SESSION['s_last_activity_at'] ?? $now);
    $maxLifetime = app_session_timeout_seconds('SESSION_MAX_LIFETIME_SECONDS');
    $idleTimeout = app_session_timeout_seconds('SESSION_IDLE_TIMEOUT_SECONDS');

    if (($maxLifetime > 0 && $now - $createdAt > $maxLifetime) || ($idleTimeout > 0 && $now - $lastActivityAt > $idleTimeout)) {
        app_destroy_session();
        return;
    }

    $_SESSION['s_session_created_at'] = $createdAt;
    $_SESSION['s_last_activity_at'] = $now;
}

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = app_env_bool('SESSION_SECURE', app_is_https());
    $sameSite = app_session_same_site($secure);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $sameSite,
    ]);

    session_start();
    app_enforce_session_lifetime();
}

function app_csrf_token(): string
{
    app_start_session();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function app_csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . app_h(app_csrf_token()) . '">';
}

function app_logout_form(string $buttonClass = 'btn btn-danger', string $label = 'Cerrar sesión'): string
{
    return '<form method="post" action="../bd/logout.php" class="app-logout-form">'
        . app_csrf_input()
        . '<button type="submit" class="' . app_h($buttonClass) . '">' . app_h($label) . '</button>'
        . '</form>';
}

function app_csrf_is_valid(): bool
{
    app_start_session();

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || $token === '') {
        return false;
    }

    return isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function app_refresh_session_auth(bool $force = false): bool
{
    app_start_session();

    $usuario = (string) ($_SESSION['s_usuario'] ?? '');
    if ($usuario === '') {
        return true;
    }

    $lastRefresh = (int) ($_SESSION['s_auth_refreshed_at'] ?? 0);
    if (!$force && $lastRefresh > 0 && time() - $lastRefresh < 60) {
        return true;
    }

    try {
        require_once __DIR__ . '/../bd/conexion.php';
        $pdo = Conexion::Conectar();
        $stmt = $pdo->prepare('
            SELECT
                usuarios_login.id,
                usuarios_login.usuario,
                usuarios_login.nombre,
                usuarios_login.idEmpresa,
                usuarios_login.rol,
                razones_sociales.empresa
            FROM usuarios_login
            JOIN razones_sociales ON usuarios_login.idEmpresa = razones_sociales.id
            WHERE usuarios_login.usuario = :usuario
            LIMIT 1
        ');
        $stmt->execute(['usuario' => $usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            unset($_SESSION['s_usuario'], $_SESSION['s_usuario_id'], $_SESSION['s_nombre'], $_SESSION['s_idEmpresa'], $_SESSION['s_empresa'], $_SESSION['s_rol'], $_SESSION['s_admin_empresa_id'], $_SESSION['s_session_created_at'], $_SESSION['s_last_activity_at']);
            return true;
        }

        $rol = in_array((string) ($row['rol'] ?? 'usuario'), ['admin', 'usuario'], true) ? (string) $row['rol'] : 'usuario';
        $_SESSION['s_usuario'] = $row['usuario'];
        $_SESSION['s_usuario_id'] = (int) $row['id'];
        $_SESSION['s_nombre'] = $row['nombre'];
        $_SESSION['s_idEmpresa'] = $row['idEmpresa'];
        $_SESSION['s_empresa'] = $row['empresa'];
        $_SESSION['s_rol'] = $rol;
        $_SESSION['s_auth_refreshed_at'] = time();

        if ($rol !== 'admin') {
            unset($_SESSION['s_admin_empresa_id']);
        } elseif (empty($_SESSION['s_admin_empresa_id'])) {
            $_SESSION['s_admin_empresa_id'] = (int) $row['idEmpresa'];
        }
        return true;
    } catch (Throwable $e) {
        error_log('No fue posible refrescar la sesión de usuario: ' . $e->getMessage());
        return false;
    }
}

function app_is_admin(): bool
{
    app_start_session();
    if (!app_refresh_session_auth(false)) {
        return false;
    }
    return (string) ($_SESSION['s_rol'] ?? 'usuario') === 'admin';
}

function app_current_empresa_id(): int
{
    app_start_session();

    if (app_is_admin() && !empty($_SESSION['s_admin_empresa_id'])) {
        return (int) $_SESSION['s_admin_empresa_id'];
    }

    return (int) ($_SESSION['s_idEmpresa'] ?? 0);
}

function app_set_admin_empresa_id(int $empresaId): void
{
    app_start_session();

    if (!app_is_admin() || $empresaId <= 0) {
        return;
    }

    $_SESSION['s_admin_empresa_id'] = $empresaId;
}

function app_require_admin(string $redirect = '../index.php'): void
{
    app_require_login(null, $redirect);
    if (!app_refresh_session_auth(true)) {
        app_fail_request('No fue posible validar la sesión.', 503, false);
    }

    if (!app_is_admin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function app_require_post_admin(bool $json = false): void
{
    app_require_post_login(null, $json);

    if (!app_is_admin()) {
        app_fail_request('No autorizado.', 403, $json);
    }
}

function app_fail_request(string $message, int $statusCode = 403, bool $json = false): void
{
    http_response_code($statusCode);

    if ($json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'message' => $message]);
    } else {
        echo $message;
    }

    exit;
}

function app_legacy_bitacora_enabled(): bool
{
    return app_env_bool('APP_ENABLE_LEGACY_BITACORA', false);
}

function app_require_legacy_view(int $empresaId, string $redirect = 'bitacora.php'): void
{
    app_require_login($empresaId);

    if (app_legacy_bitacora_enabled()) {
        return;
    }

    if (app_is_admin()) {
        app_set_admin_empresa_id($empresaId);
    }

    header('Location: ' . $redirect);
    exit;
}

function app_require_legacy_post(int $empresaId, bool $json = true): void
{
    app_require_post_login($empresaId, $json);

    if (app_legacy_bitacora_enabled()) {
        return;
    }

    app_fail_request('Ruta legacy deshabilitada. Usa la bitácora unificada.', 410, $json);
}

function app_require_login(?int $empresaId = null, string $redirect = '../index.php'): void
{
    app_start_session();
    if (!app_refresh_session_auth(false)) {
        app_fail_request('No fue posible validar la sesión.', 503, false);
    }

    $authenticated = !empty($_SESSION['s_usuario']);
    $authorizedCompany = $empresaId === null || app_is_admin() || (int) ($_SESSION['s_idEmpresa'] ?? 0) === $empresaId;

    if (!$authenticated || !$authorizedCompany) {
        header('Location: ' . $redirect);
        exit;
    }
}

function app_require_post_login(?int $empresaId = null, bool $json = false): void
{
    app_start_session();

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        app_fail_request('Método no permitido.', 405, $json);
    }

    if (empty($_SESSION['s_usuario'])) {
        app_fail_request('No autorizado.', 403, $json);
    }

    if (!app_refresh_session_auth(true)) {
        app_fail_request('No fue posible validar la sesión.', 503, $json);
    }

    if (empty($_SESSION['s_usuario'])) {
        app_fail_request('No autorizado.', 403, $json);
    }

    if ($empresaId !== null && !app_is_admin() && (int) ($_SESSION['s_idEmpresa'] ?? 0) !== $empresaId) {
        app_fail_request('No autorizado para esta empresa.', 403, $json);
    }

    if (!app_csrf_is_valid()) {
        app_fail_request('Token CSRF inválido.', 419, $json);
    }
}

function app_destroy_session(): void
{
    app_start_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => (string) ($params['path'] ?? '/'),
            'domain' => (string) ($params['domain'] ?? ''),
            'secure' => !empty($params['secure']),
            'httponly' => !empty($params['httponly']),
            'samesite' => (string) ($params['samesite'] ?? app_session_same_site(!empty($params['secure']))),
        ]);
    }

    session_destroy();
}
