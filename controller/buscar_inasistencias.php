<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida. Inicie sesión nuevamente.']);
    exit();
}

require_once "../model/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    try {
        $db = conexion();

        // 1. Obtener los nombres reales de las columnas de la tabla 'inasistencias'
        $columnasStmt = $db->query("DESCRIBE inasistencias");
        $estructura = $columnasStmt->fetchAll(PDO::FETCH_ASSOC);
        $columnas = array_column($estructura, 'Field');

        // 2. Identificar la columna para ordenar (ID o Clave Primaria)
        $columnaOrden = null;
        foreach ($estructura as $col) {
            if ($col['Key'] === 'PRI') {
                $columnaOrden = $col['Field'];
                break;
            }
        }
        
        // Si no hay Primary Key explícita, buscar columnas de ID comunes
        if (!$columnaOrden) {
            foreach (['id', 'id_inasistencia', 'id_asistencia', 'codigo'] as $posibleId) {
                if (in_array($posibleId, $columnas)) {
                    $columnaOrden = $posibleId;
                    break;
                }
            }
        }

        // 3. Identificar la columna de fecha (si existe)
        $columnaFecha = null;
        foreach (['fecha', 'created_at', 'fecha_registro', 'fecha_hora', 'timestamp'] as $posibleFecha) {
            if (in_array($posibleFecha, $columnas)) {
                $columnaFecha = $posibleFecha;
                break;
            }
        }

        // 4. Construcción dinámica de filtros WHERE
        $where = ["1=1"];
        $params = [];

        // Filtro por fecha si existe columna y fue enviada
        if (!empty($data['fecha']) && $columnaFecha) {
            $where[] = "DATE($columnaFecha) = ?";
            $params[] = $data['fecha'];
        }

        // Filtro por docente
        if (!empty($data['docente']) && in_array('docente', $columnas)) {
            $where[] = "docente = ?";
            $params[] = $data['docente'];
        }

        // Filtro por grado
        if (!empty($data['grado']) && in_array('grado', $columnas)) {
            $where[] = "grado = ?";
            $params[] = $data['grado'];
        }

        // Filtro por estado
        if (!empty($data['estado']) && in_array('estado', $columnas)) {
            $where[] = "LOWER(estado) = ?";
            $params[] = strtolower($data['estado']);
        }

        $sqlWhere = implode(" AND ", $where);

        // 5. Construcción de la fecha a mostrar
        if ($columnaFecha) {
            $selectFecha = "DATE_FORMAT($columnaFecha, '%d/%m/%Y %h:%i %p') AS fecha_formateada";
        } else {
            $selectFecha = "'N/A' AS fecha_formateada";
        }

        // 6. Definición de la cláusula ORDER BY según las columnas existentes
        $clauseOrder = "";
        if ($columnaOrden) {
            $clauseOrder = "ORDER BY $columnaOrden DESC";
        } elseif ($columnaFecha) {
            $clauseOrder = "ORDER BY $columnaFecha DESC";
        }

        $sql = "SELECT *,$selectFecha 
                FROM inasistencias 
                WHERE $sqlWhere 
                $clauseOrder 
                LIMIT 200";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'ok', 'registros' => $registros]);
        exit();

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la consulta: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
    exit();
}
?>