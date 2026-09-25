<?php
// controller/asistencia.php
// GET  ?accion=cargar&grado=11B&materia=Geometría  -> estudiantes del grado + lo guardado hoy
// POST ?accion=guardar  {grado, materia, registros:[{id, estado, nota}]}
require_once __DIR__ . '/../model/auth.php';

$usuario = requerir_login_json();
$accion  = $_GET['accion'] ?? '';

$hoyInicio = date('Y-m-d 00:00:00');
$hoyFin    = date('Y-m-d 23:59:59');

function estudiantes_del_grado(PDO $db, string $grado): array
{
    $st = $db->prepare(
        "SELECT id, nombres, documento,
                COALESCE(NULLIF(celular_padres, ''), celular_padre) AS celular
         FROM estudiantes
         WHERE grado = ?
         ORDER BY nombres"
    );
    $st->execute([$grado]);
    return $st->fetchAll();
}

try {
    $db = conexion();

    if ($accion === 'cargar' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $grado   = trim($_GET['grado'] ?? '');
        $materia = trim($_GET['materia'] ?? '');
        if ($grado === '' || $materia === '') {
            responder_json(['status' => 'error', 'message' => 'Seleccione el grado y la materia.'], 422);
        }

        $estudiantes = array_map(fn($e) => [
            'id'       => (int) $e['id'],
            'nombre'   => $e['nombres'],
            'celular'  => $e['celular'] ?: '',
            'whatsapp' => telefono_whatsapp($e['celular']),
        ], estudiantes_del_grado($db, $grado));

        // Lo que este docente ya guardó hoy para este grado y materia
        $st = $db->prepare(
            "SELECT estudiante, estado, observacion, fecha_registro
             FROM inasistencias
             WHERE grado = ? AND materia = ? AND docente = ? AND fecha_registro BETWEEN ? AND ?"
        );
        $st->execute([$grado, $materia, $usuario['nombre'], $hoyInicio, $hoyFin]);
        $guardados = [];
        $ultimo = null;
        foreach ($st->fetchAll() as $r) {
            $guardados[$r['estudiante']] = ['estado' => $r['estado'], 'nota' => $r['observacion'] ?? ''];
            $ultimo = max($ultimo, $r['fecha_registro']);
        }

        responder_json([
            'status'      => 'ok',
            'estudiantes' => $estudiantes,
            'guardados'   => $guardados,
            'ultimo'      => $ultimo ? date('h:i a', strtotime($ultimo)) : null,
            'director'    => config('directores')[$grado] ?? '',
        ]);
    }

    if ($accion === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        verificar_csrf_json();
        $data      = leer_json();
        $grado     = trim($data['grado'] ?? '');
        $materia   = trim($data['materia'] ?? '');
        $registros = $data['registros'] ?? null;

        if ($grado === '' || $materia === '' || !is_array($registros)) {
            responder_json(['status' => 'error', 'message' => 'Faltan datos: grado, materia o lista de estudiantes.'], 422);
        }
        if (mb_strlen($materia) > 100) {
            responder_json(['status' => 'error', 'message' => 'El nombre de la materia es demasiado largo.'], 422);
        }

        // Solo se aceptan estudiantes que pertenecen al grado (el nombre sale de la BD, no del navegador)
        $nombres = array_column(estudiantes_del_grado($db, $grado), 'nombres', 'id');
        if (!$nombres) {
            responder_json(['status' => 'error', 'message' => "El grado $grado no tiene estudiantes registrados."], 422);
        }

        $conteo = ['presente' => 0, 'ausente' => 0, 'evadido' => 0];
        $novedades = [];
        foreach ($registros as $r) {
            $id     = (int) ($r['id'] ?? 0);
            $estado = strtolower($r['estado'] ?? 'presente');
            $nota   = mb_substr(trim($r['nota'] ?? ''), 0, 1000);

            if (!isset($nombres[$id]) || !isset($conteo[$estado])) {
                continue;
            }
            $conteo[$estado]++;

            // Se guardan ausentes, evadidos y presentes con observación
            if ($estado !== 'presente' || $nota !== '') {
                $novedades[] = [$nombres[$id], $estado, $nota];
            }
        }

        $ahora = date('Y-m-d H:i:s');
        $db->beginTransaction();

        // Guardar de nuevo el mismo día reemplaza la toma anterior (no se duplican registros)
        $db->prepare(
            "DELETE FROM inasistencias
             WHERE grado = ? AND materia = ? AND docente = ? AND fecha_registro BETWEEN ? AND ?"
        )->execute([$grado, $materia, $usuario['nombre'], $hoyInicio, $hoyFin]);

        $ins = $db->prepare(
            "INSERT INTO inasistencias (estudiante, grado, materia, docente, estado, observacion, fecha_registro)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($novedades as [$nombre, $estado, $nota]) {
            $ins->execute([$nombre, $grado, $materia, $usuario['nombre'], $estado, $nota, $ahora]);
        }
        $db->commit();

        $mensaje = count($novedades)
            ? 'Asistencia guardada: ' . count($novedades) . ' novedad(es) registrada(s).'
            : 'Asistencia guardada: todos los estudiantes presentes, sin novedades.';

        responder_json([
            'status'  => 'ok',
            'message' => $mensaje,
            'conteo'  => $conteo,
            'ultimo'  => date('h:i a', strtotime($ahora)),
        ]);
    }

    responder_json(['status' => 'error', 'message' => 'Acción no válida.'], 400);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    responder_json(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
