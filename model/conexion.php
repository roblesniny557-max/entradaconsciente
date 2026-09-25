<?php
// model/conexion.php
require_once __DIR__ . '/../config/config.php';

/**
 * Devuelve una única conexión PDO por petición.
 * Lanza PDOException si falla (cada controlador decide cómo responder).
 */
function conexion(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        config('db_host'),
        config('db_port'),
        config('db_name')
    );

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (config('db_ssl')) {
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            // PHP compilado con mysqlnd (caso más común en hosting)
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = true;
            $opciones[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = false;
        } else {
            // PHP compilado con libmysqlclient
            foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt'] as $ca) {
                if (file_exists($ca)) {
                    $opciones[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                    break;
                }
            }
        }
    }

    $pdo = new PDO($dsn, config('db_user'), config('db_pass'), $opciones);
    // Hora de Colombia para NOW() y DATE() en la sesión SQL
    $pdo->exec("SET time_zone = '-05:00'");
    return $pdo;
}
