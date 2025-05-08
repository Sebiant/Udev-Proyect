<?php
session_start();
include '../Conexion.php';

$accion = $_GET['accion'] ?? null;

// Tiempo de expiración de sesión (30 minutos)
$session_timeout = 30 * 60;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    session_unset();
    session_destroy();
    header("Location: Login.php?error=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

switch ($accion) {

    case 'login':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            if (empty($_POST["correo"]) || empty($_POST["clave"])) {
                header("Location: Login-Vista.php?error=empty_fields");
                exit();
            }

            $correo = trim($_POST["correo"]);
            $clave = trim($_POST["clave"]);

            if (!$conn) {
                header("Location: Login-Vista.php?error=db_connection");
                exit();
            }

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

                if (password_verify($clave, $usuario["clave"])) {
                    $_SESSION["id"] = $usuario["id"];
                    $_SESSION["correo"] = $usuario["correo"];
                    header("Location: ../Dashboard/Dashboard.php");
                    exit();
                } else {
                    header("Location: Login-Vista.php?error=incorrect_password");
                    exit();
                }
            } else {
                header("Location: Login-Vista.php?error=email_not_found");
                exit();
            }

            $stmt->close();
            $conn->close();
        } else {
            header("Location: Login-Vista.php?error=invalid_method");
            exit();
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header("Location: Login/Login.php?message=logged_out");
        exit();
        break;

    default:
        header("Location: Login/Login.php?error=invalid_action");
        exit();
}
?>
