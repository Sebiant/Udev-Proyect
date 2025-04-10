<?php
include '../conexion.php';

$accion = $_GET['accion'] ?? 'default';

switch ($accion) {
    case 'crear':
        $dia = $_POST['dia'];
        $hora_inicio = $_POST['horaEntrada'];
        $hora_salida = $_POST['horaSalida'];
        $salon = $_POST['salon'];
        $docente = $_POST['docente'];
        $periodo = $_POST['periodo'];
        $modulo = $_POST['materia'];
        $estado = 'Pendiente';
        $modalidad = $_POST['modalidad'];
    
        // Validación 1: Hora de salida debe ser posterior a la de entrada
        if (!validarHorasEntradaSalida($hora_inicio, $hora_salida)) {
            die(json_encode(['status' => 'error', 'message' => 'La hora de salida debe ser posterior a la hora de entrada.']));
        }
    
        // Validación 2: Horario laboral válido (7:00 - 22:00)
        if (!horarioLaboralValido($hora_inicio, $hora_salida)) {
            die(json_encode(['status' => 'error', 'message' => 'El horario debe estar entre 7:00 AM y 10:00 PM.']));
        }
    
        // Obtener las fechas del período
        $sql_periodo = "SELECT fecha_inicio, fecha_fin FROM periodos WHERE id_periodo = ?";
        $stmt_periodo = $conn->prepare($sql_periodo);
        $stmt_periodo->bind_param('i', $periodo);
        $stmt_periodo->execute();
        $result = $stmt_periodo->get_result();
        
        if (!$row = $result->fetch_assoc()) {
            die(json_encode(['status' => 'error', 'message' => 'Periodo no encontrado.']));
        }
    
        $fecha_inicio = new DateTime($row['fecha_inicio']);
        $fecha_fin = new DateTime($row['fecha_fin']);
    
        $dias = [
            "domingo" => 0, "lunes" => 1, "martes" => 2, "miercoles" => 3,
            "jueves" => 4, "viernes" => 5, "sabado" => 6
        ];
    
        if (!isset($dias[$dia])) {
            die(json_encode(['status' => 'error', 'message' => 'Día de la semana inválido.']));
        }
    
        $dia_numero = $dias[$dia];
    
        // Buscar la primera fecha dentro del rango que coincida con el día seleccionado
        while ($fecha_inicio->format("w") != $dia_numero) {
            $fecha_inicio->modify("+1 day");
        }
    
        $fechas_generadas = [];
    
        while ($fecha_inicio <= $fecha_fin) {
            $fecha_str = $fecha_inicio->format("Y-m-d");
            
            // Validación 3: No es día festivo
            if (esFestivo($fecha_str, $conn)) {
                $fecha_inicio->modify("+7 days");
                continue;
            }
    
            // Validación 4: Docente disponible
            if (!docenteDisponible($docente, $fecha_str, $hora_inicio, $hora_salida, $conn)) {
                die(json_encode(['status' => 'error', 'message' => "El docente no está disponible el {$fecha_str} de {$hora_inicio} a {$hora_salida}"]));
            }
    
            // Validación 5: Salón disponible
            if (!salonDisponible($salon, $fecha_str, $hora_inicio, $hora_salida, $conn)) {
                die(json_encode(['status' => 'error', 'message' => "El salón no está disponible el {$fecha_str} de {$hora_inicio} a {$hora_salida}"]));
            }
    
            $fechas_generadas[] = $fecha_str;
            $fecha_inicio->modify("+7 days");
        }
    
        if (empty($fechas_generadas)) {
            die(json_encode(['status' => 'error', 'message' => 'No hay fechas válidas para programar (todas son festivos o no hay disponibilidad)']));
        }
    
        $sql = "INSERT INTO programador (fecha, hora_inicio, hora_salida, id_salon, numero_documento, id_modulo, id_periodo, estado, modalidad) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
    
        if (!$stmt) {
            die(json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta: ' . $conn->error]));
        }
    
        foreach ($fechas_generadas as $fecha) {
            $stmt->bind_param('sssiiiiss', $fecha, $hora_inicio, $hora_salida, $salon, $docente, $modulo, $periodo, $estado, $modalidad);
    
            if (!$stmt->execute()) {
                die(json_encode(['status' => 'error', 'message' => 'Error al insertar: ' . $stmt->error]));
            }
        }
    
        echo json_encode(['status' => 'success', 'message' => 'Se programaron ' . count($fechas_generadas) . ' clases.']);
    
        $stmt->close();
        break;

    case 'buscarMateriaPorPrograma':
        $programa = $_POST['id_programa'] ?? null;
    
        if ($programa !== null) {
            $sql_modulo = "SELECT id_modulo AS id, nombre FROM modulos WHERE id_programa = ? AND estado = 1";
            $stmt = $conn->prepare($sql_modulo);
        
            if ($stmt) {
                $stmt->bind_param("i", $programa);
                $stmt->execute();
                $result = $stmt->get_result();
        
                $modulos = [];
                while ($row = $result->fetch_assoc()) {
                    $modulos[] = [
                        'id' => $row['id'],
                        'nombre' => $row['nombre']
                    ];
                }
        
                echo json_encode([
                    'status' => 'success',
                    'modulos' => $modulos
                ]);
                
                $stmt->close();
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al preparar la consulta: ' . $conn->error
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID del programa no proporcionado.'
            ]);
        }
        break;  

    case 'reprogramar':        
        // Recibir datos del formulario
        $fecha = $_POST['nueva_fecha'] ?? null;
        $nueva_hora_inicio = $_POST['nueva_hora_inicio'] ?? null;
        $nueva_hora_salida = $_POST['nueva_hora_salida'] ?? null;
        $id_salon = $_POST['id_salon'] ?? null;
        $numero_documento = $_POST['numero_documento'] ?? null;
        $id_modulo = $_POST['id_modulo'] ?? null;
        $id_periodo = $_POST['id_periodo'] ?? null;
        $modalidad = $_POST['modalidad'] ?? null;
        $estado = "Pendiente";
        $clase_original_id = $_POST['id_programador'] ?? null;
        
        // Validación de datos obligatorios
        if (!$clase_original_id || !$fecha || !$nueva_hora_inicio || !$nueva_hora_salida || !$id_salon || !$numero_documento || !$id_modulo || !$id_periodo || !$modalidad) {
            echo json_encode(["error" => "Todos los campos son obligatorios."]);
            exit;
        }
        
        // Iniciar transacción para evitar inconsistencias
        $conn->begin_transaction();
        
        try {
            // Insertar nueva clase reprogramada
            $sql_insert = "INSERT INTO programador (fecha, hora_inicio, hora_salida, id_salon, numero_documento, id_modulo, id_periodo, modalidad, estado, clase_original_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql_insert);
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conn->error);
            }
            
            // Enlazar parámetros
            $stmt->bind_param("ssssiiisss", $fecha, $nueva_hora_inicio, $nueva_hora_salida, $id_salon, $numero_documento, $id_modulo, $id_periodo, $modalidad, $estado, $clase_original_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al reprogramar la clase: " . $stmt->error);
            }
        
            // Actualizar estado de la clase original a "Reprogramada"
            $sql_update = "UPDATE programador SET estado = 'Reprogramada' WHERE id_programador = ?";
            $stmt_update = $conn->prepare($sql_update);
            
            if (!$stmt_update) {
                throw new Exception("Error en la preparación del UPDATE: " . $conn->error);
            }
        
            $stmt_update->bind_param("i", $clase_original_id);
            
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar el estado de la clase original: " . $stmt_update->error);
            }
        
            // Confirmar transacción
            $conn->commit();
            
            echo json_encode(["success" => "Clase reprogramada con éxito"]);
        
        } catch (Exception $e) {
            $conn->rollback(); // Revertir cambios si hay un error
            echo json_encode(["error" => $e->getMessage()]);
        }
    
        $stmt->close();
        $stmt_update->close();
        break;

    case 'editar':
        $id_programador = $_POST['id_programador'] ?? null;
        $fecha = $_POST['fecha'] ?? null;
        $hora_inicio = $_POST['hora_inicio'] ?? null;
        $hora_salida = $_POST['hora_salida'] ?? null;
        $salon = $_POST['id_salon'] ?? null;
        $docente = $_POST['numero_documento'] ?? null;
        $modulo = $_POST['id_modulo'] ?? null;
        $modalidad = $_POST['modalidad'] ?? null;
    
        // Validación de datos obligatorios
        if (!$id_programador || !$fecha || !$hora_inicio || !$hora_salida || !$salon || !$docente || !$modulo || !$modalidad) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }
    
        $sql = "UPDATE programador 
                SET fecha=?, hora_inicio=?, hora_salida=?, id_salon=?, numero_documento=?, id_modulo=?, modalidad=? 
                WHERE id_programador=?";
    
        if (!$stmt = $conn->prepare($sql)) {
            echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta: ' . $conn->error]);
            exit;
        }
    
        $stmt->bind_param('sssssssi', $fecha, $hora_inicio, $hora_salida, $salon, $docente, $modulo, $modalidad, $id_programador);
    
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Programador actualizado con éxito.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el programador: ' . $stmt->error]);
        }
    
        $stmt->close();
        break;
    
    case 'BusquedaPorId':
        $id_programador = $_POST['id_programador'] ?? null;

        if (!$id_programador) {
            echo json_encode(['error' => 'ID no proporcionado']);
            exit;
        }

        $sql = "SELECT * FROM programador WHERE id_programador = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die(json_encode(['error' => 'Error en la preparación de la consulta: ' . $conn->error]));
        }

        $stmt->bind_param('i', $id_programador);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['data' => $data]);
        } else {
            echo json_encode(['error' => 'Registro no encontrado']);
        }

        $stmt->close();
        break;

    default:
        $conn->query("SET lc_time_names = 'es_ES'");

        // Obtener los parámetros de DataTables
        $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        
        $searchQuery = "";
        $params = [];
        $types = "";
        
        // Si hay búsqueda, filtrar resultados
        if (!empty($search)) {
            $searchQuery = " AND (d.nombres LIKE ? OR d.apellidos LIKE ? OR s.nombre_salon LIKE ? OR m.nombre LIKE ?)";
            $searchValue = "%{$search}%";
            array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
            $types .= "ssss";
        }
        
        // Contar el total de registros sin filtro
        $sqlTotal = "SELECT COUNT(*) as total FROM programador p 
                     JOIN docentes d ON p.numero_documento = d.numero_documento
                     JOIN salones s ON p.id_salon = s.id_salon
                     LEFT JOIN modulos m ON p.id_modulo = m.id_modulo
                     WHERE 1=1 $searchQuery";
        
        $stmtTotal = $conn->prepare($sqlTotal);
        if (!empty($searchQuery)) {
            $stmtTotal->bind_param($types, ...$params);
        }
        $stmtTotal->execute();
        $resultTotal = $stmtTotal->get_result();
        $totalRecords = $resultTotal->fetch_assoc()['total'];
        
        // Consulta principal con paginación
        $sql = "SELECT p.*,
                    p.id_programador, 
                    DATE_FORMAT(p.fecha, '%W %e de %M') AS fecha,
                    DATE_FORMAT(p.hora_inicio, '%h:%i %p') AS hora_inicio, 
                    DATE_FORMAT(p.hora_salida, '%h:%i %p') AS hora_salida, 
                    d.nombres,
                    d.apellidos,
                    s.nombre_salon, 
                    m.nombre AS nombre_modulo
                FROM programador p
                JOIN docentes d ON p.numero_documento = d.numero_documento
                JOIN salones s ON p.id_salon = s.id_salon
                LEFT JOIN modulos m ON p.id_modulo = m.id_modulo
                WHERE 1=1 $searchQuery
                ORDER BY 
                CASE 
                    WHEN p.estado = 'Perdida' THEN 1 
                    WHEN p.estado = 'Pendiente' THEN 2 
                    ELSE 3 
                END, 
                p.fecha ASC
                LIMIT ? OFFSET ?";
        
        // Agregar los parámetros de paginación
        $params[] = $length;
        $params[] = $start;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        // Modificar los estados antes de enviarlos
        foreach ($data as &$row) {
            if ($row['estado'] === 'Reprogramada') {
                $row['estado'] = 'Reagendada';
            } elseif ($row['estado'] === 'Pendiente') {
                $row['estado'] = 'Agendada';
            }
        }
        unset($row); // buena práctica para evitar referencias accidentales

        // Respuesta JSON para DataTables
        $response = [
            "draw" => $draw,
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
            "data" => $data
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        
        break;
}

