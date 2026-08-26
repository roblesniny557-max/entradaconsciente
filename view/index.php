<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
require_once "../model/conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia Grado 11B - IE Carlos Lleras Restrepo</title>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #1e3a1f;
            --primary-hover: #142815;
            --accent: #b89047;
            --bg: #f4f6f4;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #4b5563;
            --border: #d1d5db;
            --present: #16a34a;
            --absent: #dc2626;
            --evaded: #d97706;
            --whatsapp: #128c7e;
            --db-blue: #0284c7;
            --db-blue-hover: #0369a1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
        }

        .main-wrapper {
            padding: 1.5rem 0.5rem;
            display: flex;
            justify-content: center;
        }

        .container {
            max-width: 980px;
            width: 100%;
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        header {
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .school-logo {
            width: 75px;
            height: 75px;
            object-fit: contain;
        }

        h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 0.25rem;
            line-height: 1.4;
        }

        .info-tags {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .director-tag {
            font-size: 0.8rem;
            background: #f3f4f6;
            border-left: 3px solid var(--accent);
            padding: 0.25rem 0.6rem;
            border-radius: 0 4px 4px 0;
            color: var(--text-muted);
            text-align: right;
            line-height: 1.3;
        }

        .student-list {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .student-row {
            display: grid;
            grid-template-columns: 1.8fr 2.2fr 2.2fr auto;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #fafafa;
            transition: all 0.15s ease;
        }

        .student-row:hover {
            background: #f9fafb;
            border-color: var(--border);
        }

        .student-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-group {
            display: flex;
            gap: 0.25rem;
        }

        .status-btn {
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--border);
            background: #ffffff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.15s ease;
            flex: 1;
            text-align: center;
        }

        .status-btn:hover {
            background: #f3f4f6;
        }

        .status-btn.active[data-status="presente"] { background: var(--present); color: white; border-color: var(--present); }
        .status-btn.active[data-status="ausente"] { background: var(--absent); color: white; border-color: var(--absent); }
        .status-btn.active[data-status="evadido"] { background: var(--evaded); color: white; border-color: var(--evaded); }

        .feedback-input {
            padding: 0.3rem 0.5rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            width: 100%;
            font-size: 0.75rem;
            outline: none;
            transition: border-color 0.15s ease;
            background: #ffffff;
        }

        .feedback-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(30, 58, 31, 0.1);
        }

        .individual-actions {
            display: flex;
            gap: 0.3rem;
        }

        .wa-send-btn {
            background: var(--whatsapp);
            color: white;
            border: none;
            padding: 0.35rem 0.6rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.2rem;
            transition: background 0.15s ease;
        }

        .wa-send-btn:hover {
            filter: brightness(0.9);
        }

        .actions {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .action-buttons-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .reset-btn {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background 0.15s ease;
        }

        .reset-btn:hover {
            background: #d1d5db;
        }

        .db-batch-btn {
            background: var(--db-blue);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.15s ease;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2);
        }

        .db-batch-btn:hover {
            background: var(--db-blue-hover);
        }

        .save-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.15s ease;
            box-shadow: 0 2px 4px rgba(30, 58, 31, 0.2);
        }

        .save-btn:hover {
            background: var(--primary-hover);
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }
            .info-tags {
                width: 100%;
                align-items: flex-start;
            }
            .director-tag {
                text-align: left;
                width: 100%;
            }
            .student-row {
                grid-template-columns: 1fr;
                gap: 0.4rem;
                padding: 0.6rem;
            }
            .status-group {
                justify-content: space-between;
            }
            .individual-actions {
                width: 100%;
            }
            .wa-send-btn {
                width: 100%;
                justify-content: center;
            }
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            .action-buttons-group {
                flex-direction: column;
                width: 100%;
            }
            .reset-btn, .db-batch-btn, .save-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Integración del Navbar General -->
    <?php include_once "components/navbar.php"; ?>

    <div class="main-wrapper">
        <div class="container">
            <header>
                <div class="header-brand">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1ydV5pAMV7JIMln8X-w9GiedpSPnSVinjdJCvhRUyZawxcvrOtqDSAyZn&s=10" alt="Escudo Carlos Lleras Restrepo" class="school-logo">
                    <div>
                        <h1>Registro de Asistencia del Grado 11B</h1>
                        <div class="subtitle">
                            <p><strong>Curso:</strong> 11B • <strong>Área:</strong> Matemáticas</p>
                            <p><strong>Materia:</strong> Geometría • <strong>Fecha:</strong> <?php echo date('d/m/Y'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="info-tags">
                    <div class="director-tag">
                        Directora: <strong>Ana Milena Romero Vargas</strong><br>
                        Docente: <strong>Pascual Orduz Latorre</strong>
                    </div>
                </div>
            </header>

            <div class="student-list" id="student-list">
                <!-- Renderizado dinámico de estudiantes -->
            </div>

            <div class="actions">
                <button class="reset-btn" onclick="reiniciarAsistencia()">Limpiar Lista</button>
                <div class="action-buttons-group">
                    <button class="db-batch-btn" onclick="guardarLoteBD()">💾 Registrar Asistencia en BD</button>
                    <button class="save-btn" onclick="guardarDatosFinales()">📲 Guardar y Enviar Reporte WhatsApp</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const estudiantes = [
            "Ana Martínez", "Andrés Gómez", "Beatriz Rojas", "Carlos Ruiz", "Carmen Silva",
            "Daniela Herrera", "David Jiménez", "Elena Castro", "Esteban Morales", "Fernanda Ortiz",
            "Gabriel Mendoza", "Gloria Vargas", "Héctor Ríos", "Inés Navarro", "Javier Soto",
            "Julia Delgado", "Kevin Flores", "Laura Peña", "Luis Medina", "María Fuentes",
            "Martín Aguilar", "Natalia Reyes", "Nicolás Blanco", "Olivia Cruz", "Pablo Paredes",
            "Paula Salazar", "Ricardo Vega", "Sofía León", "Tomás Gil", "Valeria Mora"
        ];

        const listContainer = document.getElementById('student-list');
        const numeroWhatsApp = "573142520290";

        // Cargar estado inicial desde LocalStorage
        const datosGuardados = JSON.parse(localStorage.getItem('asistencia_11b_geo_corregida')) || {};

        // Renderizado dinámico
        estudiantes.forEach((nombre, index) => {
            const estadoGuardado = datosGuardados[nombre]?.estado || 'presente';
            const notaGuardada = datosGuardados[nombre]?.nota || '';

            const row = document.createElement('div');
            row.className = 'student-row';
            row.dataset.nombre = nombre;
            
            row.innerHTML = `
                <div class="student-name">${index + 1}. ${nombre}</div>
                <div class="status-group">
                    <button class="status-btn ${estadoGuardado === 'presente' ? 'active' : ''}" data-status="presente">Presente</button>
                    <button class="status-btn ${estadoGuardado === 'ausente' ? 'active' : ''}" data-status="ausente">Ausente</button>
                    <button class="status-btn ${estadoGuardado === 'evadido' ? 'active' : ''}" data-status="evadido">Evadido</button>
                </div>
                <div>
                    <input type="text" class="feedback-input" placeholder="Retroalimentación / Evidencia..." value="${notaGuardada}">
                </div>
                <div class="individual-actions">
                    <button class="wa-send-btn" onclick="notificarIndividualWA(this)" title="Avisar únicamente por WhatsApp al acudiente">
                        💬 Avisar
                    </button>
                </div>
            `;
            listContainer.appendChild(row);
        });

        // Escuchar cambios de estado
        listContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('status-btn')) {
                const btnSeleccionado = e.target;
                const grupo = btnSeleccionado.parentElement;
                const botones = grupo.querySelectorAll('.status-btn');

                botones.forEach(btn => btn.classList.remove('active'));
                btnSeleccionado.classList.add('active');

                guardarEnLocalStorage();
            }
        });

        // Guardar notas al escribir
        listContainer.addEventListener('input', (e) => {
            if (e.target.classList.contains('feedback-input')) {
                guardarEnLocalStorage();
            }
        });

        function guardarEnLocalStorage() {
            const filas = document.querySelectorAll('.student-row');
            const data = {};
            filas.forEach(fila => {
                const nombre = fila.dataset.nombre;
                const estado = fila.querySelector('.status-btn.active').dataset.status;
                const nota = fila.querySelector('.feedback-input').value;
                data[nombre] = { estado, nota };
            });
            localStorage.setItem('asistencia_11b_geo_corregida', JSON.stringify(data));
        }

        function obtenerHoraActual() {
            const ahora = new Date();
            let horas = ahora.getHours();
            const minutos = ahora.getMinutes().toString().padStart(2, '0');
            const ampm = horas >= 12 ? 'PM' : 'AM';
            horas = horas % 12;
            horas = horas ? horas : 12;
            return `${horas}:${minutos} ${ampm}`;
        }

        function notificarIndividualWA(btnElement) {
            const fila = btnElement.closest('.student-row');
            const nombre = fila.dataset.nombre;
            const estado = fila.querySelector('.status-btn.active').dataset.status;
            const nota = fila.querySelector('.feedback-input').value.trim();
            const horaActual = obtenerHoraActual();

            let mensaje = `Muy buenas tardes acudiente, soy el docente Pascual Orduz Latorre del área de matemáticas de la Institución Educativa Carlos Lleras Restrepo, este mensaje se envía de forma automática para reportar que la estudiante o el estudiante *${nombre}* a las *${horaActual}* se encuentra: *${estado.toUpperCase()}*.`;
            
            if (nota !== "") {
                mensaje += `\n\n*Nota / Observación:* ${nota}`;
            }

            const urlWhatsApp = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensaje)}`;
            window.open(urlWhatsApp, '_blank');
        }

        async function guardarLoteBD() {
            const filas = document.querySelectorAll('.student-row');
            let listaEstudiantesBD = [];

            filas.forEach(fila => {
                const nombre = fila.dataset.nombre;
                const estadoActivo = fila.querySelector('.status-btn.active').dataset.status;
                const nota = fila.querySelector('.feedback-input').value.trim();

                listaEstudiantesBD.push({
                    nombre: nombre,
                    estado: estadoActivo,
                    nota: nota
                });
            });

            try {
                const response = await fetch('../controller/guardar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        grado: '11B',
                        materia: 'Geometría',
                        docente: 'Pascual Orduz Latorre',
                        estudiantes: listaEstudiantesBD
                    })
                });
                const resData = await response.json();

                if (resData.status === 'ok') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Asistencia Registrada!',
                        text: resData.message,
                        confirmButtonColor: '#1e3a1f'
                    });
                } else {
                    Swal.fire('Error', resData.message || 'No se pudo registrar la asistencia.', 'error');
                }
            } catch (err) {
                console.error('Error en la petición AJAX:', err);
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        }

        async function guardarDatosFinales() {
            const filas = document.querySelectorAll('.student-row');
            let evadidos = [];
            let ausentes = [];
            let presentesCount = 0;
            let listaEstudiantesBD = [];

            filas.forEach(fila => {
                const nombre = fila.dataset.nombre;
                const estadoActivo = fila.querySelector('.status-btn.active').dataset.status;
                const nota = fila.querySelector('.feedback-input').value.trim();
                let textoNota = nota !== "" ? ` (Nota: ${nota})` : "";

                listaEstudiantesBD.push({
                    nombre: nombre,
                    estado: estadoActivo,
                    nota: nota
                });

                if (estadoActivo === 'evadido') {
                    evadidos.push(nombre + textoNota);
                } else if (estadoActivo === 'ausente') {
                    ausentes.push(nombre + textoNota);
                } else {
                    presentesCount++;
                }
            });

            try {
                const response = await fetch('../controller/guardar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        grado: '11B',
                        materia: 'Geometría',
                        docente: 'Pascual Orduz Latorre',
                        estudiantes: listaEstudiantesBD
                    })
                });
                const resData = await response.json();
                
                if (resData.status !== 'ok') {
                    console.error('Error al guardar reporte:', resData.message);
                }
            } catch (err) {
                console.error('Error en la petición AJAX:', err);
            }

            const fechaHoy = new Date().toLocaleDateString('es-CO');
            let mensaje = "📋 *Reporte de Asistencia - Grado 11B*\n";
            mensaje += "Área: *Matemáticas*\n";
            mensaje += "Materia: *Geometría*\n";
            mensaje += `Fecha: *${fechaHoy}*\n`;
            mensaje += "Docente: *Pascual Orduz Latorre*\n";
            mensaje += "Directora: *Ana Milena Romero Vargas*\n\n";
            
            mensaje += `📊 *Resumen:* ${presentesCount} Presentes / ${ausentes.length} Ausentes / ${evadidos.length} Evadidos\n\n`;

            if (ausentes.length > 0) {
                mensaje += "❌ *Ausentes:*\n- " + ausentes.join("\n- ") + "\n\n";
            } else {
                mensaje += "✅ Sin ausencias registradas.\n\n";
            }

            if (evadidos.length > 0) {
                mensaje += "⚠️ *Evadidos:*\n- " + evadidos.join("\n- ") + "\n";
            } else {
                mensaje += "👍 Sin estudiantes evadidos.";
            }

            window.open(`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensaje)}`, '_blank');
        }

        function reiniciarAsistencia() {
            Swal.fire({
                title: '¿Restablecer asistencia?',
                text: "Se marcarán todos los estudiantes como presentes y se limpiarán las observaciones.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1e3a1f',
                cancelButtonColor: '#dc2626',
                confirmButtonText: 'Sí, restablecer',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('asistencia_11b_geo_corregida');
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>