<?php
// controller/usuarios.php — solo administradores
// POST accion=crear|editar|restablecer|eliminar
require_once __DIR__ . '/../model/auth.php';

$yo = requerir_admin();
$volver = '../view/usuarios.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir($volver);
}
verificar_csrf($volver);

$accion = $_POST['accion'] ?? '';
$id     = (int) ($_POST['id'] ?? 0);

function validar_password(string $p): ?string
{
    return mb_strlen($p) < 6 ? 'La contraseña debe tener al menos 6 caracteres.' : null;
}

function otros_admins(PDO $db, int $exceptoId): int
{
    $st = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND id <> ?");
    $st->execute([$exceptoId]);
    return (int) $st->fetchColumn();
}

try {
    $db = conexion();

    if ($accion === 'crear' || $accion === 'editar') {
        $nombre  = trim(preg_replace('/\s+/', ' ', $_POST['nombre'] ?? ''));
        $usuario = strtolower(trim($_POST['usuario'] ?? ''));
        $rol     = ($_POST['rol'] ?? '') === 'admin' ? 'admin' : 'docente';

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            redirigir($volver, 'error', 'El nombre es obligatorio (máximo 100 caracteres).');
        }
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $usuario)) {
            redirigir($volver, 'error', 'El usuario debe tener de 3 a 50 caracteres: letras sin tilde, números, punto, guion o guion bajo.');
        }

        $st = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE LOWER(usuario) = ? AND id <> ?");
        $st->execute([$usuario, $id]);
        if ($st->fetchColumn()) {
            redirigir($volver, 'error', "El usuario \"$usuario\" ya está en uso.");
        }

        if ($accion === 'crear') {
            $pass = $_POST['password'] ?? '';
            if ($err = validar_password($pass)) {
                redirigir($volver, 'error', $err);
            }
            $db->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?, ?, ?, ?)")
                ->execute([$nombre, $usuario, password_hash($pass, PASSWORD_DEFAULT), $rol]);
            redirigir($volver, 'success', "Usuario $usuario creado.");
        }

        $st = $db->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        $antes = $st->fetch();
        if (!$antes) {
            redirigir($volver, 'error', 'El usuario no existe.');
        }
        if ($antes['rol'] === 'admin' && $rol !== 'admin' && otros_admins($db, $id) === 0) {
            redirigir($volver, 'error', 'Debe existir al menos un administrador.');
        }
        if ($id === (int) $yo['id'] && $rol !== 'admin') {
            redirigir($volver, 'error', 'No puede quitarse a sí mismo el rol de administrador.');
        }

        $db->beginTransaction();
        $db->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, rol = ? WHERE id = ?")
            ->execute([$nombre, $usuario, $rol, $id]);
        // El historial guarda el nombre del docente: se actualiza para no perder sus registros
        if ($antes['nombre'] !== $nombre) {
            $db->prepare("UPDATE inasistencias SET docente = ? WHERE docente = ?")->execute([$nombre, $antes['nombre']]);
        }
        $db->commit();

        if ($id === (int) $yo['id']) {
            $_SESSION['nombre']  = $nombre;
            $_SESSION['usuario'] = $usuario;
        }
        redirigir($volver, 'success', 'Usuario actualizado.');
    }

    if ($accion === 'restablecer') {
        $pass = $_POST['password'] ?? '';
        if ($err = validar_password($pass)) {
            redirigir($volver, 'error', $err);
        }
        $st = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $st->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        redirigir($volver, 'success', 'Contraseña restablecida. Compártala con el usuario de forma segura.');
    }

    if ($accion === 'eliminar') {
        if ($id === (int) $yo['id']) {
            redirigir($volver, 'error', 'No puede eliminar su propio usuario.');
        }
        $st = $db->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $st->execute([$id]);
        $rol = $st->fetchColumn();
        if ($rol === 'admin' && otros_admins($db, $id) === 0) {
            redirigir($volver, 'error', 'Debe existir al menos un administrador.');
        }
        $db->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        redirigir($volver, 'success', 'Usuario eliminado. Sus registros de asistencia se conservan.');
    }

    redirigir($volver, 'error', 'Acción no válida.');

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    redirigir($volver, 'error', 'Error en la base de datos: ' . $e->getMessage());
}
