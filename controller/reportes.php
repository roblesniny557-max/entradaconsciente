<?php
// controller/reportes.php
// GET  ?accion=buscar&desde=&hasta=&grado=&materia=&docente=&estado=&q=  -> JSON
// GET  ?accion=exportar&(mismos filtros)                                   -> CSV
// POST ?accion=eliminar {id}                                               -> JSON (solo admin)
require_once __DIR__ . '/../model/auth.php';

$accion = $_GET['accion'] ?? '';

/** Construye el WHERE a partir de los filtros de la URL. */
function filtros_reporte(): array
{
    $where = [];
    $params = [];

    $fecha = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v ?? '') ? $v : null;

    if ($d = $fecha($_GET['desde'] ?? null)) {
        $where[] = 'fecha_registro >= ?';
        $params[] = "$d 00:00:00";
    }
    if ($h = $fecha($_GET['hasta'] ?? null)) {
        $where[] = 'fecha_registro <= ?';
        $params[] = "$h 23:59:59";
    }
    foreach (['grado', 'materia', 'docente'] as $campo) {
        if (($v = trim($_GET[$campo] ?? '')) !== '') {
            $where[] = "$campo = ?";
            $params[] = $v;
        }
    }
    if (in_array($_GET['estado'] ?? '', ['presente', 'ausente', 'evadido'], true)) {
        $where[] = 'estado = ?';
        $params[] = $_GET['estado'];
    }
    if (($q = trim($_GET['q'] ?? '')) !== '') {
        $where[] = 'estudiante LIKE ?';
        $params[] = '%' . addcslashes($q, '%_\\') . '%';
    }

    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

try {
    $db = conexion();

    if ($accion === 'buscar') {
        requerir_login_json();
        [$where, $params] = filtros_reporte();

        $st = $db->prepare(
            "SELECT id_inasistencia AS id, estudiante, grado, materia, docente, estado, observacion,
                    DATE_FORMAT(fecha_registro, '%d/%m/%Y') AS fecha,
                    DATE_FORMAT(fecha_registro, '%h:%i %p') AS hora
             FROM inasistencias $where
             ORDER BY fecha_registro DESC, id_inasistencia DESC
             LIMIT 500"
        );
        $st->execute($params);
        $registros = $st->fetchAll();

        $st = $db->prepare("SELECT estado, COUNT(*) AS n FROM inasistencias $where GROUP BY estado");
        $st->execute($params);
        $resumen = ['total' => 0, 'presente' => 0, 'ausente' => 0, 'evadido' => 0];
        foreach ($st->fetchAll() as $r) {
            $resumen[$r['estado']] = (int) $r['n'];
            $resumen['total'] += (int) $r['n'];
        }

        // Estudiantes con más ausencias/evasiones dentro del filtro
        $st = $db->prepare(
            "SELECT estudiante, grado,
                    SUM(estado = 'ausente') AS ausencias,
                    SUM(estado = 'evadido') AS evasiones
             FROM inasistencias $where
             GROUP BY estudiante, grado
             HAVING ausencias + evasiones > 0
             ORDER BY ausencias + evasiones DESC, estudiante
             LIMIT 8"
        );
        $st->execute($params);

        responder_json([
            'status'    => 'ok',
            'registros' => $registros,
            'resumen'   => $resumen,
            'ranking'   => $st->fetchAll(),
            'limitado'  => count($registros) === 500,
        ]);
    }

    if ($accion === 'exportar') {
        requerir_login();
        [$where, $params] = filtros_reporte();

        $st = $db->prepare(
            "SELECT DATE_FORMAT(fecha_registro, '%d/%m/%Y') AS fecha, DATE_FORMAT(fecha_registro, '%H:%i') AS hora,
                    estudiante, grado, materia, docente, estado, observacion
             FROM inasistencias $where
             ORDER BY fecha_registro DESC"
        );
        $st->execute($params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_asistencia_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel reconozca las tildes
        fputcsv($out, ['Fecha', 'Hora', 'Estudiante', 'Grado', 'Materia', 'Docente', 'Estado', 'Observación'], ';');
        while ($fila = $st->fetch(PDO::FETCH_NUM)) {
            fputcsv($out, $fila, ';');
        }
        fclose($out);
        exit();
    }

    if ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        requerir_login_json(true);
        verificar_csrf_json();
        $id = (int) (leer_json()['id'] ?? 0);

        $st = $db->prepare("DELETE FROM inasistencias WHERE id_inasistencia = ?");
        $st->execute([$id]);
        if (!$st->rowCount()) {
            responder_json(['status' => 'error', 'message' => 'El registro no existe o ya fue eliminado.'], 404);
        }
        responder_json(['status' => 'ok', 'message' => 'Registro eliminado.']);
    }

    responder_json(['status' => 'error', 'message' => 'Acción no válida.'], 400);

} catch (Exception $e) {
    responder_json(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()], 500);
}