$conn->close();
function docenteDisponible($docente_id, $fecha, $hora_inicio, $hora_fin, $conn, $excluir_id = null) {
    $sql = "SELECT id_programador 
            FROM programador 
            WHERE numero_documento = ? 
            AND fecha = ? 
            AND (
                (? < hora_salida AND ? > hora_inicio) OR  -- Solapamiento parcial
                (hora_inicio = ? AND hora_salida = ?)     -- Mismo horario exacto
            )";
    
    if ($excluir_id) {
        $sql .= " AND id_programador != ?";
    }

    $stmt = $conn->prepare($sql);
    $params = [$docente_id, $fecha, $hora_inicio, $hora_fin, $hora_inicio, $hora_fin];
    if ($excluir_id) $params[] = $excluir_id;
    
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $stmt->store_result();

    return ($stmt->num_rows === 0); // True si está disponible
}
function salonDisponible($salon_id, $fecha, $hora_inicio, $hora_fin, $conn, $excluir_id = null) {
    $sql = "SELECT id_programador 
            FROM programador 
            WHERE id_salon = ? 
            AND fecha = ? 
            AND (
                (? < hora_salida AND ? > hora_inicio) OR
                (hora_inicio = ? AND hora_salida = ?)
            )";
    
    if ($excluir_id) {
        $sql .= " AND id_programador != ?";
    }

    $stmt = $conn->prepare($sql);
    $params = [$salon_id, $fecha, $hora_inicio, $hora_fin, $hora_inicio, $hora_fin];
    if ($excluir_id) $params[] = $excluir_id;
    
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $stmt->store_result();

    return ($stmt->num_rows === 0); // True si está disponible
}
function esFestivo($fecha, $conn) {
    $sql = "SELECT fecha FROM dias_festivos WHERE fecha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $fecha);
    $stmt->execute();
    $stmt->store_result();

    return ($stmt->num_rows > 0); // True si es festivo
}
function horarioLaboralValido($hora_inicio, $hora_fin) {
    $hora_min = '07:00:00';
    $hora_max = '22:00:00';
    return ($hora_inicio >= $hora_min && $hora_fin <= $hora_max);
}
function validarHorasEntradaSalida($hora_inicio, $hora_fin) {
    return strtotime($hora_fin) > strtotime($hora_inicio);
}
?>  