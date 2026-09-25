<?php
require_once __DIR__ . '/components/layout.php';
$yo = requerir_admin();

$usuarios = [];
try {
    $usuarios = conexion()->query(
        "SELECT u.id, u.nombre, u.usuario, u.rol,
                DATE_FORMAT(u.created_at, '%d/%m/%Y') AS creado,
                (SELECT COUNT(*) FROM inasistencias i WHERE i.docente = u.nombre) AS registros
         FROM usuarios u
         ORDER BY u.rol, u.nombre"
    )->fetchAll();
} catch (Exception $e) {
    flash('error', 'No se pudo consultar la base de datos.');
}

layout_inicio('Usuarios', 'usuarios');
?>
<div class="pagina-cabecera">
    <div>
        <h1 class="pagina-titulo">Usuarios</h1>
        <p class="pagina-desc">Docentes y administradores con acceso al sistema.</p>
    </div>
    <button type="button" class="btn btn-primario" id="btnNuevo"><?= icono('mas') ?> Nuevo usuario</button>
</div>

<div class="card">
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Registros</th>
                    <th>Creado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?= e($u['nombre']) ?></strong><?= (int) $u['id'] === (int) $yo['id'] ? ' <span class="texto-suave">(usted)</span>' : '' ?></td>
                        <td><?= e($u['usuario']) ?></td>
                        <td><span class="insignia insignia-<?= e($u['rol']) ?>"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Docente' ?></span></td>
                        <td><?= (int) $u['registros'] ?></td>
                        <td class="texto-suave"><?= e($u['creado'] ?? '—') ?></td>
                        <td class="acciones">
                            <button type="button" class="btn btn-fantasma btn-icono btn-editar" title="Editar"
                                data-u='<?= e(json_encode($u, JSON_UNESCAPED_UNICODE)) ?>'><?= icono('editar', 'ic-sm') ?></button>
                            <button type="button" class="btn btn-fantasma btn-icono btn-clave" title="Restablecer contraseña"
                                data-id="<?= (int) $u['id'] ?>" data-nombre="<?= e($u['nombre']) ?>"><?= icono('llave', 'ic-sm') ?></button>
                            <?php if ((int) $u['id'] !== (int) $yo['id']): ?>
                                <form action="../controller/usuarios.php" method="POST"
                                      data-confirmar="¿Eliminar el usuario <?= e($u['usuario']) ?>?"
                                      data-detalle="Ya no podrá ingresar. Sus registros de asistencia se conservan." data-boton="Eliminar">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-fantasma btn-icono" title="Eliminar"><?= icono('eliminar', 'ic-sm') ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<dialog class="dialogo" id="dlgUsuario">
    <form action="../controller/usuarios.php" method="POST" id="formUsuario">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="id" value="">
        <div class="dialogo-cabecera">
            <span class="dialogo-titulo" id="tituloDlg">Nuevo usuario</span>
            <button type="button" class="btn btn-fantasma btn-icono" data-cerrar aria-label="Cerrar"><?= icono('cerrar') ?></button>
        </div>
        <div class="dialogo-cuerpo">
            <div class="campo">
                <label for="u_nombre">Nombre completo</label>
                <input type="text" id="u_nombre" name="nombre" class="control" required maxlength="100">
                <span class="ayuda">Aparece en los reportes y en los mensajes a los acudientes.</span>
            </div>
            <div class="rejilla rejilla-2">
                <div class="campo">
                    <label for="u_usuario">Usuario</label>
                    <input type="text" id="u_usuario" name="usuario" class="control" required pattern="[A-Za-z0-9._\-]{3,50}" placeholder="nombre.apellido" autocomplete="off">
                </div>
                <div class="campo">
                    <label for="u_rol">Rol</label>
                    <select id="u_rol" name="rol" class="control">
                        <option value="docente">Docente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            <div class="campo" id="campoPassword">
                <label for="u_password">Contraseña inicial</label>
                <input type="text" id="u_password" name="password" class="control" minlength="6" autocomplete="off">
                <span class="ayuda">Mínimo 6 caracteres. El docente puede cambiarla en "Mi perfil".</span>
            </div>
        </div>
        <div class="dialogo-pie">
            <button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primario"><?= icono('guardar') ?> Guardar</button>
        </div>
    </form>
</dialog>

<dialog class="dialogo" id="dlgClave">
    <form action="../controller/usuarios.php" method="POST" id="formClave">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="restablecer">
        <input type="hidden" name="id" value="">
        <div class="dialogo-cabecera">
            <span class="dialogo-titulo">Restablecer contraseña</span>
            <button type="button" class="btn btn-fantasma btn-icono" data-cerrar aria-label="Cerrar"><?= icono('cerrar') ?></button>
        </div>
        <div class="dialogo-cuerpo">
            <p id="claveDe"></p>
            <div class="campo">
                <label for="c_password">Nueva contraseña</label>
                <input type="text" id="c_password" name="password" class="control" required minlength="6" autocomplete="off">
            </div>
        </div>
        <div class="dialogo-pie">
            <button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primario"><?= icono('llave') ?> Restablecer</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const form = document.getElementById('formUsuario');

    function abrir(u) {
        form.reset();
        form.accion.value = u ? 'editar' : 'crear';
        form.elements.id.value = u?.id || '';
        document.getElementById('tituloDlg').textContent = u ? 'Editar usuario' : 'Nuevo usuario';
        form.nombre.value = u?.nombre || '';
        form.usuario.value = u?.usuario || '';
        form.rol.value = u?.rol || 'docente';
        document.getElementById('campoPassword').classList.toggle('oculto', !!u);
        form.password.required = !u;
        document.getElementById('dlgUsuario').showModal();
    }

    document.getElementById('btnNuevo').addEventListener('click', () => abrir(null));
    document.querySelectorAll('.btn-editar').forEach((b) => b.addEventListener('click', () => abrir(JSON.parse(b.dataset.u))));

    document.querySelectorAll('.btn-clave').forEach((b) => b.addEventListener('click', () => {
        const f = document.getElementById('formClave');
        f.reset();
        f.elements.id.value = b.dataset.id;
        document.getElementById('claveDe').textContent = `Usuario: ${b.dataset.nombre}`;
        document.getElementById('dlgClave').showModal();
    }));
})();
</script>
<?php layout_fin(); ?>
