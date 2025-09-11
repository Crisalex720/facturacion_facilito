<?php
session_start();
include '../facturacion_facilito/conexion_DB/conexion.php';

// Captura datos del formulario
$usuario = $_POST['usuario'];
$clave = $_POST['clave'];

// Conecta con la base de datos usando las credenciales ingresadas
$conn = conectarDB($usuario, $clave);

if ($conn) {
    // Guarda datos en sesión
    $_SESSION['usuario'] = $usuario;
    $_SESSION['clave'] = $clave;

    // Asigna permisos según el usuario
    $permisos = [
        'ceo' => 'todos',        // Super usuario con todos los permisos
        'caja' => 'caja',
    ];
    // Redirige al inventario
    header("Location: inventario/index.html");
    exit;
} else {
    // Si falla la conexión, vuelve al login con error
    echo pg_last_error();
    exit;
}
?>