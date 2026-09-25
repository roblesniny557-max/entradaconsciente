<?php
// controller/login_process.php
require_once __DIR__ . '/../model/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir(RUTA_LOGIN);
}
verificar_csrf(RUTA_LOGIN);

// Bloqueo temporal tras varios intentos fallidos (por sesión)
$intentos = $_SESSION['login_intentos'] ?? 0;
$bloqueo  = $_SESSION['login_bloqueo'] ?? 0;
if ($bloqueo > time()) {
    $min = ceil(($bloqueo - time()) / 60);
    redirigir(RUTA_LOGIN, 'error', "Demasiados intentos fallidos. Espere $min minuto(s) e intente de nuevo.");
}

$user = trim($_POST['usuario'] ?? '');
$pass = $_POST['password'] ?? '';

if ($user === '' || $pass === '') {
    redirigir(RUTA_LOGIN, 'error', 'Por favor complete todos los campos.');
}

try {
    $stmt = conexion()->prepare("SELECT id, nombre, usuario, password, rol FROM usuarios WHERE LOWER(usuario) = LOWER(?) LIMIT 1");
    $stmt->execute([$user]);
    $usuario = $stmt->fetch();
} catch (Exception $e) {
    redirigir(RUTA_LOGIN, 'error', 'No hay conexión con la base de datos. Intente más tarde.');
}

if ($usuario && password_verify($pass, $usuario['password'])) {
    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueo']);

    // Actualizar el hash si PHP recomienda un algoritmo más fuerte
    if (password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
        try {
            conexion()->prepare("UPDATE usuarios SET password = ? WHERE id = ?")
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $usuario['id']]);
        } catch (Exception $e) {
            // No es crítico
        }
    }

    iniciar_sesion_usuario($usuario);
    redirigir('../view/index.php', 'success', 'Bienvenido(a), ' . $usuario['nombre'] . '.');
}

$_SESSION['login_intentos'] = ++$intentos;
if ($intentos >= 5) {
    $_SESSION['login_bloqueo']  = time() + 5 * 60;
    $_SESSION['login_intentos'] = 0;
}
redirigir(RUTA_LOGIN, 'error', 'Usuario o contraseña incorrectos.');
