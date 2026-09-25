<?php
// model/auth.php
// Sesión, roles, protección CSRF, mensajes flash y utilidades de respuesta.
require_once __DIR__ . '/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

const RUTA_LOGIN = '../view/login.php';

/* ---------- Sesión y roles ---------- */

function usuario_actual(): ?array
{
    // Las sesiones creadas antes de esta versión no tienen usuario_id: se pide volver a entrar
    if (!isset($_SESSION['usuario'], $_SESSION['usuario_id'])) {
        return null;
    }
    return [
        'id'      => $_SESSION['usuario_id'] ?? null,
        'usuario' => $_SESSION['usuario'],
        'nombre'  => $_SESSION['nombre'] ?? $_SESSION['usuario'],
        'rol'     => $_SESSION['rol'] ?? 'docente',
    ];
}

function es_admin(): bool
{
    return ($_SESSION['rol'] ?? '') === 'admin';
}

function iniciar_sesion_usuario(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $u['id'];
    $_SESSION['usuario']    = $u['usuario'];
    $_SESSION['nombre']     = $u['nombre'];
    $_SESSION['rol']        = $u['rol'];
    unset($_SESSION['csrf']);
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Para vistas y formularios: redirige al login si no hay sesión. */
function requerir_login(): array
{
    if (!usuario_actual()) {
        header('Location: ' . RUTA_LOGIN);
        exit();
    }
    return usuario_actual();
}

function requerir_admin(): array
{
    $u = requerir_login();
    if (!es_admin()) {
        flash('error', 'No tiene permisos para acceder a esa sección.');
        header('Location: ../view/index.php');
        exit();
    }
    return $u;
}

/** Para endpoints JSON: responde 401/403 en vez de redirigir. */
function requerir_login_json(bool $soloAdmin = false): array
{
    if (!usuario_actual()) {
        responder_json(['status' => 'error', 'message' => 'Su sesión expiró. Vuelva a iniciar sesión.'], 401);
    }
    if ($soloAdmin && !es_admin()) {
        responder_json(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción.'], 403);
    }
    return usuario_actual();
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valido(): bool
{
    $enviado = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($enviado) && $enviado !== '' && hash_equals(csrf_token(), $enviado);
}

function verificar_csrf(string $volverA): void
{
    if (!csrf_valido()) {
        redirigir($volverA, 'error', 'La sesión del formulario expiró. Intente de nuevo.');
    }
}

function verificar_csrf_json(): void
{
    if (!csrf_valido()) {
        responder_json(['status' => 'error', 'message' => 'Token de seguridad inválido. Recargue la página.'], 419);
    }
}

/* ---------- Mensajes flash ---------- */

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function tomar_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Respuestas ---------- */

function redirigir(string $url, ?string $tipo = null, ?string $mensaje = null): void
{
    if ($tipo && $mensaje) {
        flash($tipo, $mensaje);
    }
    header('Location: ' . $url);
    exit();
}

function responder_json(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function leer_json(): array
{
    $datos = json_decode(file_get_contents('php://input'), true);
    return is_array($datos) ? $datos : [];
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Normaliza un celular colombiano a formato internacional para wa.me (solo dígitos). */
function telefono_whatsapp(?string $numero): string
{
    $digitos = preg_replace('/\D+/', '', (string) $numero);
    if (strlen($digitos) === 10) {
        $digitos = config('indicativo_pais') . $digitos;
    }
    return strlen($digitos) >= 11 ? $digitos : '';
}

/** Grados del catálogo + los que existan en la tabla de estudiantes. */
function lista_grados(): array
{
    $grados = config('grados');
    try {
        $extra = conexion()->query("SELECT DISTINCT grado FROM estudiantes WHERE grado <> ''")->fetchAll(PDO::FETCH_COLUMN);
        $grados = array_values(array_unique(array_merge($grados, $extra)));
    } catch (Exception $e) {
        // Si la BD falla se usa solo el catálogo
    }
    usort($grados, 'strnatcasecmp');
    return $grados;
}
