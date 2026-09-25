# Entrada Consciente

Sistema de asistencia de clase para la IE Carlos Lleras Restrepo (PHP 8 + MySQL/TiDB, sin frameworks).

## Funciones

- **Tomar asistencia**: se elige grado y materia y se carga la lista desde la base de datos. Cada estudiante se marca como presente, ausente o evadido, con una observación opcional. Si se guarda otra vez el mismo día, la toma anterior se reemplaza y no se duplica.
- **Avisos por WhatsApp**: mensaje individual al celular del acudiente y reporte general de la clase para coordinación.
- **Reportes**: filtros por fechas, grado, materia, docente, estado y estudiante. Incluye totales, estudiantes con más novedades y exportación a CSV para Excel.
- **Estudiantes** (administradores): crear, editar, eliminar e importar desde CSV.
- **Usuarios** (administradores): crear docentes y administradores, editarlos, restablecer contraseñas y eliminarlos.
- **Mi perfil**: cambio de contraseña.
- Seguridad: contraseñas con bcrypt, token CSRF en todos los formularios, escape de HTML, bloqueo de 5 minutos tras 5 intentos fallidos y credenciales fuera del código.

## Instalación

1. Copie `config/config.example.php` como `config/config.local.php` y complete los datos de la base de datos. Ese archivo no se sube a git.
2. Si la base de datos es nueva, ejecute `database/schema.sql`. Crea el usuario `admin` con contraseña `admin123`; cámbiela al ingresar.
3. Opcional, en una base de datos existente: `php database/migrate.php`. Agrega un índice e importa los 30 estudiantes de 11B que antes estaban escritos en el código.
4. Para desarrollo local: `php -S localhost:8000` y abrir http://localhost:8000

## Estructura

```
config/       configuración (config.php) y credenciales locales (config.local.php)
model/        conexión PDO, autenticación/CSRF/utilidades, íconos SVG
controller/   asistencia, reportes, estudiantes, usuarios, perfil, login/logout/recuperar
view/         páginas + components/ (layout, navbar) + assets/ (app.css, app.js)
database/     schema.sql y migrate.php
```
