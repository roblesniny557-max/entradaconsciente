<?php
// config/config.php
// Configuración general. Los valores sensibles (base de datos, clave maestra)
// se definen en config/config.local.php (no se sube a git) o en variables de entorno.

date_default_timezone_set('America/Bogota');

$GLOBALS['CONFIG'] = array_merge(
    [
        // Base de datos
        'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
        'db_port' => getenv('DB_PORT') ?: '3306',
        'db_user' => getenv('DB_USER') ?: 'root',
        'db_pass' => getenv('DB_PASS') ?: '',
        'db_name' => getenv('DB_NAME') ?: 'consciente',
        'db_ssl'  => filter_var(getenv('DB_SSL') ?: 'false', FILTER_VALIDATE_BOOLEAN),

        // Clave maestra para restablecer contraseñas desde el login (vacía = función desactivada)
        'clave_maestra' => getenv('CLAVE_MAESTRA') ?: '',

        // Número de WhatsApp que recibe el reporte general de cada clase (vacío = elegir contacto)
        'whatsapp_coordinacion' => getenv('WHATSAPP_COORDINACION') ?: '',
        'indicativo_pais'       => '57',

        // Datos de la institución
        'institucion' => 'IE Carlos Lleras Restrepo',
        'sistema'     => 'Entrada Consciente',
        'logo_url'    => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1ydV5pAMV7JIMln8X-w9GiedpSPnSVinjdJCvhRUyZawxcvrOtqDSAyZn&s=10',

        // Catálogos
        'grados' => [
            '6A', '6B', '7A', '7B', '8A', '8B', '9A', '9B', '10A', '10B', '11A', '11B',
        ],
        'materias' => [
            'Matemáticas', 'Geometría', 'Estadística', 'Física', 'Química', 'Biología',
            'Lengua Castellana', 'Inglés', 'Ciencias Sociales', 'Filosofía',
            'Educación Física', 'Educación Artística', 'Tecnología e Informática',
            'Ética y Valores', 'Religión',
        ],

        // Director(a) de grupo por grado (aparece en el reporte de WhatsApp)
        'directores' => [
            '11B' => 'Ana Milena Romero Vargas',
        ],
    ],
    file_exists(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : []
);

function config(string $clave)
{
    return $GLOBALS['CONFIG'][$clave] ?? null;
}
