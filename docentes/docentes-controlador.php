<?php
include '../conexion.php';

$accion = isset($_GET['accion']) ? $_GET['accion'] : 'default';

switch ($accion) {
    case 'crear':
        header('Content-Type: application/json; charset=utf-8');
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    
        try {
            if (!$conn) {
                throw new Exception("Error en la conexión con la base de datos.");
            }
    
            if (!validarCedula($_POST['numero_documento'])) {
                echo json_encode(["status" => "error", "message" => "El número de documento ya está registrado."]);
                exit;
            }
    
            if (!validarTelefono($_POST['telefono'])) {
                echo json_encode(["status" => "error", "message" => "El número de teléfono ya está registrado."]);
                exit;
            }
    
            if (!validarCorreo($_POST['email'])) {
                echo json_encode(["status" => "error", "message" => "El correo electrónico ya está registrado."]);
                exit;
            }
    
            $sql = "INSERT INTO docentes 
                    (tipo_documento, numero_documento, nombres, apellidos, perfil_profesional, telefono, direccion, declara_renta, retenedor_iva, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
            $stmt = $conn->prepare($sql);
    
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conn->error);
            }
    
            $declara_renta = isset($_POST['declara_renta']) ? 1 : 0;
            $retenedor_iva = isset($_POST['retenedor_iva']) ? 1 : 0;
            $estado = 1;
    
            $stmt->bind_param(
                'sssssssiii',
                $_POST['tipo_documento'],
                $_POST['numero_documento'],
                $_POST['nombres'],
                $_POST['apellidos'],
                $_POST['perfil_profesional'],
                $_POST['telefono'],
                $_POST['direccion'],
                $declara_renta,
                $retenedor_iva,
                $estado
            );
    
            if (!$stmt->execute()) {
                throw new Exception($stmt->error, $stmt->errno);
            }
            $stmt->close();
    
            // Insertar también en tabla usuarios
            $sql_usuario = "INSERT INTO usuarios (correo, clave, rol, numero_documento) VALUES (?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_usuario);
            if (!$stmt_user) {
                throw new Exception("Error preparando usuario: " . $conn->error);
            }
    
            $clave_hashed = password_hash("numero_documento", PASSWORD_DEFAULT); 
            $rol = "docente";
    
            $stmt_user->bind_param(
                'ssss',
                $_POST['email'],
                $clave_hashed,
                $rol,
                $_POST['numero_documento']
            );
    
            if (!$stmt_user->execute()) {
                throw new Exception($stmt_user->error, $stmt_user->errno);
            }
    
            $stmt_user->close();
    
            echo json_encode(["status" => "success", "message" => "Docente registrado correctamente."]);
    
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Error al crear el registro: " . $e->getMessage()]);
        }
    
    break;
    
    case 'Modificar':
        $numero_documento = $_POST['numero_documento'];
        $tipo_documento = $_POST['tipo_documento'];
        $nombres = $_POST['nombres'];
        $apellidos = $_POST['apellidos'];
        $perfil_profesional = $_POST['perfil_profesional'];
        $telefono = $_POST['telefono'];
        $direccion = $_POST['direccion'];
        $email = $_POST['correo'];
    
        $retenedor_iva = isset($_POST['retenedor_iva']) && $_POST['retenedor_iva'] === 'on' ? 1 : 0;
        $declara_renta = isset($_POST['declara_renta']) && $_POST['declara_renta'] === 'on' ? 1 : 0;
    
        if (!validarCorreo($_POST['correo'])) {
            echo json_encode(["status" => "error", "message" => "El correo electrónico ya está registrado."]);
            exit;
        }

        // Actualizar docente
        $sql = "UPDATE docentes 
                SET tipo_documento = ?, nombres = ?, apellidos = ?, perfil_profesional = ?, telefono = ?, direccion = ?, declara_renta = ?, retenedor_iva = ? 
                WHERE numero_documento = ?";
    
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param(
                "ssssssiis", 
                $tipo_documento, 
                $nombres, 
                $apellidos, 
                $perfil_profesional, 
                $telefono, 
                $direccion, 
                $declara_renta,
                $retenedor_iva,
                $numero_documento
            );
    
            if ($stmt->execute()) {
                echo "Registro de docente actualizado correctamente.<br>";
            } else {
                echo "Error al actualizar el docente: " . $stmt->error;
            }
    
            $stmt->close();
        } else {
            echo "Error al preparar consulta de docente: " . $conn->error;
        }
    
        // Actualizar correo del usuario
        $sql_usuario = "UPDATE usuarios SET correo = ? WHERE numero_documento = ?";

        $stmt = $conn->prepare($sql_usuario); // <-- ESTA LÍNEA ES IMPORTANTE

        if ($stmt) {
            $email = $_POST['email'];
            $numero_documento;

            $stmt->bind_param("ss", $email, $numero_documento);

            if ($stmt->execute()) {
                echo "Correo del usuario actualizado correctamente.";
            } else {
                echo "Error al actualizar el correo del usuario: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error al preparar consulta de usuario: " . $conn->error;
        }

    
        break;
    

    case 'cambiarEstado':
        $numero_documento = $_POST['numero_documento'];
        $estado = $_POST['estado'];

        $sql = "UPDATE docentes SET estado=? WHERE numero_documento=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $estado, $numero_documento);

        if (!$stmt->execute()) {
            echo "Error al cambiar el estado: " . $stmt->error;
        }
        $stmt->close();
        break;

        case 'buscarPorId':
            if (empty($_POST['numero_documento'])) {
                echo json_encode(["error" => "Número de documento no proporcionado"]);
                exit;
            }
            $sql = "SELECT d.*, u.correo 
            FROM docentes d 
            LEFT JOIN usuarios u ON d.numero_documento = u.numero_documento
            WHERE d.numero_documento = ?";
    

            $stmt = $conn->prepare($sql);
    
            if (!$stmt) {
                die("Error en la preparación de la consulta: " . $conn->error);
            }
    
            $stmt->bind_param('s', $_POST['numero_documento']);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows > 0) {
                echo json_encode(['data' => $result->fetch_all(MYSQLI_ASSOC)]);
            } else {
                echo json_encode(['error' => 'Registro no encontrado']);
            }
            $stmt->close();
            break;
    
        default:
            header('Content-Type: application/json; charset=utf-8');
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        
            $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $pageSize = isset($_POST['pageSize']) ? (int)$_POST['pageSize'] : 10;
            $offset = ($page - 1) * $pageSize;
        
            // Total de registros
            $totalRecordsSql = "SELECT COUNT(*) AS total FROM docentes WHERE nombres LIKE ? OR apellidos LIKE ? OR tipo_documento LIKE ? OR numero_documento LIKE ?";
            $stmt = $conn->prepare($totalRecordsSql);
        
            if (!$stmt) {
                ob_clean();
                echo json_encode(["status" => "error", "message" => "Error al contar registros: " . $conn->error]);
                exit;
            }
        
            $searchTerm = "%$search%";
            $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $totalResult = $stmt->get_result();
            $totalRecords = $totalResult->fetch_assoc()['total'];
            $stmt->close();
        
            // Consulta principal
            $sql = "SELECT d.tipo_documento, d.numero_documento, d.nombres, d.apellidos,
                CONCAT(d.nombres, ' ', d.apellidos) AS nombre_completo, d.perfil_profesional,
                d.telefono, d.direccion, u.correo AS email, d.declara_renta, 
                d.retenedor_iva, d.estado
                FROM docentes d
                LEFT JOIN usuarios u ON d.numero_documento = u.numero_documento
                WHERE d.nombres LIKE ? OR d.apellidos LIKE ? OR d.tipo_documento LIKE ? OR d.numero_documento LIKE ?
                ORDER BY d.estado DESC
                LIMIT ?, ?";
    
        
            $stmt = $conn->prepare($sql);
        
            if (!$stmt) {
                ob_clean();
                echo json_encode(["status" => "error", "message" => "Error al preparar consulta principal: " . $conn->error]);
                exit;
            }
        
            $stmt->bind_param('ssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $offset, $pageSize);
            $stmt->execute();
            $result = $stmt->get_result();
        
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $row['estado'] = ($row['estado'] == 1) ? "Activo" : "Inactivo";
                $data[] = $row;
            }
        
            ob_clean(); // limpia antes de enviar JSON
            echo json_encode([
                'data' => $data,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ]);
        break;
        
}

$conn->close();

function validarTelefono($telefono) {
    include '../conexion.php';

    $sql = "SELECT telefono FROM docentes WHERE telefono = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $telefono);
    $stmt->execute();
    $stmt->store_result();

    $telefono_valido = ($stmt->num_rows === 0);

    $stmt->close();
    $conn->close();

    return $telefono_valido;
}

function validarCedula($cedula) {
    include '../conexion.php';

    $sql = "SELECT numero_documento FROM docentes WHERE numero_documento = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $stmt->store_result();

    $cedula_valida = ($stmt->num_rows === 0);

    $stmt->close();
    $conn->close();

    return $cedula_valida;
}

function validarCorreo($correo) {
    include '../conexion.php';

    $sql = "SELECT correo FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    $correo_valido = ($stmt->num_rows === 0);

    $stmt->close();
    $conn->close();

    return $correo_valido;
}






