<?php
require_once __DIR__ . '/components/layout.php';
requerir_login();

$docentes = $grados = $materias = [];
try {
    $db = conexion();
    $docentes = $db->query("SELECT DISTINCT docente FROM inasistencias WHERE docente <> '' ORDER BY docente")->fetchAll(PDO::FETCH_COLUMN);
    $grados   = $db->query("SELECT DISTINCT grado FROM inasistencias WHERE grado <> ''")->fetchAll(PDO::FETCH_COLUMN);
    $materias = $db->query("SELECT DISTINCT materia FROM inasistencias WHERE materia <> '' ORDER BY materia")->fetchAll(PDO::FETCH_COLUMN);
    usort($grados, 'strnatcasecmp');
} catch (Exception $e) {
    flash('error', 'No se pudieron cargar los filtros: sin conexión con la base de datos.');
}

layout_inicio('Reportes', 'reportes');
?>
<div class="pagina-cabecera">
    <div>
        <h1 class="pagina-titulo">Reportes de asistencia</h1>
        <p class="pagina-desc">Historial de ausencias, evasiones y observaciones registradas.</p>
    </div>
    <a href="#" class="btn btn-secundario" id="btnExportar"><?= icono('descargar') ?> Exportar a Excel (CSV)</a>
</div>

<div class="pila">
    <div class="card card-cuerpo">
        <form id="filtros" class="rejilla rejilla-filtros">
            <div class="campo">
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" class="control">
            </div>
            <div class="campo">
                <label for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" class="control">
            </div>
            <div class="campo">
                <label for="grado">Grado</label>
                <select id="grado" name="grado" class="control">
                    <option value="">Todos</option>
                    <?php foreach ($grados as $g): ?><option><?= e($g) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="materia">Materia</label>
                <select id="materia" name="materia" class="control">
                    <option value="">Todas</option>
                    <?php foreach ($materias as $m): ?><option><?= e($m) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="docente">Docente</label>
                <select id="docente" name="docente" class="control">
                    <option value="">Todos</option>
                    <?php foreach ($docentes as $d): ?><option><?= e($d) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" class="control">
                    <option value="">Todos</option>
                    <option value="ausente">Ausente</option>
                    <option value="evadido">Evadido</option>
                    <option value="presente">Presente con observación</option>
                </select>
            </div>
            <div class="campo">
                <label for="q">Estudiante</label>
                <input type="search" id="q" name="q" class="control" placeholder="Nombre...">
            </div>
            <div class="fila" style="flex-wrap:nowrap">
                <button type="submit" class="btn btn-primario" style="flex:1"><?= icono('buscar') ?> Buscar</button>
                <button type="button" class="btn btn-secundario btn-icono" id="btnLimpiar" title="Limpiar filtros"><?= icono('reiniciar') ?></button>
            </div>
        </form>
    </div>

    <div class="stats" id="stats"></div>

    <div class="rejilla" style="grid-template-columns: minmax(0, 1fr) 300px; align-items:start" id="rejillaReporte">
        <div class="card">
            <div class="card-cabecera">
                <span class="card-titulo">Registros</span>
                <span class="texto-suave" style="font-size:13px" id="contador"></span>
            </div>
            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Grado</th>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Estado</th>
                            <th>Observación</th>
                            <?php if (es_admin()): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tabla"></tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-cabecera"><span class="card-titulo">Estudiantes con más novedades</span></div>
            <div id="ranking"></div>
        </div>
    </div>
</div>

