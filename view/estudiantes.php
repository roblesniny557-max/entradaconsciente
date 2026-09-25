<?php
require_once __DIR__ . '/components/layout.php';
requerir_admin();

$grados = lista_grados();
$gradoSel = trim($_GET['grado'] ?? '');
$estudiantes = [];
$conteoPorGrado = [];

try {
    $db = conexion();
    $conteoPorGrado = $db->query("SELECT grado, COUNT(*) FROM estudiantes GROUP BY grado")->fetchAll(PDO::FETCH_KEY_PAIR);

    $sql = "SELECT id, nombres, documento, grado,
                   COALESCE(NULLIF(celular_padres, ''), celular_padre) AS celular,
                   COALESCE(NULLIF(correo_padres, ''), correo_padre) AS correo
            FROM estudiantes";
    $params = [];
    if ($gradoSel !== '') {
        $sql .= " WHERE grado = ?";
        $params[] = $gradoSel;
    }
    $sql .= " ORDER BY grado, nombres";
    $st = $db->prepare($sql);
    $st->execute($params);
    $estudiantes = $st->fetchAll();
} catch (Exception $e) {
    flash('error', 'No se pudo consultar la base de datos.');
}

layout_inicio('Estudiantes', 'estudiantes');
?>
<div class="pagina-cabecera">
    <div>
        <h1 class="pagina-titulo">Estudiantes</h1>
        <p class="pagina-desc">Listado por grado y datos de contacto del acudiente para los avisos por WhatsApp.</p>
    </div>
    <div class="fila">
        <button type="button" class="btn btn-secundario" data-abrir="dlgImportar"><?= icono('subir') ?> Importar CSV</button>
        <button type="button" class="btn btn-primario" id="btnNuevo"><?= icono('mas') ?> Nuevo estudiante</button>
    </div>
</div>

<div class="card">
    <div class="card-cabecera">
        <form method="GET" class="fila">
            <select name="grado" class="control" style="width:auto; min-width:180px" onchange="this.form.submit()">
                <option value="">Todos los grados (<?= array_sum($conteoPorGrado) ?>)</option>
                <?php foreach ($grados as $g): ?>
                    <option value="<?= e($g) ?>" <?= $g === $gradoSel ? 'selected' : '' ?>><?= e($g) ?> (<?= (int) ($conteoPorGrado[$g] ?? 0) ?>)</option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="control-icono" style="min-width:240px">
            <?= icono('buscar') ?>
            <input type="search" id="buscar" class="control" placeholder="Buscar por nombre o documento...">
        </div>
    </div>

    <?php if (!$estudiantes): ?>
        <div class="vacio">
            <?= icono('estudiantes', 'ic-lg') ?>
            <p class="vacio-titulo">No hay estudiantes<?= $gradoSel ? ' en ' . e($gradoSel) : '' ?></p>
            <p>Créelos uno por uno o importe un archivo CSV con la lista del curso.</p>
        </div>
    <?php else: ?>
        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Grado</th>
                        <th>Celular acudiente</th>
                        <th>Correo acudiente</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tabla">
                    <?php foreach ($estudiantes as $est): ?>
                        <tr data-buscar="<?= e(mb_strtolower($est['nombres'] . ' ' . $est['documento'])) ?>">
                            <td><strong><?= e($est['nombres']) ?></strong></td>
                            <td><?= $est['documento'] ? e($est['documento']) : '<span class="texto-suave">—</span>' ?></td>
                            <td><span class="insignia insignia-docente"><?= e($est['grado']) ?></span></td>
                            <td><?= $est['celular'] ? e($est['celular']) : '<span class="texto-suave">Sin registrar</span>' ?></td>
                            <td><?= $est['correo'] ? e($est['correo']) : '<span class="texto-suave">—</span>' ?></td>
                            <td class="acciones">
                                <button type="button" class="btn btn-fantasma btn-icono btn-editar" title="Editar"
                                    data-est='<?= e(json_encode($est, JSON_UNESCAPED_UNICODE)) ?>'><?= icono('editar', 'ic-sm') ?></button>
                                <form action="../controller/estudiantes.php" method="POST"
                                      data-confirmar="¿Eliminar a <?= e($est['nombres']) ?>?"
                                      data-detalle="Se conservará su historial de asistencia." data-boton="Eliminar">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $est['id'] ?>">
                                    <input type="hidden" name="volver_grado" value="<?= e($gradoSel) ?>">
                                    <button type="submit" class="btn btn-fantasma btn-icono" title="Eliminar"><?= icono('eliminar', 'ic-sm') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Crear / editar -->
