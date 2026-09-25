<?php
require_once __DIR__ . '/components/layout.php';
$usuario = requerir_login();

$grados = lista_grados();
$materias = config('materias');
try {
    $usadas = conexion()->query("SELECT DISTINCT materia FROM inasistencias WHERE materia <> ''")->fetchAll(PDO::FETCH_COLUMN);
    $materias = array_values(array_unique(array_merge($materias, $usadas)));
} catch (Exception $e) {
    // Se usa solo el catálogo
}
sort($materias, SORT_LOCALE_STRING);

$dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaLarga = ucfirst($dias[date('w')]) . ', ' . date('j') . ' de ' . $meses[(int) date('n')] . ' de ' . date('Y');

layout_inicio('Tomar asistencia', 'asistencia');
?>
<div class="pagina-cabecera">
    <div>
        <h1 class="pagina-titulo">Tomar asistencia</h1>
        <p class="pagina-desc"><?= icono('calendario', 'ic-sm') ?> <?= e($fechaLarga) ?> · Docente: <strong><?= e($usuario['nombre']) ?></strong></p>
    </div>
</div>

<div class="pila">
    <div class="card card-cuerpo">
        <form id="formClase" class="rejilla rejilla-filtros">
            <div class="campo">
                <label for="grado">Grado</label>
                <select id="grado" class="control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?= e($g) ?>"><?= e($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo" style="grid-column: span 2">
                <label for="materia">Materia</label>
                <input id="materia" class="control" list="listaMaterias" placeholder="Escriba o seleccione la materia" required maxlength="100">
                <datalist id="listaMaterias">
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= e($m) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <button type="submit" class="btn btn-primario"><?= icono('asistencia') ?> Cargar lista</button>
        </form>
    </div>

    <div id="estadoInicial" class="card vacio">
        <?= icono('asistencia', 'ic-lg') ?>
        <p class="vacio-titulo">Seleccione un grado y una materia</p>
        <p>Se cargará la lista de estudiantes del grado. Todos inician como presentes.</p>
    </div>

    <div id="panelLista" class="card oculto">
        <div class="card-cabecera">
            <div>
                <div class="card-titulo" id="tituloClase"></div>
                <div class="texto-suave" style="font-size:13px" id="infoClase"></div>
            </div>
            <div class="fila">
                <div class="control-icono" style="min-width:220px">
                    <?= icono('buscar') ?>
                    <input type="search" id="buscar" class="control" placeholder="Buscar estudiante...">
                </div>
                <button type="button" class="btn btn-secundario" id="btnTodosPresentes"><?= icono('reiniciar') ?> Todos presentes</button>
            </div>
        </div>

        <div class="lista-estudiantes" id="lista"></div>

        <div class="barra-acciones">
            <div class="resumen-chips" id="resumen"></div>
            <div class="fila">
                <button type="button" class="btn btn-whatsapp" id="btnReporte"><?= icono('enviar') ?> Guardar y enviar reporte</button>
                <button type="button" class="btn btn-primario" id="btnGuardar"><?= icono('guardar') ?> Guardar asistencia</button>
            </div>
        </div>
    </div>
</div>

<script>
const DOCENTE = <?= json_encode($usuario['nombre']) ?>;
const INSTITUCION = <?= json_encode(config('institucion')) ?>;
const WHATSAPP_COORDINACION = <?= json_encode(telefono_whatsapp(config('whatsapp_coordinacion'))) ?>;
const ESTADOS = [
    ['presente', 'Presente'],
    ['ausente', 'Ausente'],
    ['evadido', 'Evadido'],
];

(() => {
    const $ = (id) => document.getElementById(id);
    const lista = $('lista');
    let clase = null;        // { grado, materia, director }
    let estudiantes = [];    // [{ id, nombre, celular, whatsapp, estado, nota }]
    let cambiosSinGuardar = false;

    // Recordar la última clase usada
    try {
        const ultima = JSON.parse(localStorage.getItem('ec_ultima_clase') || 'null');
        if (ultima) {
            $('grado').value = ultima.grado;
            $('materia').value = ultima.materia;
        }
    } catch (e) {}

    $('formClase').addEventListener('submit', async (e) => {
        e.preventDefault();
        const grado = $('grado').value;
        const materia = $('materia').value.trim();
        if (!grado || !materia) return;

        if (cambiosSinGuardar && !(await confirmar('Hay cambios sin guardar', 'Si carga otra lista se perderán los cambios actuales.', 'Cargar de todas formas', true))) {
            return;
        }
        await cargar(grado, materia);
    });

    async function cargar(grado, materia) {
        $('estadoInicial').classList.add('oculto');
        $('panelLista').classList.remove('oculto');
        lista.innerHTML = '<div class="cargando"><span class="spinner"></span> Cargando estudiantes...</div>';

        try {
            const r = await api(`../controller/asistencia.php?accion=cargar&grado=${encodeURIComponent(grado)}&materia=${encodeURIComponent(materia)}`);
            clase = { grado, materia, director: r.director };
            estudiantes = r.estudiantes.map((est) => ({
                ...est,
                estado: r.guardados[est.nombre]?.estado || 'presente',
                nota: r.guardados[est.nombre]?.nota || '',
            }));
            try { localStorage.setItem('ec_ultima_clase', JSON.stringify({ grado, materia })); } catch (e) {}

            $('tituloClase').textContent = `Grado ${grado} · ${materia}`;
            actualizarInfo(r.ultimo);
            cambiosSinGuardar = false;
            $('buscar').value = '';
            pintar();
        } catch (err) {
            lista.innerHTML = `<div class="vacio">${icono('evadido', 'ic-lg')}<p class="vacio-titulo">No se pudo cargar la lista</p><p>${esc(err.message)}</p></div>`;
        }
    }

    function actualizarInfo(ultimo) {
        const partes = [`${estudiantes.length} estudiantes`];
        if (clase.director) partes.push(`Director(a) de grupo: ${clase.director}`);
        partes.push(ultimo ? `Guardado hoy a las ${ultimo}` : 'Aún no se ha guardado hoy');
        $('infoClase').textContent = partes.join(' · ');
    }

    function pintar() {
        if (!estudiantes.length) {
            lista.innerHTML = `<div class="vacio">${icono('estudiantes', 'ic-lg')}<p class="vacio-titulo">Este grado no tiene estudiantes</p><p>Un administrador puede agregarlos en la sección Estudiantes.</p></div>`;
            pintarResumen();
            return;
        }

        lista.innerHTML = estudiantes.map((est, i) => `
            <div class="estudiante es-${est.estado}" data-i="${i}">
                <span class="estudiante-num">${i + 1}</span>
                <div class="estudiante-info" style="min-width:0">
                    <div class="estudiante-nombre">${esc(est.nombre)}</div>
                    <div class="estudiante-contacto">${est.celular ? 'Acudiente: ' + esc(est.celular) : 'Sin celular de acudiente'}</div>
                </div>
                <div class="segmentado" role="radiogroup" aria-label="Estado de ${esc(est.nombre)}">
                    ${ESTADOS.map(([valor, texto]) => `
                        <button type="button" role="radio" data-estado="${valor}" aria-checked="${est.estado === valor}"
                            class="${est.estado === valor ? 'activo' : ''}">${icono(valor, 'ic-sm')} ${texto}</button>`).join('')}
                </div>
                <input type="text" class="control nota" placeholder="Observación (opcional)" maxlength="1000" value="${esc(est.nota)}">
                <button type="button" class="btn btn-secundario btn-sm btn-aviso" ${est.whatsapp ? '' : 'disabled'}
                    title="${est.whatsapp ? 'Avisar al acudiente por WhatsApp' : 'El estudiante no tiene celular de acudiente registrado'}">
                    ${icono('mensaje', 'ic-sm')} Avisar
                </button>
            </div>`).join('');
        filtrar();
        pintarResumen();
    }

    function contar() {
        const c = { presente: 0, ausente: 0, evadido: 0 };
        estudiantes.forEach((e) => c[e.estado]++);
        return c;
    }

    function pintarResumen() {
        const c = contar();
        $('resumen').innerHTML = `
            <span class="insignia insignia-presente">${icono('presente', 'ic-sm')} ${c.presente} presentes</span>
            <span class="insignia insignia-ausente">${icono('ausente', 'ic-sm')} ${c.ausente} ausentes</span>
            <span class="insignia insignia-evadido">${icono('evadido', 'ic-sm')} ${c.evadido} evadidos</span>`;
    }

    function filtrar() {
        const q = $('buscar').value.trim().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
        lista.querySelectorAll('.estudiante').forEach((fila) => {
            const nombre = estudiantes[fila.dataset.i].nombre.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
            fila.classList.toggle('oculto', q !== '' && !nombre.includes(q));
        });
    }

    $('buscar').addEventListener('input', filtrar);

    lista.addEventListener('click', (e) => {
        const fila = e.target.closest('.estudiante');
        if (!fila) return;
        const est = estudiantes[fila.dataset.i];

        const botonEstado = e.target.closest('[data-estado]');
        if (botonEstado) {
            est.estado = botonEstado.dataset.estado;
            fila.className = `estudiante es-${est.estado}`;
            fila.querySelectorAll('[data-estado]').forEach((b) => {
                const activo = b === botonEstado;
                b.classList.toggle('activo', activo);
                b.setAttribute('aria-checked', activo);
            });
            cambiosSinGuardar = true;
            pintarResumen();
            return;
        }

        if (e.target.closest('.btn-aviso')) {
            avisarAcudiente(est);
        }
    });

    lista.addEventListener('input', (e) => {
        if (e.target.classList.contains('nota')) {
            estudiantes[e.target.closest('.estudiante').dataset.i].nota = e.target.value;
            cambiosSinGuardar = true;
        }
    });

    $('btnTodosPresentes').addEventListener('click', async () => {
        if (!(await confirmar('¿Marcar a todos como presentes?', 'Se borrarán también las observaciones escritas.', 'Sí, marcar todos'))) return;
        estudiantes.forEach((e) => { e.estado = 'presente'; e.nota = ''; });
        cambiosSinGuardar = true;
        pintar();
    });

    async function guardar() {
        const r = await api('../controller/asistencia.php?accion=guardar', {
            method: 'POST',
            data: {
                grado: clase.grado,
                materia: clase.materia,
                registros: estudiantes.map(({ id, estado, nota }) => ({ id, estado, nota: nota.trim() })),
            },
        });
        cambiosSinGuardar = false;
        actualizarInfo(r.ultimo);
        return r;
    }

    $('btnGuardar').addEventListener('click', (e) => {
        if (!clase || !estudiantes.length) return;
        conCarga(e.currentTarget, async () => {
            try {
                const r = await guardar();
                notificar(r.message);
            } catch (err) {
                alerta('No se pudo guardar', err.message);
            }
        });
    });

    $('btnReporte').addEventListener('click', (e) => {
        if (!clase || !estudiantes.length) return;
        // La ventana se abre antes del await para que el navegador no la bloquee
        const ventana = window.open('', '_blank');
        conCarga(e.currentTarget, async () => {
            try {
                await guardar();
                const url = urlWhatsApp(WHATSAPP_COORDINACION, mensajeReporte());
                if (ventana) ventana.location.href = url; else window.location.href = url;
                notificar('Asistencia guardada. Se abrió WhatsApp con el reporte.');
            } catch (err) {
                ventana?.close();
                alerta('No se pudo guardar', err.message);
            }
        });
    });

    function urlWhatsApp(numero, texto) {
        return `https://wa.me/${numero || ''}?text=${encodeURIComponent(texto)}`;
    }

    function saludo() {
        const h = new Date().getHours();
        return h < 12 ? 'Buenos días' : h < 18 ? 'Buenas tardes' : 'Buenas noches';
    }

    function horaActual() {
        return new Date().toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    function avisarAcudiente(est) {
        let msg = `${saludo()}, señor(a) acudiente. Le escribe ${DOCENTE}, docente de ${clase.materia} de la ${INSTITUCION}. `
            + `Le informamos que el/la estudiante *${est.nombre}* (grado ${clase.grado}) a las *${horaActual()}* se encuentra: *${est.estado.toUpperCase()}*.`;
        if (est.nota.trim()) msg += `\n\n*Observación:* ${est.nota.trim()}`;
        window.open(urlWhatsApp(est.whatsapp, msg), '_blank');
    }

    function mensajeReporte() {
        const c = contar();
        const con = (estado) => estudiantes.filter((e) => e.estado === estado)
            .map((e) => `- ${e.nombre}${e.nota.trim() ? ` (${e.nota.trim()})` : ''}`).join('\n');
        const obsPresentes = estudiantes.filter((e) => e.estado === 'presente' && e.nota.trim())
            .map((e) => `- ${e.nombre}: ${e.nota.trim()}`).join('\n');

        let msg = `*Reporte de asistencia - Grado ${clase.grado}*\n`
            + `Materia: *${clase.materia}*\n`
            + `Fecha: *${new Date().toLocaleDateString('es-CO')}* - ${horaActual()}\n`
            + `Docente: *${DOCENTE}*\n`
            + (clase.director ? `Director(a) de grupo: *${clase.director}*\n` : '')
            + `\n*Resumen:* ${c.presente} presentes / ${c.ausente} ausentes / ${c.evadido} evadidos\n\n`;

        msg += c.ausente ? `*Ausentes:*\n${con('ausente')}\n\n` : 'Sin ausencias.\n\n';
        msg += c.evadido ? `*Evadidos:*\n${con('evadido')}\n` : 'Sin estudiantes evadidos.\n';
        if (obsPresentes) msg += `\n*Observaciones:*\n${obsPresentes}\n`;
        return msg;
    }

    window.addEventListener('beforeunload', (e) => {
        if (cambiosSinGuardar) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Cargar automáticamente la última clase usada
    if ($('grado').value && $('materia').value) {
        cargar($('grado').value, $('materia').value.trim());
    }
})();
</script>
<?php layout_fin(); ?>
