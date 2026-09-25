<?php
// Copie este archivo como config/config.local.php y complete los valores.
return [
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_user' => 'usuario',
    'db_pass' => 'contraseña',
    'db_name' => 'consciente',
    'db_ssl'  => false,   // true para TiDB Cloud u otros servicios que exigen SSL

    'clave_maestra'         => '',   // vacío desactiva "¿Olvidó su contraseña?"
    'whatsapp_coordinacion' => '',   // ej. 573001234567
];
