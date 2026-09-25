// view/assets/app.js — utilidades compartidas

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

/** fetch con token CSRF y manejo de errores en español. Lanza Error con un mensaje legible. */
async function api(url, { method = 'GET', data = null } = {}) {
    const opciones = { method, headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' } };
    if (data) {
        opciones.headers['Content-Type'] = 'application/json';
        opciones.body = JSON.stringify(data);
    }

    let respuesta;
    try {
        respuesta = await fetch(url, opciones);
    } catch (e) {
        throw new Error('No se pudo conectar con el servidor. Revise su conexión a internet.');
    }

    let json;
    try {
        json = await respuesta.json();
    } catch (e) {
        throw new Error(`El servidor respondió con un error inesperado (HTTP ${respuesta.status}).`);
    }

    if (respuesta.status === 401) {
        window.location.href = 'login.php';
    }
    if (!respuesta.ok || json.status !== 'ok') {
        throw new Error(json.message || 'Ocurrió un error desconocido.');
    }
    return json;
}

function esc(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function icono(nombre, clase = 'ic') {
    return `<svg class="${clase}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${(window.ICONOS || {})[nombre] || ''}</svg>`;
}

const swalBase = {
    confirmButtonColor: '#1c3b1e',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
};

function notificar(mensaje, tipo = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: mensaje,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });
}

function alerta(titulo, texto, tipo = 'error') {
    return Swal.fire({ ...swalBase, icon: tipo, title: titulo, text: texto });
}

async function confirmar(titulo, texto, textoBoton = 'Confirmar', peligro = false) {
    const r = await Swal.fire({
        ...swalBase,
        icon: peligro ? 'warning' : 'question',
        title: titulo,
        text: texto,
        showCancelButton: true,
        confirmButtonText: textoBoton,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: peligro ? '#b91c1c' : '#1c3b1e',
    });
    return r.isConfirmed;
}

/** Botón en estado de carga mientras corre una promesa. */
async function conCarga(boton, tarea) {
    const html = boton.innerHTML;
    boton.disabled = true;
    boton.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px"></span> Procesando...';
    try {
        return await tarea();
    } finally {
        boton.disabled = false;
        boton.innerHTML = html;
    }
}

// Formularios que piden confirmación: <form data-confirmar="¿Seguro?">
document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.dataset.confirmar || form.dataset.confirmado) return;
    e.preventDefault();
    if (await confirmar(form.dataset.confirmar, form.dataset.detalle || '', form.dataset.boton || 'Sí, continuar', true)) {
        form.dataset.confirmado = '1';
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }
});

// Diálogos: data-abrir="id" / data-cerrar
document.addEventListener('click', (e) => {
    const abrir = e.target.closest('[data-abrir]');
    if (abrir) {
        document.getElementById(abrir.dataset.abrir)?.showModal();
    }
    const cerrar = e.target.closest('[data-cerrar]');
    if (cerrar) {
        cerrar.closest('dialog')?.close();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Cerrar diálogo al hacer clic en el fondo
    document.querySelectorAll('dialog.dialogo').forEach((d) => {
        d.addEventListener('click', (e) => { if (e.target === d) d.close(); });
    });

    // Menú móvil
    document.getElementById('navToggle')?.addEventListener('click', () => {
        document.getElementById('nav').classList.toggle('abierto');
    });

    // Salir con confirmación
    document.getElementById('formSalir')?.addEventListener('submit', async (e) => {
        if (e.target.dataset.ok) return;
        e.preventDefault();
        if (await confirmar('¿Cerrar sesión?', 'Saldrá del sistema de asistencia.', 'Cerrar sesión')) {
            e.target.dataset.ok = '1';
            e.target.submit();
        }
    });
});
