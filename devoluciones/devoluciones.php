<?php
session_start();
include '../conexion_DB/conexion.php';
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$clave = isset($_SESSION['clave']) ? $_SESSION['clave'] : null;
$conn = conectarDB($usuario, $clave);
if (!$conn) {
    die('<div class="alert alert-danger">No se pudo conectar a la base de datos. Verifique sus credenciales.</div>');
}

$mensaje = '';

// Procesar devolución parcial
if (isset($_POST['devolver']) && isset($_POST['id_factura'])) {
    $id_factura = intval($_POST['id_factura']);
    $cantidades = isset($_POST['cant_devolver']) ? $_POST['cant_devolver'] : [];
    $ids_lista = isset($_POST['id_lista']) ? $_POST['id_lista'] : [];

    // Validar que no se devuelvan todos los productos
    $total_productos = 0;
    $total_devueltos = 0;
    foreach ($ids_lista as $i => $id_lista) {
        $q = pg_query($conn, "SELECT cantidad FROM lista_prod WHERE id_lista = $id_lista");
        $row = pg_fetch_assoc($q);
        $cantidad = $row ? intval($row['cantidad']) : 0;
        $dev = isset($cantidades[$i]) ? intval($cantidades[$i]) : 0;
        $total_productos += $cantidad;
        $total_devueltos += min($dev, $cantidad);
    }
    if ($total_devueltos >= $total_productos) {
        $mensaje = '<div class="alert alert-danger">No puedes devolver todos los productos. Al menos uno debe quedar en la factura.</div>';
    } else {
        // Procesar devoluciones parciales
        foreach ($ids_lista as $i => $id_lista) {
            $q = pg_query($conn, "SELECT cantidad, id_producto FROM lista_prod WHERE id_lista = $id_lista");
            $row = pg_fetch_assoc($q);
            $cantidad = $row ? intval($row['cantidad']) : 0;
            $id_producto = $row ? intval($row['id_producto']) : 0;
            $dev = isset($cantidades[$i]) ? intval($cantidades[$i]) : 0;
            if ($dev > 0 && $dev < $cantidad) {
                // Devolución parcial: restar cantidad en lista_prod y sumar stock
                pg_query($conn, "UPDATE lista_prod SET cantidad = cantidad - $dev WHERE id_lista = $id_lista");
                pg_query($conn, "UPDATE producto SET cantidad_prod = cantidad_prod + $dev WHERE id_producto = $id_producto");
            } elseif ($dev > 0 && $dev == $cantidad) {
                // Devolución total de ese producto: eliminar de lista_prod y sumar stock
                pg_query($conn, "DELETE FROM lista_prod WHERE id_lista = $id_lista");
                pg_query($conn, "UPDATE producto SET cantidad_prod = cantidad_prod + $dev WHERE id_producto = $id_producto");
            }
        }
        $mensaje = '<div class="alert alert-success">Devolución procesada correctamente.</div>';
    }
}

// Buscador de factura por num_fact
$busqueda = isset($_POST['buscar_num_fact']) ? trim($_POST['buscar_num_fact']) : '';
if ($busqueda !== '') {
    $facturas_res = pg_query($conn, "SELECT id_fact, num_fact, prefijo_fact FROM factura WHERE estado = 'activa' AND num_fact::text ILIKE '%$busqueda%' ORDER BY id_fact DESC");
} else {
    $facturas_res = pg_query($conn, "SELECT id_fact, num_fact, prefijo_fact FROM factura WHERE estado = 'activa' ORDER BY id_fact DESC");
}
$facturas = [];
while ($f = pg_fetch_assoc($facturas_res)) {
    $facturas[] = $f;
}

// Si se seleccionó una factura, mostrar sus productos
$productos_factura = [];
if (isset($_POST['id_factura'])) {
    $id_factura = intval($_POST['id_factura']);
    $res = pg_query($conn, "SELECT lp.id_lista, p.nombre_prod, lp.cantidad FROM lista_prod lp JOIN producto p ON lp.id_producto = p.id_producto WHERE lp.id_fact = $id_factura");
    while ($row = pg_fetch_assoc($res)) {
        $productos_factura[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Devoluciones</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
<body>
 <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand font-weight-bold text-primary">Facturación Fácilito</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-info nav-btn" href="../trabajadores/usuarios.php">Usuarios</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-primary nav-btn" href="../facturacion/facturacion.php">Facturación</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-secondary disabled nav-btn" href="../devoluciones/devoluciones.php"  tabindex="-1" aria-disabled="true">Devoluciones</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-success nav-btn" href="../inventario/inventario.php">Inventario</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-info nav-btn" href="../clientes/registro_clientes.php">Clientes</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-danger nav-btn" href="../logout.php">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<div class="container py-4">
    <h2 class="mb-4">Devoluciones Parciales</h2>
    <?php echo $mensaje; ?>

    <form method="post" class="mb-4 facturacion-form">
        <div class="row align-items-end">
            <div class="col-md-4 mb-2">
                <label class="font-weight-bold text-primary">Buscar factura por número</label>
                <div class="input-group">
                    <input type="text" name="buscar_num_fact" class="form-control" placeholder="Ej: 5" value="<?php echo htmlspecialchars($busqueda); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-primary" type="submit">Buscar</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <label class="font-weight-bold text-primary">Selecciona una factura activa</label>
                <select name="id_factura" class="form-control" required onchange="this.form.submit()">
                    <option value="">-- Selecciona --</option>
                    <?php foreach($facturas as $f): ?>
                        <option value="<?php echo $f['id_fact']; ?>" <?php if(isset($id_factura) && $id_factura == $f['id_fact']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($f['prefijo_fact']) . '-' . $f['num_fact']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <?php if (!empty($productos_factura)) { ?>
    <form method="post" class="mb-4 facturacion-form">
        <input type="hidden" name="id_factura" value="<?php echo $id_factura; ?>">
        <table class="table table-bordered carrito-table">
            <thead class="carrito-thead">
                <tr>
                    <th>Producto</th>
                    <th>Cantidad en factura</th>
                    <th>Cantidad a devolver</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($productos_factura as $i => $prod): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prod['nombre_prod']); ?></td>
                    <td><?php echo $prod['cantidad']; ?></td>
                    <td>
                        <input type="number" name="cant_devolver[]" class="form-control" min="0" max="<?php echo $prod['cantidad']; ?>" value="0">
                        <input type="hidden" name="id_lista[]" value="<?php echo $prod['id_lista']; ?>">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" name="devolver" class="btn btn-success font-weight-bold w-100 btn-procesar-devol">Procesar devolución</button>
    </form>
    <?php } ?>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>