<?php
// controller/recuperar_process.php
require_once __DIR__ . '/../model/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir(RUTA_LOGIN);
}
verificar_csrf(RUTA_LOGIN);

$claveConfig = (string) config('clave_maestra');
if ($claveConfig === '') {
    redirigir(RUTA_LOGIN, 'error', 'La recuperación con clave maestra está desactivada. Contacte a un administrador.');
}

$user          = trim($_POST['usuario'] ?? '');
$claveMaestra  = $_POST['claveMaestra'] ?? '';
$nuevaPassword = $_POST['nuevaPassword'] ?? '';

if ($user === '' || $claveMaestra === '' || $nuevaPassword === '') {
    redirigir(RUTA_LOGIN, 'error', 'Por favor complete todos los campos.');
}
if (mb_strlen($nuevaPassword) < 6) {
    redirigir(RUTA_LOGIN, 'error', 'La nueva contraseña debe tener al menos 6 caracteres.');
}
if (!hash_equals($claveConfig, $claveMaestra)) {
    redirigir(RUTA_LOGIN, 'error', 'La clave maestra ingresada no es válida.');
}

try {
    $db = conexion();
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE LOWER(usuario) = LOWER(?) LIMIT 1");
    $stmt->execute([$user]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        redirigir(RUTA_LOGIN, 'error', 'El usuario ingresado no existe.');
    }

    $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
        ->execute([password_hash($nuevaPassword, PASSWORD_DEFAULT), $id]);
} catch (Exception $e) {
    redirigir(RUTA_LOGIN, 'error', 'No hay conexión con la base de datos. Intente más tarde.');
}

redirigir(RUTA_LOGIN, 'success', 'Contraseña restablecida. Ya puede ingresar.');
