<?php
session_start();
include 'conexion.php';

$usuario = $_POST['usuario'];
$clave = $_POST['clave'];

$conn = conectarDB($usuario, $clave);

if ($conn) {
    $_SESSION['usuario'] = $usuario;
    $_SESSION['clave'] = $clave;

    // Asigna permisos según el usuario
    // Puedes mejorar esto usando una tabla de roles en la BD si lo deseas
    $permisos = [
        'valeria' => 'leer',
        'leonardo' => 'crear',
        'alexander' => 'actualizar',
        'cristian' => 'eliminar'
    ];
    $_SESSION['permiso'] = isset($permisos[$usuario]) ? $permisos[$usuario] : '';

    header("Location: index.php");
    exit;
} else {
    header("Location: login.php?error=1");
    exit;
}
?>