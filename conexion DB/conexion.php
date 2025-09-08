<?php
function conectarDB($usuario = null, $clave = null) {
    $host = "localhost";
    $port = "5432";
    $dbname = "masa_corporal";
    $conn = @pg_connect("host=$host port=$port dbname=$dbname user=$usuario password=$clave");
    if (!$conn) {
        return false; // No detener el script, solo retorna false
    }
    return $conn;
}
?>
