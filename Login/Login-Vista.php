<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Udev</title>
    <link rel="stylesheet" href="../css/bootstrap.rtl.css">
    <link rel="stylesheet" href="../css/bootstrap.css">
</head>
<body>

<div class="container d-flex justify-content-center mt-5">
    <div class="card p-4 shadow-lg w-50 w-md-25">
        <h2 class="text-center text-dark">Iniciar Sesión</h2>

        <!-- Mostrar mensajes de error -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                switch ($_GET['error']) {
                    case 'empty_fields':
                        echo "Todos los campos son obligatorios.";
                        break;
                    case 'db_connection':
                        echo "Error de conexión con la base de datos.";
                        break;
                    case 'sql_error':
                        echo "Error en la consulta de la base de datos.";
                        break;
                    case 'incorrect_password':
                        echo "Contraseña incorrecta.";
                        break;
                    case 'email_not_found':
                        echo "Correo no encontrado.";
                        break;
                    case 'session_expired':
                        echo "Tu sesión ha expirado. Por favor, vuelve a iniciar sesión.";
                        break;
                    default:
                        echo "Error desconocido.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" enctype="multipart/form-data" method="post" action="Login-Controlador.php?accion=login">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico:</label>
                <input type="email" class="form-control" id="correo" name="correo" required>
            </div>

            <div class="mb-3">
                <label for="contraseña" class="form-label">Contraseña:</label>
                <input type="password" class="form-control" id="clave" name="clave" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2" id="login_button" name="login_button">
                Iniciar Sesión
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
