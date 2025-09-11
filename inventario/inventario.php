<?php
// Oculta errores y warnings en producción
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();
include '../conexion_DB/conexion.php';

// Verifica si el usuario está autenticado
if (!isset($_SESSION['usuario']) || !isset($_SESSION['clave'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$clave = $_SESSION['clave'];
$conn = conectarDB($usuario, $clave);

// Verifica privilegios de INSERT
$puede_insertar = false;
$consulta_privilegios = "SELECT has_table_privilege('$usuario', 'producto', 'INSERT') AS puede_insertar;";
$result = pg_query($conn, $consulta_privilegios);
if ($result && $row = pg_fetch_assoc($result)) {
    $puede_insertar = $row['puede_insertar'] === 't';
}


// Manejo de inserción y actualización de producto (PRG pattern)
$mensaje = '';

// Actualización de producto
if (isset($_POST['id']) && isset($_POST['codigo_barra']) && isset($_POST['nombre']) && isset($_POST['precio_venta']) && isset($_POST['form_edit'])) {
    $id = $_POST['id'];
    $codigo = trim($_POST['codigo_barra']);
    $nombre = trim($_POST['nombre']);
    $cantidad = isset($_POST['cantidad']) && $_POST['cantidad'] !== '' ? $_POST['cantidad'] : 0;
    $precio_costo = isset($_POST['precio_costo']) && $_POST['precio_costo'] !== '' ? $_POST['precio_costo'] : 0;
    $precio_venta = $_POST['precio_venta'];

    if ($codigo === '' || $nombre === '' || $precio_venta === '' || $precio_venta === null) {
        header('Location: inventario.php?msg=campos_obligatorios');
        exit;
    }

    $update = "UPDATE producto SET barcode='$codigo', nombre_prod='$nombre', cantidad_prod=$cantidad, precio_costop=$precio_costo, precio_ventap=$precio_venta WHERE id_producto=$id";
    if (pg_query($conn, $update)) {
        header('Location: inventario.php?msg=actualizado');
        exit;
    } else {
        $error = pg_last_error($conn);
        header('Location: inventario.php?msg=error_update&pgerr=' . urlencode($error));
        exit;
    }
}

// Inserción de producto
if ($puede_insertar && isset($_POST['codigo_barra']) && !isset($_POST['form_edit'])) {
    $codigo = trim($_POST['codigo_barra']);
    $nombre = trim($_POST['nombre']);
    $cantidad = isset($_POST['cantidad']) && $_POST['cantidad'] !== '' ? $_POST['cantidad'] : 0;
    $precio_costo = isset($_POST['precio_costo']) && $_POST['precio_costo'] !== '' ? $_POST['precio_costo'] : 0;
    $precio_venta = $_POST['precio_venta'];

    // Validación: código de barras, nombre y precio de venta no pueden ser nulos o vacíos
    if ($codigo === '' || $nombre === '' || $precio_venta === '' || $precio_venta === null) {
        header('Location: inventario.php?msg=campos_obligatorios');
        exit;
    }

    $insert = "INSERT INTO producto (barcode, nombre_prod, cantidad_prod, precio_costop, precio_ventap) VALUES ('$codigo', '$nombre', $cantidad, $precio_costo, $precio_venta)";
    if (pg_query($conn, $insert)) {
        header('Location: inventario.php?msg=ok');
        exit;
    } else {
        header('Location: inventario.php?msg=error');
        exit;
    }
}

// Mensaje amigable tras redirección
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'ok') {
        $mensaje = 'Producto registrado correctamente.';
    } elseif ($_GET['msg'] === 'actualizado') {
        $mensaje = 'Producto actualizado correctamente.';
    } elseif ($_GET['msg'] === 'error') {
        $mensaje = 'Error al registrar producto.';
    } elseif ($_GET['msg'] === 'error_update') {
        $mensaje = 'Error al actualizar producto.';
        if (isset($_GET['pgerr'])) {
            $mensaje .= '<br><small class="text-danger">' . htmlspecialchars($_GET['pgerr']) . '</small>';
        }
    } elseif ($_GET['msg'] === 'campos_obligatorios') {
        $mensaje = 'Debe completar Código de Barras, Nombre y Precio de Venta.';
    }
}

// Consulta de productos (máximo 20 por defecto)
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 20;
$productos = pg_query($conn, "SELECT * FROM producto ORDER BY id_producto DESC LIMIT $limite");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Productos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
        <div class="container py-4">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h2 class="mb-0">Inventario de Productos</h2>
                                <button class="btn btn-success" id="abrirModal" <?php if(!$puede_insertar) echo 'disabled'; ?>>
                                    <i class="bi bi-plus-circle"></i> Agregar Producto
                                </button>
                            </div>
                            <?php if ($mensaje) { echo "<div class='alert alert-info'>$mensaje</div>"; } ?>
                            <form method="get" class="row g-2 align-items-center mb-3">
                                <div class="col-auto">
                                    <label class="col-form-label">Mostrar:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="number" name="limite" min="1" value="<?php echo $limite; ?>" class="form-control form-control-sm" style="width:80px;">
                                </div>
                                <div class="col-auto">
                                    <span>productos</span>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Actualizar</button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Código de Barras</th>
                                            <th>Nombre</th>
                                            <th>Cantidad</th>
                                            <th>Precio Costo</th>
                                            <th>Precio Venta</th>
                                            <th>Editar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                <?php while($row = pg_fetch_assoc($productos)): ?>
                        <tr>
                                <td><?php echo htmlspecialchars($row['id_producto']); ?></td>
                                <td><?php echo htmlspecialchars($row['barcode']); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre_prod']); ?></td>
                                <td><?php echo number_format($row['cantidad_prod']); ?></td>
                                <td>$<?php echo number_format($row['precio_costop'], 2); ?></td>
                                <td>$<?php echo number_format($row['precio_ventap'], 2); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editarProducto('<?php echo $row['id_producto']; ?>', '<?php echo htmlspecialchars($row['barcode']); ?>', '<?php echo htmlspecialchars($row['nombre_prod']); ?>', '<?php echo $row['cantidad']; ?>', '<?php echo $row['precio_costop']; ?>', '<?php echo $row['precio_ventap']; ?>')">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>
                                </td>
                        </tr>
                <?php endwhile; ?>
        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Modal para agregar producto -->
    <div id="modalAgregar" class="modal">
            <div class="modal-content">
                <span class="close" id="cerrarModal">&times;</span>
                <h3 class="mb-3">Agregar Producto</h3>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" name="codigo_barra" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" min="0" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Costo</label>
                        <input type="number" name="precio_costo" min="0" step="0.01" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> value="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Venta</label>
                        <input type="number" name="precio_venta" min="0" step="0.01" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?>>
                    </div>
                    <button class="btn btn-success w-100" type="submit" <?php if(!$puede_insertar) echo 'disabled'; ?>>Registrar</button>
                </form>
            </div>
    </div>

    <!-- Modal para editar producto (solo estructura, funcionalidad JS a implementar) -->
    <div id="modalEditar" class="modal">
            <div class="modal-content">
                <span class="close" id="cerrarEditar">&times;</span>
                <h3 class="mb-3">Editar Producto</h3>
                <form id="formEditar" method="post" action="inventario.php">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="form_edit" value="1">
                    <div class="mb-3">
                        <label class="form-label">Código de Barras</label>
                        <input type="text" name="codigo_barra" id="edit_codigo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" id="edit_cantidad" min="0" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Costo</label>
                        <input type="number" name="precio_costo" id="edit_precio_costo" min="0" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Venta</label>
                        <input type="number" name="precio_venta" id="edit_precio_venta" min="0" step="0.01" class="form-control" required>
                    </div>
                    <button class="btn btn-warning w-100" type="submit">Actualizar</button>
                </form>
            </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </div>

    <script>
    // Modal agregar
    var modal = document.getElementById('modalAgregar');
    var btn = document.getElementById('abrirModal');
    var span = document.getElementById('cerrarModal');
    btn.onclick = function() { modal.style.display = 'block'; }
    span.onclick = function() { modal.style.display = 'none'; }
    window.onclick = function(event) { if (event.target == modal) { modal.style.display = 'none'; } }

    // Modal editar
    var modalEditar = document.getElementById('modalEditar');
    var cerrarEditar = document.getElementById('cerrarEditar');
    cerrarEditar.onclick = function() { modalEditar.style.display = 'none'; }
    window.onclick = function(event) { if (event.target == modalEditar) { modalEditar.style.display = 'none'; } }
    function editarProducto(id, codigo, nombre, cantidad, precio_costo, precio_venta) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_id').readOnly = true;
    document.getElementById('edit_codigo').value = codigo;
    document.getElementById('edit_codigo').readOnly = false;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_nombre').readOnly = false;
    document.getElementById('edit_cantidad').value = cantidad || 0;
    document.getElementById('edit_cantidad').readOnly = false;
    document.getElementById('edit_precio_costo').value = precio_costo;
    document.getElementById('edit_precio_costo').readOnly = false;
    document.getElementById('edit_precio_venta').value = precio_venta;
    document.getElementById('edit_precio_venta').readOnly = false;
    modalEditar.style.display = 'block';
    }
    </script>
</body>
</html>
