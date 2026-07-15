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

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = app_env_bool('SESSION_SECURE', app_is_https());
    $sameSite = app_env('SESSION_SAMESITE', 'Lax') ?? 'Lax';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $sameSite,
    ]);

    session_start();
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

function app_require_login(?int $empresaId = null, string $redirect = '../index.php'): void
{
    app_start_session();

    $authenticated = !empty($_SESSION['s_usuario']);
    $authorizedCompany = $empresaId === null || (int) ($_SESSION['s_idEmpresa'] ?? 0) === $empresaId;

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

    if ($empresaId !== null && (int) ($_SESSION['s_idEmpresa'] ?? 0) !== $empresaId) {
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
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
