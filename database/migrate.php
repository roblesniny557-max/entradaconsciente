<?php
// database/migrate.php
// Ajustes opcionales sobre una base de datos existente. Es idempotente: se puede ejecutar varias veces.
// Uso: php database/migrate.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ejecutar solo desde la terminal.');
}

require_once __DIR__ . '/../model/conexion.php';
$db = conexion();

// 1. Índice para acelerar la carga de la toma del día y los reportes
$st = $db->query("SELECT COUNT(*) FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inasistencias' AND INDEX_NAME = 'idx_toma'");
if (!$st->fetchColumn()) {
    $db->exec("ALTER TABLE inasistencias ADD INDEX idx_toma (grado, materia, docente, fecha_registro)");
    echo "+ índice inasistencias.idx_toma\n";
}

// 2. La lista del grado 11B estaba fija en el código de index.php; se pasa a la base de datos.
$lista11B = [
    "Ana Martínez", "Andrés Gómez", "Beatriz Rojas", "Carlos Ruiz", "Carmen Silva",
    "Daniela Herrera", "David Jiménez", "Elena Castro", "Esteban Morales", "Fernanda Ortiz",
    "Gabriel Mendoza", "Gloria Vargas", "Héctor Ríos", "Inés Navarro", "Javier Soto",
    "Julia Delgado", "Kevin Flores", "Laura Peña", "Luis Medina", "María Fuentes",
    "Martín Aguilar", "Natalia Reyes", "Nicolás Blanco", "Olivia Cruz", "Pablo Paredes",
    "Paula Salazar", "Ricardo Vega", "Sofía León", "Tomás Gil", "Valeria Mora",
];
$existe = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE grado = '11B' AND nombres = ?");
$crear  = $db->prepare("INSERT INTO estudiantes (nombres, grado) VALUES (?, '11B')");
$agregados = 0;
foreach ($lista11B as $nombre) {
    $existe->execute([$nombre]);
    if (!$existe->fetchColumn()) {
        $crear->execute([$nombre]);
        $agregados++;
    }
}
echo $agregados ? "+ $agregados estudiantes de 11B importados\n" : "= estudiantes de 11B ya existían\n";

echo "Migración completa.\n";
