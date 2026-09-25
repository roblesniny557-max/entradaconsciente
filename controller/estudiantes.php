<?php
// controller/estudiantes.php — solo administradores
// POST accion=crear|editar|eliminar|importar
require_once __DIR__ . '/../model/auth.php';

requerir_admin();
$volver = '../view/estudiantes.php' . (!empty($_POST['volver_grado']) ? '?grado=' . urlencode($_POST['volver_grado']) : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir($volver);
}
verificar_csrf($volver);

/** Valida y normaliza los datos de un estudiante. Devuelve [datos, error]. */
function datos_estudiante(array $f): array
{
    $d = [
        'nombres'   => trim(preg_replace('/\s+/', ' ', $f['nombres'] ?? '')),
        'documento' => trim($f['documento'] ?? ''),
        'grado'     => strtoupper(trim($f['grado'] ?? '')),
        'celular'   => preg_replace('/[^\d+]/', '', $f['celular'] ?? ''),
        'correo'    => trim($f['correo'] ?? ''),
    ];

    if ($d['nombres'] === '' || mb_strlen($d['nombres']) > 150) {
        return [null, 'El nombre es obligatorio (máximo 150 caracteres).'];
    }
    if ($d['grado'] === '' || mb_strlen($d['grado']) > 20) {
        return [null, 'El grado es obligatorio.'];
    }
    if ($d['documento'] !== '' && !preg_match('/^[A-Za-z0-9.-]{4,50}$/', $d['documento'])) {
        return [null, 'El documento solo puede tener letras, números, puntos o guiones (4 a 50 caracteres).'];
    }
    if ($d['celular'] !== '' && !preg_match('/^\+?\d{7,15}$/', $d['celular'])) {
        return [null, 'El celular del acudiente no es válido.'];
    }
    if ($d['correo'] !== '' && !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
        return [null, 'El correo del acudiente no es válido.'];
    }
    $d['documento'] = $d['documento'] ?: null;
    return [$d, null];
}

function es_duplicado(Exception $e): bool
{
    return $e instanceof PDOException && ($e->errorInfo[1] ?? 0) == 1062;
}

$accion = $_POST['accion'] ?? '';

try {
    $db = conexion();

    if ($accion === 'crear') {
        [$d, $error] = datos_estudiante($_POST);
        if ($error) {
            redirigir($volver, 'error', $error);
        }
        $db->prepare("INSERT INTO estudiantes (nombres, documento, grado, celular_padres, correo_padres) VALUES (?, ?, ?, ?, ?)")
            ->execute([$d['nombres'], $d['documento'], $d['grado'], $d['celular'], $d['correo']]);
        redirigir('../view/estudiantes.php?grado=' . urlencode($d['grado']), 'success', "Estudiante {$d['nombres']} creado.");
    }

    if ($accion === 'editar') {
        $id = (int) ($_POST['id'] ?? 0);
        [$d, $error] = datos_estudiante($_POST);
        if ($error) {
            redirigir($volver, 'error', $error);
        }

        $st = $db->prepare("SELECT nombres, grado FROM estudiantes WHERE id = ?");
        $st->execute([$id]);
        $antes = $st->fetch();
        if (!$antes) {
            redirigir($volver, 'error', 'El estudiante no existe.');
        }

        $db->beginTransaction();
        $db->prepare(
            "UPDATE estudiantes
             SET nombres = ?, documento = ?, grado = ?, celular_padres = ?, correo_padres = ?,
                 celular_padre = NULL, correo_padre = NULL
             WHERE id = ?"
        )->execute([$d['nombres'], $d['documento'], $d['grado'], $d['celular'], $d['correo'], $id]);

        // Mantener el historial asociado si cambió el nombre
        if ($antes['nombres'] !== $d['nombres']) {
            $db->prepare("UPDATE inasistencias SET estudiante = ? WHERE estudiante = ? AND grado = ?")
                ->execute([$d['nombres'], $antes['nombres'], $antes['grado']]);
        }
        $db->commit();
        redirigir($volver, 'success', 'Datos del estudiante actualizados.');
    }

    if ($accion === 'eliminar') {
        $st = $db->prepare("DELETE FROM estudiantes WHERE id = ?");
        $st->execute([(int) ($_POST['id'] ?? 0)]);
        redirigir($volver, $st->rowCount() ? 'success' : 'error', $st->rowCount() ? 'Estudiante eliminado. Su historial de asistencia se conserva.' : 'El estudiante no existe.');
    }

    if ($accion === 'importar') {
        $archivo = $_FILES['archivo'] ?? null;
        $gradoPorDefecto = strtoupper(trim($_POST['grado'] ?? ''));

        if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
            redirigir($volver, 'error', 'Seleccione un archivo CSV válido.');
        }
        if ($archivo['size'] > 1024 * 1024) {
            redirigir($volver, 'error', 'El archivo supera 1 MB.');
        }

        $contenido = file_get_contents($archivo['tmp_name']);
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }
        $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
        $sep = substr_count($lineas[0] ?? '', ';') >= substr_count($lineas[0] ?? '', ',') ? ';' : ',';

        $creados = 0;
        $omitidos = [];
        $existeNombre = $db->prepare("SELECT COUNT(*) FROM estudiantes WHERE nombres = ? AND grado = ?");
        $insertar = $db->prepare("INSERT INTO estudiantes (nombres, documento, grado, celular_padres, correo_padres) VALUES (?, ?, ?, ?, ?)");

        foreach ($lineas as $n => $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $c = array_map('trim', str_getcsv($linea, $sep));
            if ($n === 0 && preg_match('/nombre/i', $c[0] ?? '')) {
                continue; // encabezado
            }

            [$d, $error] = datos_estudiante([
                'nombres'   => $c[0] ?? '',
                'documento' => $c[1] ?? '',
                'grado'     => ($c[2] ?? '') !== '' ? $c[2] : $gradoPorDefecto,
                'celular'   => $c[3] ?? '',
                'correo'    => $c[4] ?? '',
            ]);
            if ($error) {
                $omitidos[] = 'fila ' . ($n + 1) . ": $error";
                continue;
            }

            $existeNombre->execute([$d['nombres'], $d['grado']]);
            if ($existeNombre->fetchColumn()) {
                $omitidos[] = 'fila ' . ($n + 1) . ': ya existe ' . $d['nombres'];
                continue;
            }

            try {
                $insertar->execute([$d['nombres'], $d['documento'], $d['grado'], $d['celular'], $d['correo']]);
                $creados++;
            } catch (PDOException $e) {
                $omitidos[] = 'fila ' . ($n + 1) . ': ' . (es_duplicado($e) ? 'documento repetido' : 'error al guardar');
            }
        }

        $msg = "Importación terminada: $creados estudiante(s) creados.";
        if ($omitidos) {
            $msg .= ' Omitidos ' . count($omitidos) . ' (' . implode('; ', array_slice($omitidos, 0, 5)) . (count($omitidos) > 5 ? '; ...' : '') . ').';
        }
        redirigir($volver, $creados ? 'success' : 'error', $msg);
    }

    redirigir($volver, 'error', 'Acción no válida.');

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $msg = es_duplicado($e) ? 'Ya existe un estudiante con ese número de documento.' : 'Error en la base de datos: ' . $e->getMessage();
    redirigir($volver, 'error', $msg);
}
