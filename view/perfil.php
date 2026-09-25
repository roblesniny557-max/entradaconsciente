<?php
require_once __DIR__ . '/components/layout.php';
$yo = requerir_login();

$resumen = ['total' => 0, 'hoy' => 0];
try {
    $st = conexion()->prepare(
        "SELECT COUNT(*) AS total, SUM(fecha_registro >= ?) AS hoy FROM inasistencias WHERE docente = ?"
    );
    $st->execute([date('Y-m-d 00:00:00'), $yo['nombre']]);
    $resumen = $st->fetch();
} catch (Exception $e) {
    // Sin estadísticas si la BD no responde
}

layout_inicio('Mi perfil');
?>
<div class="pagina-cabecera">
    <div>
        <h1 class="pagina-titulo">Mi perfil</h1>
        <p class="pagina-desc">Datos de su cuenta y cambio de contraseña.</p>
    </div>
</div>

<div class="rejilla rejilla-2" style="align-items:start; max-width:900px">
    <div class="card card-cuerpo pila">
        <div class="fila" style="gap:14px">
            <span class="avatar" style="width:52px;height:52px;font-size:18px"><?= e(mb_strtoupper(mb_substr($yo['nombre'], 0, 1))) ?></span>
            <div>
                <div style="font-weight:700; font-size:17px"><?= e($yo['nombre']) ?></div>
                <div class="texto-suave"><?= e($yo['usuario']) ?></div>
            </div>
        </div>
        <div class="fila">
            <span class="insignia insignia-<?= e($yo['rol']) ?>"><?= $yo['rol'] === 'admin' ? 'Administrador' : 'Docente' ?></span>
        </div>
        <div class="stats" style="grid-template-columns:1fr 1fr">
            <div class="stat stat-total">
                <div class="stat-icono"><?= icono('nota') ?></div>
                <div><div class="stat-valor"><?= (int) $resumen['total'] ?></div><div class="stat-etiqueta">Novedades registradas</div></div>
            </div>
            <div class="stat stat-presente">
                <div class="stat-icono"><?= icono('calendario') ?></div>
                <div><div class="stat-valor"><?= (int) $resumen['hoy'] ?></div><div class="stat-etiqueta">Hoy</div></div>
            </div>
        </div>
    </div>

    <form action="../controller/perfil.php" method="POST" class="card">
        <?= csrf_campo() ?>
        <div class="card-cabecera"><span class="card-titulo fila" style="gap:8px"><?= icono('candado') ?> Cambiar contraseña</span></div>
        <div class="card-cuerpo pila">
            <div class="campo">
                <label for="actual">Contraseña actual</label>
                <input type="password" id="actual" name="actual" class="control" required autocomplete="current-password">
            </div>
            <div class="campo">
                <label for="nueva">Nueva contraseña</label>
                <input type="password" id="nueva" name="nueva" class="control" required minlength="6" autocomplete="new-password">
                <span class="ayuda">Mínimo 6 caracteres.</span>
            </div>
            <div class="campo">
                <label for="confirmar">Confirmar nueva contraseña</label>
                <input type="password" id="confirmar" name="confirmar" class="control" required minlength="6" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primario"><?= icono('guardar') ?> Actualizar contraseña</button>
        </div>
    </form>
</div>
<?php layout_fin(); ?>
