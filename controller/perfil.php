<?php
// controller/perfil.php — cambio de contraseña del usuario actual
require_once __DIR__ . '/../model/auth.php';

$yo = requerir_login();
$volver = '../view/perfil.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir($volver);
}
verificar_csrf($volver);

$actual    = $_POST['actual'] ?? '';
$nueva     = $_POST['nueva'] ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if (mb_strlen($nueva) < 6) {
    redirigir($volver, 'error', 'La nueva contraseña debe tener al menos 6 caracteres.');
}
if ($nueva !== $confirmar) {
    redirigir($volver, 'error', 'La confirmación no coincide con la nueva contraseña.');
}

try {
    $db = conexion();
    $st = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
    $st->execute([$yo['id']]);
    $hash = $st->fetchColumn();

    if (!$hash || !password_verify($actual, $hash)) {
        redirigir($volver, 'error', 'La contraseña actual no es correcta.');
    }

    $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
        ->execute([password_hash($nueva, PASSWORD_DEFAULT), $yo['id']]);
} catch (Exception $e) {
    redirigir($volver, 'error', 'Error en la base de datos: ' . $e->getMessage());
}

redirigir($volver, 'success', 'Contraseña actualizada correctamente.');
