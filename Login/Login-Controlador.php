<?php
session_start();
include '../Conexion.php';

// Tiempo de expiración de sesión (por ejemplo, 30 minutos)
$session_timeout = 30 * 60; // 30 minutos en segundos

// Verificar si la sesión ha expirado
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    session_unset();     // Destruir todas las variables de sesión
    session_destroy();   // Destruir la sesión
    header("Location: Login.php?error=session_expired");
    exit();
}
$_SESSION['last_activity'] = time(); // Actualizar la última actividad de la sesión

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar que los campos no estén vacíos
    if (empty($_POST["correo"]) || empty($_POST["clave"])) {
        header("Location: Login-Vista.php?error=empty_fields");
        exit();
    }

    $correo = trim($_POST["correo"]);
    $clave = trim($_POST["clave"]);

    // Verificar conexión con la base de datos
    if (!$conn) {
        header("Location: Login-Vista.php?error=db_connection");
        exit();
    }

    // Consulta SQL para prevenir inyección SQL
    $sql = "SELECT * FROM usuarios WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        header("Location: Login-Vista.php?error=sql_error");
        exit();
    }

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        // Debugging: Mostrar el hash de la contraseña almacenado y la contraseña ingresada
        echo "Hash almacenado: " . htmlspecialchars($usuario["clave"]) . "<br>"; // Muestra el hash
        echo "Contraseña ingresada: " . htmlspecialchars($clave) . "<br>"; // Muestra la contraseña ingresada

        // Verificar la contraseña
        if (password_verify($clave, $usuario["clave"])) {
            $_SESSION["id"] = $usuario["id"];  // Definir la sesión
            $_SESSION["correo"] = $usuario["correo"];
            header("Location: ../Dashboard/Dashboard.php");
            exit();
        } else {
            // Si la contraseña es incorrecta
            echo "Contraseña incorrecta."; // Depurar el error
            header("Location: Login-Vista.php?error=incorrect_password");
            exit();
        }
    } else {
        // Si el correo no existe
        echo "Correo no encontrado."; // Depurar el error
        header("Location: Login-Vista.php?error=email_not_found");
        exit();
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}
?>
