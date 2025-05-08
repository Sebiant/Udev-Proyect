<?php
session_start();

// Si el usuario está autenticado, redirigir al dashboard
if (isset($_SESSION['id'])) {
    header("Location: Dashboard/Dashboard.php");
    exit();
} else {
    // Si no está autenticado, mostrar el formulario de login
    header("Location: Login/Login.php");
    exit();
}
?>
