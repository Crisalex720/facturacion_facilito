<?php
session_start();
include '../conexion_DB/conexion.php';

// Usar las credenciales de la sesión
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$clave = isset($_SESSION['clave']) ? $_SESSION['clave'] : null;
$conn = conectarDB($usuario, $clave);
if (!$conn) {
    die('<div class="alert alert-danger">No se pudo conectar a la base de datos. Verifique sus credenciales.</div>');
}

// Obtener trabajador logueado
$trabajador = pg_fetch_assoc(pg_query($conn, "SELECT * FROM trabajadores WHERE nombre = '$usuario' LIMIT 1"));
$id_trabajador = $trabajador ? $trabajador['id_trab'] : null;

// Obtener cliente final (por defecto)
$consumidor_final = pg_fetch_assoc(pg_query($conn, "SELECT id_cliente, nombre_cl FROM cliente WHERE lower(nombre_cl) LIKE '%cliente final%' LIMIT 1"));
$id_cliente_default = $consumidor_final ? $consumidor_final['id_cliente'] : null;
$nombre_cliente_default = $consumidor_final ? $consumidor_final['nombre_cl'] : 'Cliente Final';

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar producto al carrito
if (isset($_POST['agregar_producto'])) {
    $id_producto = $_POST['id_producto'];
    $cantidad = max(1, intval($_POST['cantidad']));
    // Evitar duplicados: suma cantidades si ya existe
    $encontrado = false;
    foreach ($_SESSION['carrito'] as &$item) {
        if ($item['id_producto'] == $id_producto) {
            $item['cantidad'] += $cantidad;
            $encontrado = true;
            break;
        }
    }
    unset($item);
    if (!$encontrado) {
        $_SESSION['carrito'][] = [
            'id_producto' => $id_producto,
            'cantidad' => $cantidad
        ];
    }
}

// Quitar producto del carrito
if (isset($_GET['quitar'])) {
    $id_quitar = $_GET['quitar'];
    $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($id_quitar) {
        return $item['id_producto'] != $id_quitar;
    });
}

// Obtener el próximo número de factura y prefijo (siempre FACT)
$sql_next_fact = "SELECT COALESCE(MAX(consecutivo),0)+1 AS next_consecutivo FROM vista_listado_facturas";
$res_next_fact = pg_query($conn, $sql_next_fact);
$row_next_fact = pg_fetch_assoc($res_next_fact);
$next_consecutivo = isset($row_next_fact['next_consecutivo']) ? intval($row_next_fact['next_consecutivo']) : 1;
$prefijo_fact = 'FACT';

// Finalizar compra (crear factura y lista_prod)
$mensaje = '';
if (isset($_POST['finalizar_factura']) && count($_SESSION['carrito']) > 0) {
    $id_cliente = isset($_POST['id_cliente']) && $_POST['id_cliente'] ? $_POST['id_cliente'] : $id_cliente_default;
    // Insertar factura con num_fact (consecutivo)
    $sql_fact = "INSERT INTO factura (cliente, id_trab, estado, num_fact) VALUES ($id_cliente, $id_trabajador, 'activa', $next_consecutivo) RETURNING id_fact";
    $res_fact = pg_query($conn, $sql_fact);
    if ($res_fact && ($row_fact = pg_fetch_assoc($res_fact))) {
        $id_fact = $row_fact['id_fact'];
        // Insertar productos del carrito
        foreach ($_SESSION['carrito'] as $item) {
            $id_prod = $item['id_producto'];
            $cant = $item['cantidad'];
            pg_query($conn, "INSERT INTO lista_prod (id_fact, id_producto, cantidad) VALUES ($id_fact, $id_prod, $cant)");
        }
        $_SESSION['carrito'] = [];
        $mensaje = 'Factura registrada correctamente.';
    } else {
        $mensaje = 'Error al registrar la factura.';
    }
}

// Listar productos para seleccionar
$productos = pg_query($conn, "SELECT * FROM producto ORDER BY nombre_prod");
// Listar clientes para seleccionar
$clientes = pg_query($conn, "SELECT * FROM cliente ORDER BY nombre_cl");
// Anular factura si se solicita
if (isset($_GET['anular_factura'])) {
    $id_fact_anular = intval($_GET['anular_factura']);
    pg_query($conn, "UPDATE factura SET estado = 'anulado' WHERE id_fact = $id_fact_anular");
}

