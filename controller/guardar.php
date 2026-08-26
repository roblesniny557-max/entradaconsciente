<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Protección de seguridad para las peticiones AJAX
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida. Inicie sesión para realizar esta acción.']);
    exit();
}

require_once "../model/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Datos no válidos o vacíos.']);
        exit();
    }

    try {
        $db = conexion();

        $sql = "INSERT INTO inasistencias (estudiante, grado, materia, docente, estado, observacion) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);

        // Inserción en lote (Registro masivo de la clase)
        if (isset($data['estudiantes']) && is_array($data['estudiantes'])) {
            $grado = $data['grado'] ?? '11B';
            $materia = $data['materia'] ?? 'Geometría';
            $docente = $data['docente'] ?? 'Pascual Orduz Latorre';
            
            $guardadosCount = 0;

            foreach ($data['estudiantes'] as $item) {
                $estado = strtolower($item['estado'] ?? 'presente');
                $nota = trim($item['nota'] ?? '');

                // Regla: Guardar si es ausente, evadido O si es presente pero TIENE una observación
                if ($estado === 'ausente' || $estado === 'evadido' || ($estado === 'presente' && !empty($nota))) {
                    $stmt->execute([
                        $item['nombre'],
                        $grado,
                        $materia,
                        $docente,
                        $estado,
                        $nota
                    ]);
                    $guardadosCount++;
                }
            }

            echo json_encode([
                'status' => 'ok', 
                'message' => "Se registraron $guardadosCount novedades/observaciones en la base de datos.",
                'registrados' => $guardadosCount
            ]);
            exit();
        } 
        // Inserción individual (Por si se requiere un registro puntual)
        else if (isset($data['nombre']) && isset($data['estado'])) {
            $stmt->execute([
                $data['nombre'],
                $data['grado'] ?? '11B',
                $data['materia'] ?? 'Geometría',
                $data['docente'] ?? 'Pascual Orduz Latorre',
                $data['estado'],
                $data['nota'] ?? ''
            ]);
            echo json_encode(['status' => 'ok', 'message' => 'Asistencia guardada correctamente en la base de datos.']);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros requeridos.']);
            exit();
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}
?>