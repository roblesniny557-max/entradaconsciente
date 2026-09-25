<?php
// controller/logout.php
require_once __DIR__ . '/../model/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido()) {
    cerrar_sesion();
    session_start();
    flash('success', 'Sesión cerrada correctamente.');
}

redirigir(RUTA_LOGIN);
