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
        PDO::ATTR_TIMEOUT            => 5, // Límite de 5 segundos para evitar bloqueos por latencia de red
    ];

    if (config('db_ssl')) {
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $opciones[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = false;
        }
        if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = true;
        }
    }

    try {
        $pdo = new PDO($dsn, config('db_user'), config('db_pass'), $opciones);
        // Hora de Colombia para NOW() y DATE() en la sesión SQL
        $pdo->exec("SET time_zone = '-05:00'");
        return $pdo;
    } catch (PDOException $e) {
        // Muestra el error exacto de la base de datos si la conexión falla o expira el tiempo
        die("Error de conexión real con la BD: " . $e->getMessage());
    }
}