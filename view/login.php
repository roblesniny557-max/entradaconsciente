<?php
require_once __DIR__ . '/../model/auth.php';
require_once __DIR__ . '/../model/iconos.php';

if (usuario_actual()) {
    header('Location: index.php');
    exit();
}
$flash = tomar_flash();
$recuperacionActiva = config('clave_maestra') !== '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · <?= e(config('sistema')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/app.css?v=4">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/app.js?v=4"></script>
</head>
<body>
<div class="login-fondo">
    <section class="login-panel">
        <div class="fila" style="gap:10px">
            <?= icono('escudo', 'ic-lg') ?>
            <strong><?= e(config('institucion')) ?></strong>
        </div>
        <div>
            <h2>Control de asistencia en el aula, sin papel.</h2>
            <p>Registre la asistencia de cada clase, avise a los acudientes y consulte el historial de novedades desde un solo lugar.</p>
            <ul class="login-lista">
                <li><?= icono('asistencia') ?> Toma de asistencia por grado y materia</li>
                <li><?= icono('mensaje') ?> Avisos a acudientes por WhatsApp</li>
                <li><?= icono('reportes') ?> Reportes filtrables y exportables</li>
            </ul>
        </div>
        <small style="color:#9fb3a3">&copy; <?= date('Y') ?> <?= e(config('sistema')) ?></small>
    </section>

    <section class="login-form-lado">
        <div class="login-caja pila" style="gap:24px">
            <div class="pila" style="gap:10px; align-items:flex-start">
                <img src="<?= e(config('logo_url')) ?>" alt="Escudo" class="login-logo" onerror="this.style.display='none'">
                <div>
                    <h1 class="pagina-titulo">Ingresar</h1>
                    <p class="pagina-desc">Use su usuario y contraseña institucional.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="aviso <?= $flash['tipo'] === 'error' ? 'aviso-alerta' : 'aviso-exito' ?>">
                    <?= icono($flash['tipo'] === 'error' ? 'evadido' : 'presente') ?>
                    <span><?= e($flash['mensaje']) ?></span>
                </div>
            <?php endif; ?>

            <form action="../controller/login_process.php" method="POST" class="pila">
                <?= csrf_campo() ?>
                <div class="campo">
                    <label for="usuario">Usuario</label>
                    <div class="control-icono">
                        <?= icono('usuario') ?>
                        <input type="text" id="usuario" name="usuario" class="control" placeholder="ej. pascual.orduz" required autocomplete="username" autofocus>
                    </div>
                </div>
                <div class="campo">
                    <label for="password">Contraseña</label>
                    <div class="control-icono">
                        <?= icono('candado') ?>
                        <input type="password" id="password" name="password" class="control" placeholder="Su contraseña" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primario btn-bloque" style="padding:11px"><?= icono('entrar') ?> Ingresar</button>
            </form>

            <?php if ($recuperacionActiva): ?>
                <button type="button" class="btn btn-fantasma" data-abrir="dlgRecuperar" style="align-self:center">
                    <?= icono('llave', 'ic-sm') ?> ¿Olvidó su contraseña?
                </button>
            <?php else: ?>
                <p class="texto-suave" style="text-align:center; font-size:13px">¿Olvidó su contraseña? Pida a un administrador que la restablezca.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($recuperacionActiva): ?>
<dialog class="dialogo" id="dlgRecuperar">
    <form action="../controller/recuperar_process.php" method="POST">
        <?= csrf_campo() ?>
        <div class="dialogo-cabecera">
            <span class="dialogo-titulo">Restablecer contraseña</span>
            <button type="button" class="btn btn-fantasma btn-icono" data-cerrar aria-label="Cerrar"><?= icono('cerrar') ?></button>
        </div>
        <div class="dialogo-cuerpo">
            <p class="texto-suave" style="font-size:14px">Necesita la clave maestra que entrega la coordinación.</p>
            <div class="campo">
                <label for="r_usuario">Usuario</label>
                <input type="text" id="r_usuario" name="usuario" class="control" required autocomplete="username">
            </div>
            <div class="campo">
                <label for="r_clave">Clave maestra</label>
                <input type="password" id="r_clave" name="claveMaestra" class="control" required autocomplete="off">
            </div>
            <div class="campo">
                <label for="r_nueva">Nueva contraseña</label>
                <input type="password" id="r_nueva" name="nuevaPassword" class="control" required minlength="6" autocomplete="new-password">
                <span class="ayuda">Mínimo 6 caracteres.</span>
            </div>
        </div>
        <div class="dialogo-pie">
            <button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primario"><?= icono('check') ?> Restablecer</button>
        </div>
    </form>
</dialog>
<?php endif; ?>

</body>
</html>