// Listar facturas con productos y cantidades detalladas
$facturas = pg_query($conn, "
    SELECT f.*, 
        (
            SELECT string_agg(p.nombre_prod || ' (' || lp.cantidad || ')', ', ' ORDER BY p.nombre_prod)
            FROM lista_prod lp
            JOIN producto p ON lp.id_producto = p.id_producto
            WHERE lp.id_fact = f.id_fact
        ) AS productos_detalle
    FROM vista_listado_facturas f
    ORDER BY f.consecutivo DESC
");


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
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
                    <a class="btn btn-primary nav-btn disabled" href="../facturacion/facturacion.php" tabindex="-1" aria-disabled="true">Facturación</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="btn btn-outline-secondary nav-btn" href="../devoluciones/devoluciones.php">Devoluciones</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="btn btn-outline-success nav-btn" href="../inventario/inventario.php" tabindex="-1" aria-disabled="true">Inventario</a>
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
    <h2 class="mb-4">Facturación (Carrito de Compras)</h2>
    <?php if ($mensaje) { ?>
    <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php } ?>
    <form method="post" class="mb-3">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label>Cliente</label>
                <select name="id_cliente" class="form-control">
                    <option value="<?php echo $id_cliente_default; ?>" selected><?php echo htmlspecialchars($nombre_cliente_default); ?></option>
                    <?php while($cl = pg_fetch_assoc($clientes)): ?>
                        <option value="<?php echo $cl['id_cliente']; ?>"><?php echo htmlspecialchars($cl['nombre_cl']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Producto</label>
                <select name="id_producto" class="form-control">
                    <?php while($p = pg_fetch_assoc($productos)): ?>
                        <option value="<?php echo $p['id_producto']; ?>"><?php echo htmlspecialchars($p['nombre_prod']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Cantidad</label>
                <input type="number" name="cantidad" class="form-control" min="1" value="1">
            </div>
            <div class="col-md-2">
                <button type="submit" name="agregar_producto" class="btn btn-primary w-100">Agregar al carrito</button>
            </div>
        </div>
    </form>
    <h4>Carrito actual</h4>
    <div class="mb-2">
        <span class="badge badge-info" style="font-size:1.1em;">Próxima factura: <strong><?php echo htmlspecialchars($prefijo_fact) . '-' . $next_consecutivo; ?></strong></span>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio unitario</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $total_factura = 0;
        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $prod = pg_fetch_assoc(pg_query($conn, "SELECT nombre_prod, precio_ventap FROM producto WHERE id_producto = {$item['id_producto']}"));
                $precio = $prod ? floatval($prod['precio_ventap']) : 0;
                $subtotal = $precio * $item['cantidad'];
                $total_factura += $subtotal;
                echo '<tr>';
                echo '<td>' . htmlspecialchars($prod['nombre_prod']) . '</td>';
                echo '<td>$' . number_format($precio, 2) . '</td>';
                echo '<td>' . $item['cantidad'] . '</td>';
                echo '<td>$' . number_format($subtotal, 2) . '</td>';
                echo '<td><a href="?quitar=' . $item['id_producto'] . '" class="btn btn-danger btn-sm">Quitar</a></td>';
                echo '</tr>';
            }
            echo '<tr style="font-weight:bold;background:#f1f5fa;"><td colspan="3" class="text-right">Total factura</td><td colspan="2">$' . number_format($total_factura, 2) . '</td></tr>';
        } else {
            echo '<tr><td colspan="5">El carrito está vacío.</td></tr>';
        }
        ?>
        </tbody>
    </table>
    <form method="post">
        <button type="submit" name="finalizar_factura" class="btn btn-success" <?php if(empty($_SESSION['carrito'])) echo 'disabled'; ?>>Finalizar y Registrar Factura</button>
    </form>
    <hr>
    <h3>Listado de Facturas</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Prefijo</th>
                <th>Consecutivo</th>
                <th>Cliente</th>
                <th>Productos</th>
                <th>Atendido por</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php while($f = pg_fetch_assoc($facturas)): ?>
            <tr>
                <td><?php echo isset($f['prefijo_fact']) ? htmlspecialchars($f['prefijo_fact']) : 'FACT'; ?></td>
                <td><?php echo $f['consecutivo']; ?></td>
                <td><?php echo htmlspecialchars($f['nombre_cliente']); ?></td>
                <td><?php echo isset($f['productos_detalle']) ? htmlspecialchars($f['productos_detalle']) : ''; ?></td>
                <td><?php echo htmlspecialchars($f['atendido_por']); ?></td>
                <td>$<?php echo number_format($f['total_factura'], 2); ?></td>
                <td><?php echo isset($f['estado']) ? htmlspecialchars($f['estado']) : ''; ?></td>
                <td>
                    <?php if (isset($f['estado']) && $f['estado'] !== 'anulado'): ?>
                        <a href="?anular_factura=<?php echo $f['id_fact']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea anular esta factura?');">Anular</a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>