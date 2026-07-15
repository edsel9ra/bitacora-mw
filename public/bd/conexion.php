<?php
require_once __DIR__ . '/../config/env.php';

class Conexion
{
    public static function Conectar(): PDO
    {
        $host = app_env('MYSQL_HOST', 'db');
        $database = app_env('MYSQL_DATABASE', 'php_app');
        $user = app_env('MYSQL_USER', 'php_user');
        $password = app_env('MYSQL_PASSWORD', 'php_password');

        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO($dsn, $user, $password, $options);
        } catch (Throwable $e) {
            error_log('Error conectando a la base de datos: ' . $e->getMessage());
            throw new RuntimeException('No fue posible conectar a la base de datos.');
        }
    }
}
