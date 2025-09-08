<?php
session_start();
session_destroy();
header("Location: ../facturacion_facilito/login.php");
exit;
?>