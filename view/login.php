<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso al Sistema - IE Carlos Lleras Restrepo</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #1e3a1f;
            --primary-hover: #142815;
            --accent: #b89047;
            --bg: #f4f6f4;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #d1d5db;
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .logo-container {
            margin-bottom: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
            border: none;
            padding: 0;
        }

        .school-logo {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border: none;
            outline: none;
            border-radius: 50%;
            mix-blend-mode: multiply;
        }

        .title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.4rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 1.5px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 58, 31, 0.15);
        }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.85rem;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s ease;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }

        .forgot-link {
            display: inline-block;
            margin-top: 1.2rem;
            font-size: 0.82rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-container">
            <img 
                src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1ydV5pAMV7JIMln8X-w9GiedpSPnSVinjdJCvhRUyZawxcvrOtqDSAyZn&s=10" 
                alt="Escudo IE Carlos Lleras Restrepo" 
                class="school-logo"
                onerror="this.onerror=null; this.src='../assets/img/logo.png';"
            >
        </div>

        <h1 class="title">ENTRADA CONSCIENTE</h1>
        <p class="subtitle">Ingreso al Sistema de Asistencia</p>

        <form action="../controller/login_process.php" method="POST">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Ingrese su usuario" required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Ingrese su contraseña" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Ingresar</button>
        </form>

        <a class="forgot-link" onclick="abrirOlvidePassword()">¿Olvidó su contraseña?</a>
    </div>

    <script>
        function abrirOlvidePassword() {
            Swal.fire({
                title: 'Restablecer Contraseña',
                html: `
                    <form id="resetForm" style="text-align: left; font-size: 0.85rem;">
                        <div style="margin-bottom: 0.8rem;">
                            <label style="font-weight: 700;">Nombre de Usuario</label>
                            <input type="text" id="swal_usuario" class="swal2-input" placeholder="Ej. pascual.orduz" style="width: 100%; margin: 0.3rem 0 0.8rem 0;">
                        </div>
                        <div style="margin-bottom: 0.8rem;">
                            <label style="font-weight: 700;">Clave Maestra</label>
                            <input type="password" id="swal_clave_maestra" class="swal2-input" placeholder="Ingrese clave maestra" style="width: 100%; margin: 0.3rem 0 0.8rem 0;">
                        </div>
                        <div>
                            <label style="font-weight: 700;">Nueva Contraseña</label>
                            <input type="password" id="swal_nueva_password" class="swal2-input" placeholder="Escriba nueva contraseña" style="width: 100%; margin: 0.3rem 0 0 0;">
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Restablecer',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#1e3a1f',
                focusConfirm: false,
                preConfirm: () => {
                    const usuario = document.getElementById('swal_usuario').value.trim();
                    const claveMaestra = document.getElementById('swal_clave_maestra').value.trim();
                    const nuevaPassword = document.getElementById('swal_nueva_password').value.trim();

                    if (!usuario || !claveMaestra || !nuevaPassword) {
                        Swal.showValidationMessage('Todos los campos son obligatorios');
                        return false;
                    }
                    return { usuario, claveMaestra, nuevaPassword };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '../controller/recuperar_process.php';

                    for (const key in result.value) {
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = key;
                        hiddenField.value = result.value[key];
                        form.appendChild(hiddenField);
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>

    <?php if (isset($_GET['error']) || isset($_GET['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_GET['error'])): ?>
                const errorType = "<?php echo htmlspecialchars($_GET['error']); ?>";
                let msg = 'Ocurrió un error inesperado.';

                if (errorType === 'campos_vacios') msg = 'Por favor complete todos los campos.';
                else if (errorType === 'datos_incorrectos') msg = 'Usuario o contraseña incorrectos.';
                else if (errorType === 'db_error') msg = 'Error de conexión con la base de datos.';
                else if (errorType === 'clave_maestra_incorrecta') msg = 'La clave maestra ingresada no es válida.';
                else if (errorType === 'usuario_no_encontrado') msg = 'El usuario ingresado no existe en la base de datos.';

                Swal.fire({
                    icon: 'error',
                    title: 'Error de acceso',
                    text: msg,
                    confirmButtonColor: '#1e3a1f'
                });
            <?php elseif (isset($_GET['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Contraseña actualizada',
                    text: 'Su contraseña ha sido restablecida con éxito. Ya puede ingresar.',
                    confirmButtonColor: '#1e3a1f'
                });
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>