<style>
    @media (max-width: 1000px) { #rejillaReporte { grid-template-columns: 1fr !important; } }
    .ranking-fila { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 20px; border-bottom:1px solid var(--borde); font-size:14px; }
    .ranking-fila:last-child { border-bottom:none; }
</style>

<script>
(() => {
    const ES_ADMIN = <?= json_encode(es_admin()) ?>;
    const COLUMNAS = ES_ADMIN ? 8 : 7;
    const form = document.getElementById('filtros');
    const tabla = document.getElementById('tabla');

    const parametros = () => new URLSearchParams(new FormData(form)).toString();

    async function buscar() {
        tabla.innerHTML = `<tr><td colspan="${COLUMNAS}"><div class="cargando"><span class="spinner"></span> Cargando registros...</div></td></tr>`;
        try {
            const r = await api(`../controller/reportes.php?accion=buscar&${parametros()}`);
            pintarStats(r.resumen);
            pintarTabla(r.registros, r.limitado);
            pintarRanking(r.ranking);
        } catch (err) {
            tabla.innerHTML = `<tr><td colspan="${COLUMNAS}"><div class="vacio">${icono('evadido', 'ic-lg')}<p class="vacio-titulo">No se pudieron cargar los registros</p><p>${esc(err.message)}</p></div></td></tr>`;
        }
    }

    function pintarStats(s) {
        const tarjeta = (clase, ic, valor, etiqueta) => `
            <div class="stat stat-${clase}">
                <div class="stat-icono">${icono(ic)}</div>
                <div><div class="stat-valor">${valor}</div><div class="stat-etiqueta">${etiqueta}</div></div>
            </div>`;
        document.getElementById('stats').innerHTML =
            tarjeta('total', 'nota', s.total, 'Registros') +
            tarjeta('ausente', 'ausente', s.ausente, 'Ausencias') +
            tarjeta('evadido', 'evadido', s.evadido, 'Evasiones') +
            tarjeta('presente', 'presente', s.presente, 'Observaciones');
    }

    function pintarTabla(registros, limitado) {
        document.getElementById('contador').textContent =
            `${registros.length} registro(s)${limitado ? ' · se muestran los 500 más recientes, exporte para ver todos' : ''}`;

        if (!registros.length) {
            tabla.innerHTML = `<tr><td colspan="${COLUMNAS}"><div class="vacio">${icono('vacio', 'ic-lg')}<p class="vacio-titulo">Sin resultados</p><p>No hay registros para los filtros seleccionados.</p></div></td></tr>`;
            return;
        }

        tabla.innerHTML = registros.map((r) => `
            <tr data-id="${r.id}">
                <td style="white-space:nowrap">${esc(r.fecha)}<br><span class="texto-suave" style="font-size:12px">${esc(r.hora)}</span></td>
                <td class="nowrap"><strong>${esc(r.estudiante)}</strong></td>
                <td>${esc(r.grado)}</td>
                <td>${esc(r.materia)}</td>
                <td class="nowrap">${esc(r.docente)}</td>
                <td><span class="insignia insignia-${esc(r.estado)}">${icono(r.estado, 'ic-sm')} ${esc(r.estado)}</span></td>
                <td class="celda-obs">${r.observacion ? esc(r.observacion) : '<span class="texto-suave">—</span>'}</td>
                ${ES_ADMIN ? `<td class="acciones"><button class="btn btn-fantasma btn-icono btn-eliminar" title="Eliminar registro">${icono('eliminar', 'ic-sm')}</button></td>` : ''}
            </tr>`).join('');
    }

    function pintarRanking(lista) {
        const cont = document.getElementById('ranking');
        if (!lista.length) {
            cont.innerHTML = `<div class="vacio" style="padding:28px 20px">${icono('presente', 'ic-lg')}<p>Sin ausencias ni evasiones en este periodo.</p></div>`;
            return;
        }
        cont.innerHTML = lista.map((r) => `
            <div class="ranking-fila">
                <div style="min-width:0"><strong>${esc(r.estudiante)}</strong><div class="texto-suave" style="font-size:12px">Grado ${esc(r.grado)}</div></div>
                <div class="fila" style="gap:6px; flex-wrap:nowrap">
                    ${+r.ausencias ? `<span class="insignia insignia-ausente" title="Ausencias">${r.ausencias}</span>` : ''}
                    ${+r.evasiones ? `<span class="insignia insignia-evadido" title="Evasiones">${r.evasiones}</span>` : ''}
                </div>
            </div>`).join('');
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); buscar(); });
    form.querySelectorAll('select, input[type=date]').forEach((c) => c.addEventListener('change', buscar));

    document.getElementById('btnLimpiar').addEventListener('click', () => {
        form.reset();
        buscar();
    });

    document.getElementById('btnExportar').addEventListener('click', (e) => {
        e.preventDefault();
        window.location.href = `../controller/reportes.php?accion=exportar&${parametros()}`;
    });

    tabla.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-eliminar');
        if (!btn) return;
        const fila = btn.closest('tr');
        if (!(await confirmar('¿Eliminar este registro?', 'Esta acción no se puede deshacer.', 'Eliminar', true))) return;
        try {
            const r = await api('../controller/reportes.php?accion=eliminar', { method: 'POST', data: { id: +fila.dataset.id } });
            notificar(r.message);
            buscar();
        } catch (err) {
            alerta('No se pudo eliminar', err.message);
        }
    });

    buscar();
})();
</script>
<?php layout_fin(); ?>
