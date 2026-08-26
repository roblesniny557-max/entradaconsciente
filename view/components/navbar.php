<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sesionActiva = isset($_SESSION['usuario']);
$nombreUsuario = $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario';
$rolUsuario = $_SESSION['rol'] ?? 'docente';
?>
<!-- SweetAlert2 para mensajes y confirmación de salida -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary: #1e3a1f;
        --primary-hover: #142815;
        --accent: #b89047;
        --navbar-bg: #ffffff;
        --text-main: #1f2937;
        --text-muted: #6b7280;
        --border-color: #e5e7eb;
        --danger: #dc2626;
        --danger-hover: #b91c1c;
    }

    .main-navbar {
        background-color: var(--navbar-bg);
        border-bottom: 2px solid var(--primary);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 0;
        z-index: 1000;
        width: 100%;
    }

    .navbar-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0.6rem 1.2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        color: var(--primary);
    }

    .navbar-logo {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 50%;
        mix-blend-mode: multiply;
    }

    .navbar-title {
        font-size: 1.05rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }

    .navbar-subtitle {
        font-size: 0.72rem;
        color: var(--text-muted);
        font-weight: 600;
    }

    .navbar-menu {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        list-style: none;
    }

    .nav-item a {
        text-decoration: none;
        color: var(--text-main);
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.5rem 0.8rem;
        border-radius: 6px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .nav-item a:hover {
        background-color: #f3f4f6;
        color: var(--primary);
    }

    .nav-item a.active {
        background-color: var(--primary);
        color: #ffffff;
    }

    .user-info-badge {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        padding: 0 0.5rem;
        border-right: 1px solid var(--border-color);
        margin-right: 0.2rem;
    }

    .user-name {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--primary);
    }

    .user-role {
        font-size: 0.68rem;
        background: #e0f2fe;
        color: #0369a1;
        padding: 0.1rem 0.4rem;
        border-radius: 4px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .btn-logout {
        background-color: #fee2e2;
        color: var(--danger) !important;
        border: 1px solid #fca5a5;
    }

    .btn-logout:hover {
        background-color: var(--danger) !important;
        color: #ffffff !important;
    }

    @media (max-width: 768px) {
        .navbar-container {
            flex-direction: column;
            gap: 0.75rem;
        }

        .navbar-menu {
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .user-info-badge {
            border-right: none;
            align-items: center;
            margin-right: 0;
        }
    }
</style>

<nav class="main-navbar">
    <div class="navbar-container">
        <!-- Marca y Logo de la Institución -->
        <a href="index.php" class="navbar-brand">
            <img 
                src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1ydV5pAMV7JIMln8X-w9GiedpSPnSVinjdJCvhRUyZawxcvrOtqDSAyZn&s=10" 
                alt="Escudo IE Carlos Lleras Restrepo" 
                class="navbar-logo"
                onerror="this.onerror=null; this.src='../assets/img/logo.png';"
            >
            <div>
                <div class="navbar-title">Entrada Consciente</div>
                <div class="navbar-subtitle">IE Carlos Lleras Restrepo</div>
            </div>
        </a>

        <!-- Opciones de Navegación -->
        <ul class="navbar-menu">
            <li class="nav-item">
                <a href="index.php">🏠 Inicio</a>
            </li>

            <?php if ($sesionActiva): ?>
                <li class="nav-item">
                    <a href="reporte_inasistencias.php">📊 Reportes</a>
                </li>
                
                <!-- Sección para futuras opciones del sistema -->
                <!-- 
                <li class="nav-item">
                    <a href="estudiantes.php">👨‍🎓 Estudiantes</a>
                </li>
                -->

                <!-- Datos de la sesión activa -->
                <li class="user-info-badge">
                    <span class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                    <span class="user-role"><?php echo strtoupper(htmlspecialchars($rolUsuario)); ?></span>
                </li>

                <!-- Botón de Cerrar Sesión (Visible solo si hay sesión iniciada) -->
                <li class="nav-item">
                    <a href="#" onclick="confirmarCierreSesion(event)" class="btn-logout">🚪 Salir</a>
                </li>
            <?php else: ?>
                <!-- Enlace de inicio de sesión si la sesión expiró -->
                <li class="nav-item">
                    <a href="login.php" style="background: var(--primary); color: white;">🔑 Iniciar Sesión</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
    function confirmarCierreSesion(e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Desea cerrar sesión?',
            text: "Saldrá del sistema de asistencia.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1e3a1f',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../controller/logout.php';
            }
        });
    }
</script>