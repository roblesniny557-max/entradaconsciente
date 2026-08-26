<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
require_once "../model/conexion.php";

$db = conexion();

$docentes = [];
try {
    $stmtDocentes = $db->query("SELECT DISTINCT docente FROM inasistencias WHERE docente IS NOT NULL AND docente != '' ORDER BY docente ASC");
    $docentes = $stmtDocentes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $docentes = [];
}

$grados = [];
try {
    $stmtGrados = $db->query("SELECT DISTINCT grado FROM inasistencias WHERE grado IS NOT NULL AND grado != '' ORDER BY grado ASC");
    $grados = $stmtGrados->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $grados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte General de Inasistencias - IE Carlos Lleras Restrepo</title>
    
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
            --absent: #dc2626;
            --evaded: #d97706;
            --present: #16a34a;
            --db-blue: #0284c7;
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
            max-width: 1100px;
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
        }

        .nav-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-nav {
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.15s ease;
        }

        .btn-nav:hover {
            background: var(--primary-hover);
        }

        .filters-card {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .filter-control {
            padding: 0.45rem 0.6rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.85rem;
            outline: none;
            background: #ffffff;
            width: 100%;
        }

        .filter-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(30, 58, 31, 0.1);
        }

        .btn-filter {
            background: var(--db-blue);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.15s ease;
        }

        .btn-filter:hover {
            background: #0369a1;
        }

        .btn-clear {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        th {
            background-color: var(--primary);
            color: white;
            padding: 0.6rem 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        td {
            padding: 0.6rem 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        tr:hover {
            background-color: #f3f4f6;
        }

        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-ausente { background: #fee2e2; color: var(--absent); }
        .badge-evadido { background: #fef3c7; color: var(--evaded); }
        .badge-presente { background: #dcfce7; color: var(--present); }

        .empty-results {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-actions {
                width: 100%;
            }
            .btn-nav {
                width: 100%;
                text-align: center;
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
                    <img 
                        src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1ydV5pAMV7JIMln8X-w9GiedpSPnSVinjdJCvhRUyZawxcvrOtqDSAyZn&s=10" 
                        alt="Escudo IE Carlos Lleras Restrepo" 
                        class="school-logo"
                        onerror="this.onerror=null; this.src='https://i.ibb.co/VMybJd1s/escudo-lleras.png';"
                    >
                    <div>
                        <h1>Historial de Inasistencias y Novedades</h1>
                        <div class="subtitle">Institución Educativa Carlos Lleras Restrepo</div>
                    </div>
                </div>
                <div class="nav-actions">
                    <a href="index.php" class="btn-nav">📋 Volver a Toma de Asistencia</a>
                </div>
            </header>

            <div class="filters-card">
                <form id="filterForm" class="filters-grid">
                    <div class="filter-group">
                        <label for="fecha">Día / Fecha</label>
                        <input type="date" id="fecha" name="fecha" class="filter-control">
                    </div>

                    <div class="filter-group">
                        <label for="docente">Docente</label>
                        <select id="docente" name="docente" class="filter-control">
                            <option value="">-- Todos los Docentes --</option>
                            <?php foreach ($docentes as $doc): ?>
                                <option value="<?php echo htmlspecialchars($doc['docente']); ?>">
                                    <?php echo htmlspecialchars($doc['docente']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="grado">Grado</label>
                        <select id="grado" name="grado" class="filter-control">
                            <option value="">-- Todos los Grados --</option>
                            <?php foreach ($grados as $gr): ?>
                                <option value="<?php echo htmlspecialchars($gr['grado']); ?>">
                                    <?php echo htmlspecialchars($gr['grado']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="filter-control">
                            <option value="">-- Todos los Estados --</option>
                            <option value="ausente">Ausente</option>
                            <option value="evadido">Evadido</option>
                            <option value="presente">Presente (con observación)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn-filter" onclick="consultarRegistros()">🔍 Buscar</button>
                        <button type="button" class="btn-clear" onclick="limpiarFiltros()">Limpiar</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha / Registro</th>
                            <th>Estudiante</th>
                            <th>Grado</th>
                            <th>Materia</th>
                            <th>Docente</th>
                            <th>Estado</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody id="tablaResultados">
                        <!-- Datos renderizados mediante AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            consultarRegistros();
        });

        async function consultarRegistros() {
            const fecha = document.getElementById('fecha').value;
            const docente = document.getElementById('docente').value;
            const grado = document.getElementById('grado').value;
            const estado = document.getElementById('estado').value;

            const tbody = document.getElementById('tablaResultados');
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:1.5rem;">Cargando registros...</td></tr>';

            try {
                const response = await fetch('../controller/buscar_inasistencias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha, docente, grado, estado })
                });

                const data = await response.json();

                if (data.status === 'ok') {
                    renderizarTabla(data.registros);
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" class="empty-results">${data.message || 'Error al obtener registros.'}</td></tr>`;
                }
            } catch (err) {
                console.error('Error:', err);
                tbody.innerHTML = '<tr><td colspan="7" class="empty-results">Error de conexión con el servidor.</td></tr>';
            }
        }

        function renderizarTabla(registros) {
            const tbody = document.getElementById('tablaResultados');
            tbody.innerHTML = '';

            if (!registros || registros.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-results">No se encontraron registros para los filtros seleccionados.</td></tr>';
                return;
            }

            registros.forEach(reg => {
                const tr = document.createElement('tr');
                
                let badgeClass = 'badge-presente';
                const est = (reg.estado || '').toLowerCase();
                if (est === 'ausente') badgeClass = 'badge-ausente';
                if (est === 'evadido') badgeClass = 'badge-evadido';

                // Mapeo flexible de nombres de columnas
                const estudiante = reg.estudiante || reg.nombre || reg.alumno || 'N/A';
                const materia = reg.materia || reg.asignatura || 'N/A';
                const observacion = reg.observacion || reg.nota || reg.evidencia || '';

                tr.innerHTML = `
                    <td>${reg.fecha_formateada || 'N/A'}</td>
                    <td><strong>${estudiante}</strong></td>
                    <td>${reg.grado || 'N/A'}</td>
                    <td>${materia}</td>
                    <td>${reg.docente || 'N/A'}</td>
                    <td><span class="badge ${badgeClass}">${reg.estado || 'N/A'}</span></td>
                    <td>${observacion ? observacion : '<em>Sin observación</em>'}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function limpiarFiltros() {
            document.getElementById('filterForm').reset();
            consultarRegistros();
        }
    </script>
</body>
</html>