<dialog class="dialogo" id="dlgEstudiante">
    <form action="../controller/estudiantes.php" method="POST" id="formEstudiante">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="id" value="">
        <input type="hidden" name="volver_grado" value="<?= e($gradoSel) ?>">
        <div class="dialogo-cabecera">
            <span class="dialogo-titulo" id="tituloDlg">Nuevo estudiante</span>
            <button type="button" class="btn btn-fantasma btn-icono" data-cerrar aria-label="Cerrar"><?= icono('cerrar') ?></button>
        </div>
        <div class="dialogo-cuerpo">
            <div class="campo">
                <label for="f_nombres">Nombres y apellidos</label>
                <input type="text" id="f_nombres" name="nombres" class="control" required maxlength="150">
            </div>
            <div class="rejilla rejilla-2">
                <div class="campo">
                    <label for="f_documento">Documento</label>
                    <input type="text" id="f_documento" name="documento" class="control" maxlength="50" placeholder="Opcional">
                </div>
                <div class="campo">
                    <label for="f_grado">Grado</label>
                    <input type="text" id="f_grado" name="grado" class="control" list="listaGrados" required maxlength="20">
                </div>
            </div>
            <div class="rejilla rejilla-2">
                <div class="campo">
                    <label for="f_celular">Celular del acudiente</label>
                    <input type="tel" id="f_celular" name="celular" class="control" placeholder="3001234567">
                    <span class="ayuda">Se usa para los avisos por WhatsApp.</span>
                </div>
                <div class="campo">
                    <label for="f_correo">Correo del acudiente</label>
                    <input type="email" id="f_correo" name="correo" class="control" maxlength="100" placeholder="Opcional">
                </div>
            </div>
        </div>
        <div class="dialogo-pie">
            <button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primario"><?= icono('guardar') ?> Guardar</button>
        </div>
    </form>
</dialog>

<!-- Importar -->
<dialog class="dialogo" id="dlgImportar">
    <form action="../controller/estudiantes.php" method="POST" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="importar">
        <input type="hidden" name="volver_grado" value="<?= e($gradoSel) ?>">
        <div class="dialogo-cabecera">
            <span class="dialogo-titulo">Importar estudiantes</span>
            <button type="button" class="btn btn-fantasma btn-icono" data-cerrar aria-label="Cerrar"><?= icono('cerrar') ?></button>
        </div>
        <div class="dialogo-cuerpo">
            <div class="aviso">
                <?= icono('nota') ?>
                <div>
                    Archivo CSV (se puede guardar desde Excel) con las columnas en este orden:<br>
                    <strong>nombres ; documento ; grado ; celular acudiente ; correo acudiente</strong><br>
                    Solo el nombre es obligatorio. Se omiten los estudiantes que ya existan.
                </div>
            </div>
            <div class="campo">
                <label for="i_grado">Grado para las filas sin grado</label>
                <select id="i_grado" name="grado" class="control">
                    <option value="">—</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?= e($g) ?>" <?= $g === $gradoSel ? 'selected' : '' ?>><?= e($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="i_archivo">Archivo</label>
                <input type="file" id="i_archivo" name="archivo" class="control" accept=".csv,text/csv" required>
            </div>
        </div>
        <div class="dialogo-pie">
            <button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primario"><?= icono('subir') ?> Importar</button>
        </div>
    </form>
</dialog>

<datalist id="listaGrados">
    <?php foreach ($grados as $g): ?><option value="<?= e($g) ?>"><?php endforeach; ?>
</datalist>

<script>
(() => {
    const dlg = document.getElementById('dlgEstudiante');
    const form = document.getElementById('formEstudiante');

    function abrir(est) {
        form.reset();
        form.accion.value = est ? 'editar' : 'crear';
        form.elements.id.value = est?.id || '';
        document.getElementById('tituloDlg').textContent = est ? 'Editar estudiante' : 'Nuevo estudiante';
        form.nombres.value = est?.nombres || '';
        form.documento.value = est?.documento || '';
        form.grado.value = est?.grado || <?= json_encode($gradoSel) ?>;
        form.celular.value = est?.celular || '';
        form.correo.value = est?.correo || '';
        dlg.showModal();
        form.nombres.focus();
    }

    document.getElementById('btnNuevo').addEventListener('click', () => abrir(null));
    document.querySelectorAll('.btn-editar').forEach((b) => b.addEventListener('click', () => abrir(JSON.parse(b.dataset.est))));

    document.getElementById('buscar').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        document.querySelectorAll('#tabla tr').forEach((tr) => tr.classList.toggle('oculto', q !== '' && !tr.dataset.buscar.includes(q)));
    });
})();
</script>
<?php layout_fin(); ?>
