<?php

include("../Conexion.php");
/*Este archivo hace la consulta de la info de estudiante para el modal
info_estudiante Estado:FUNCIONAL activo*/

@$action = $_POST["operacion"];

main($action, $conn);

function main($action, $conn)
{
    switch ($action) {
        case 'registro_individual':
            Registro_individual($conn);
            break;

        case 'registro_movimientos':
            Registro_movimientos($conn);
            break;
        
        case 'registro_completo':
            Registro_completo($conn);
            break;

       
            
      
        default:
            
            break;


    }
}

function Registro_individual($conn){


    try {
    $query = "SELECT estudiantes.codigo_estudiante, estudiantes.nombre_estudiante, estudiantes.apellidos_estudiante, estudiantes.fecha_nacimiento_estudiante, estudiantes.imagen, programas.id_programa, programas.nombre
            FROM convenio 
            INNER JOIN estudiantes 
            ON convenio.codigo_estudiante = estudiantes.codigo_estudiante 
            INNER JOIN programas
            ON convenio.codigo_convenio = programas.id_programa 
            WHERE convenio.codigo_convenio = ?  "; 
            /*Consulta que compara con el codigo convenio recibido de el view y limit la busqueda a 1 rgistro*/


    $codigo_conveni=intval($_POST['codigo_convenio']);

    
    //echo $codigo_conveni;

       
            $stmt=$conn->prepare($query);
            $stmt->bind_param('i', $codigo_conveni);
            $stmt->execute();
            $resultado = $stmt->get_result();

            

            if ($resultado->num_rows > 0) {

                $fila=$resultado->fetch_assoc();


                $salida = $fila;

                //$salida = $resultado->fetch_assoc();
                
            } else {
                $salida["error"] = "No se encontraron resultados";
            }

    
          
            
    
            //$resultadoDato = $conexion->query($query);
            
    
    
            /*while ($row = $resultadoDato->fetch(PDO::FETCH_ASSOC)) {
                $codigo_estudi = $row['codigo_estudiante'];
                $nombre_est = $row['nombre_estudiante'];
                $apellidos_est = $row['apellidos_estudiante'];
                $fecha_naci_est = $row['fecha_nacimiento_estudiante'];
                $carrera_est= $row['descripcion_servicio'];
                $imagen_est= $row['imagen'];
               
            }*/
        } catch (PDOException $e) {
            echo "error al ejecutar " . $e->getMessage();
        }
        echo json_encode($salida);
    }

    function Registro_movimientos($conn){
        //obtencion de la tabla movimientos para mostrar sin DATATABLES por medio de listas y variables

if($conn){
    try{
    $consulta = "SELECT movimientos.id_movimiento, movimientos.fecha_movimiento, movimientos.descripcion, movimientos.valor_movimiento, convenio.codigo_estudiante
    FROM movimientos
    LEFT JOIN convenio
    ON movimientos.codigo_estudiante = convenio.codigo_estudiante
    WHERE movimientos.codigo_estudiante = ? ";
    $stmt = $conn->prepare($consulta);

    $id_recibido=intval($_POST['codigo_convenio']);
    //echo $id_recibido;

    
    $stmt->bind_param(
        'i', $id_recibido
    );
    if(!$stmt){
        throw new Exception("Error al preparar la consulta: " . $conn->error);
        
    }   
        if($stmt->execute()){

            $resultados=$stmt->get_result();
        
            if($resultados){
                while($row = $resultados->fetch_assoc())   {
                    $codigo = $row['id_movimiento'];
                    $fecha = $row['fecha_movimiento'];
                    $descripcion = $row['descripcion'];
                    $cuotas = $row['valor_movimiento'];

                    echo "<tr>";
                    echo "<td>$codigo</td>";
                    echo "<td>$fecha</td>";
                    echo "<td>$descripcion</td>";
                    echo "<td>$cuotas</td>";
                    echo '<td class="text-center"><input type="checkbox" class="form-check-input"></td>';
                    echo "</tr>";

                }
            } else {
                echo "<tr><td colspan='5'>No se encontraron resultados</td></tr>";
            }
        }else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
    }catch(Exception $e){
        echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
    }

}else{
    echo "<tr><td colspan='5'>Error de conexión a la base de datos</td></tr>";
}
    }

    function Registro_completo($conn) {
        // Validación robusta del parámetro
        if(!isset($_POST['codigo_convenio'])) {
            http_response_code(400);
            die(json_encode(["error" => "Parámetro codigo_convenio faltante"]));
        }
    
        $codigo = intval($_POST['codigo_convenio']);
        
        try {
            // 1. Datos del estudiante
            $queryEstudiante = "SELECT e.codigo_estudiante, e.nombre_estudiante, 
                              e.apellidos_estudiante, e.fecha_nacimiento_estudiante, 
                              e.imagen, p.nombre
                       FROM convenio c
                       INNER JOIN estudiantes e ON c.codigo_estudiante = e.codigo_estudiante
                       INNER JOIN programas p ON c.codigo_convenio = p.id_programa
                       WHERE c.codigo_convenio = ?";
            
            $stmt = $conn->prepare($queryEstudiante);
            $stmt->bind_param('i', $codigo);
            $stmt->execute();
            $estudiante = $stmt->get_result()->fetch_assoc();
            
            if(!$estudiante) {
                throw new Exception("No se encontró el estudiante");
            }
    
            // 2. Movimientos
            $queryMovimientos = "SELECT m.id_movimiento, m.fecha_movimiento, 
                                m.descripcion, m.valor_movimiento
                         FROM movimientos m
                         JOIN convenio c ON m.codigo_estudiante = c.codigo_estudiante
                         WHERE c.codigo_convenio = ?
                         ORDER BY m.fecha_movimiento DESC";
            
            $stmt = $conn->prepare($queryMovimientos);
            $stmt->bind_param('i', $codigo);
            $stmt->execute();
            $movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
            // Respuesta unificada
            echo json_encode([
                'estudiante' => $estudiante,
                'movimientos' => $movimientos
            ]);
            
        } catch(Exception $e) {
            http_response_code(500);
            die(json_encode(["error" => $e->getMessage()]));
        }
    }


