<?php
function conexion(): PDO {
    $host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
    $port = "4000";
    $user = "24JM9xLq4c8h9yo.root";
    $pass = "Z7BJ7BFh6nO67zh8";
    $db   = "consciente";

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            // PHP compilado con mysqlnd (caso más común en hosting)
            $options[PDO::MYSQL_ATTR_SSL_CA] = true;
            $options[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = false;
        } else {
            // PHP compilado con libmysqlclient (ej. este Codespace)
            foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt'] as $ca) {
                if (file_exists($ca)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                    break;
                }
            }
        }

        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
