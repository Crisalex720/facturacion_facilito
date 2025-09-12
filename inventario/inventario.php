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
    $eliminar_imagen = isset($_POST['eliminar_imagen']) ? $_POST['eliminar_imagen'] : '0';

    // Obtener imagen actual
    $res_img = pg_query($conn, "SELECT imagen_url FROM producto WHERE id_producto = $id");
    $row_img = pg_fetch_assoc($res_img);
    $imagen_actual = $row_img ? $row_img['imagen_url'] : '';
    $imagen_url = $imagen_actual;

    // Eliminar imagen si se solicita
    if ($eliminar_imagen === '1' && $imagen_actual) {
        $ruta_fisica = __DIR__ . '/' . $imagen_actual;
        if (file_exists($ruta_fisica)) {
            unlink($ruta_fisica);
        }
        $imagen_url = '';
    }

    // Subir nueva imagen si se proporciona
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['imagen']['tmp_name'];
        $info = getimagesize($tmp_name);
        if ($info !== false) {
            $ancho = $info[0];
            $alto = $info[1];
            if ($ancho <= 500 && $alto <= 500) {
                // Eliminar imagen anterior si existe
                if ($imagen_actual && file_exists(__DIR__ . '/' . $imagen_actual)) {
                    unlink(__DIR__ . '/' . $imagen_actual);
                }
                $nombre_archivo = uniqid('prod_') . '_' . basename($_FILES['imagen']['name']);
                $ruta_destino = 'imagenes_prod/' . $nombre_archivo;
                if (move_uploaded_file($tmp_name, __DIR__ . '/' . $ruta_destino)) {
                    $imagen_url = $ruta_destino;
                }
            } else {
                $mensaje = 'La imagen debe ser de máximo 500x500 píxeles.';
            }
        } else {
            $mensaje = 'El archivo seleccionado no es una imagen válida.';
        }
    }

    if ($codigo === '' || $nombre === '' || $precio_venta === '' || $precio_venta === null) {
        header('Location: inventario.php?msg=campos_obligatorios');
        exit;
    }

    $update = "UPDATE producto SET barcode='$codigo', nombre_prod='$nombre', cantidad_prod=$cantidad, precio_costop=$precio_costo, precio_ventap=$precio_venta, imagen_url='$imagen_url' WHERE id_producto=$id";
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
    $imagen_url = '';

    // Validación: código de barras, nombre y precio de venta no pueden ser nulos o vacíos
    if ($codigo === '' || $nombre === '' || $precio_venta === '' || $precio_venta === null) {
        header('Location: inventario.php?msg=campos_obligatorios');
        exit;
    }

    // Manejo de imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['imagen']['tmp_name'];
        $info = getimagesize($tmp_name);
        if ($info !== false) {
            $ancho = $info[0];
            $alto = $info[1];
            if ($ancho <= 500 && $alto <= 500) {
                $nombre_archivo = uniqid('prod_') . '_' . basename($_FILES['imagen']['name']);
                $ruta_destino = 'imagenes_prod/' . $nombre_archivo;
                if (move_uploaded_file($tmp_name, __DIR__ . '/' . $ruta_destino)) {
                    $imagen_url = $ruta_destino;
                }
            } else {
                $mensaje = 'La imagen debe ser de máximo 500x500 píxeles.';
            }
        } else {
            $mensaje = 'El archivo seleccionado no es una imagen válida.';
        }
    }

    $insert = "INSERT INTO producto (barcode, nombre_prod, cantidad_prod, precio_costop, precio_ventap, imagen_url) VALUES ('$codigo', '$nombre', $cantidad, $precio_costo, $precio_venta, '$imagen_url')";
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
    } elseif ($_GET['msg'] === 'eliminado') {
        $mensaje = 'Producto eliminado correctamente.';
    } elseif ($_GET['msg'] === 'error_delete') {
        $mensaje = 'Error al eliminar producto.';
        if (isset($_GET['pgerr'])) {
            $mensaje .= '<br><small class="text-danger">' . htmlspecialchars($_GET['pgerr']) . '</small>';
        }
    } elseif ($_GET['msg'] === 'campos_obligatorios') {
        $mensaje = 'Debe completar Código de Barras, Nombre y Precio de Venta.';
    } elseif ($_GET['msg'] === 'stock_actualizado') {
        $mensaje = 'Stock de productos actualizado correctamente.';
    }
}

// Eliminar producto por id
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $delete = "DELETE FROM producto WHERE id_producto = $id_eliminar";
    if (pg_query($conn, $delete)) {
        header('Location: inventario.php?msg=eliminado');
        exit;
    } else {
        $error = pg_last_error($conn);
        header('Location: inventario.php?msg=error_delete&pgerr=' . urlencode($error));
        exit;
    }
}

$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 20;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$where = '';
if ($busqueda !== '') {
    $busqueda_sql = pg_escape_string($conn, $busqueda);
    $where = "WHERE barcode ILIKE '%$busqueda_sql%' OR nombre_prod ILIKE '%$busqueda_sql%'";
}

$productos = pg_query($conn, "SELECT * FROM producto $where ORDER BY id_producto DESC LIMIT $limite");

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
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand font-weight-bold text-primary">Facturación Fácilito</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-info nav-btn" href="../trabajadores/usuarios.php">Usuarios</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-primary nav-btn" href="../facturacion/facturacion.php">Facturación</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-secondary nav-btn" href="../devoluciones/devoluciones.php">Devoluciones</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-success nav-btn disabled" href="../inventario/inventario.php" tabindex="-1" aria-disabled="true">Inventario</a>
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
            <div class="row justify-content-center mb-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm border-10">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <h2 class="mb-0">Inventario de Productos</h2>
                                <button class="btn btn-success" id="abrirModal" <?php if(!$puede_insertar) echo 'disabled'; ?>>
                                    <i class="bi bi-plus-circle"></i> Agregar Producto
                                </button>
                            </div>
                            <?php if ($mensaje) { ?>
                            <div id="modalMensaje" class="modal" style="display:none;">
                                <div class="modal-content" style="max-width:400px;margin:auto;">
                                    <span class="close" id="cerrarModalMensaje">&times;</span>
                                    <div id="contenidoMensaje"><?php echo $mensaje; ?></div>
                                </div>
                            </div>
                            <script>
                            window.onload = function() {
                                var modal = document.getElementById('modalMensaje');
                                var closeBtn = document.getElementById('cerrarModalMensaje');
                                if (modal) {
                                    modal.style.display = 'block';
                                    closeBtn.onclick = function() { modal.style.display = 'none'; window.history.replaceState(null, '', window.location.pathname); };
                                    window.onclick = function(event) { if (event.target == modal) { modal.style.display = 'none'; window.history.replaceState(null, '', window.location.pathname); } }
                                }
                            }
                            </script>
                            <?php } ?>
                            <form method="get" class="row g-6 align-items-center mb-3">
                                <div class="col-auto">
                                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda ?? ''); ?>" class="form-control form-control-sm" placeholder="Buscar por nombre o código...">
                                </div>
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
                                            <th>Imagen</th>
                                            <th>Acciones</th>
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
        <?php if (!empty($row['imagen_url'])): ?>
            <button class="btn btn-info btn-sm" onclick="verImagen('<?php echo $row['imagen_url']; ?>')" type="button">
                <i class="bi bi-image"></i> Ver Imagen
            </button>
        <?php else: ?>
            <span class="text-muted">Sin imagen</span>
        <?php endif; ?>
    </td>
    <td>
        <button class="btn btn-warning btn-sm" onclick="editarProducto('<?php echo $row['id_producto']; ?>', '<?php echo htmlspecialchars($row['barcode']); ?>', '<?php echo htmlspecialchars($row['nombre_prod']); ?>', '<?php echo $row['cantidad_prod']; ?>', '<?php echo $row['precio_costop']; ?>', '<?php echo $row['precio_ventap']; ?>', '<?php echo isset($row['imagen_url']) ? htmlspecialchars($row['imagen_url']) : ''; ?>')">
            <i class="bi bi-pencil-square"></i> Editar
        </button>
        <a href="inventario.php?eliminar=<?php echo $row['id_producto']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este producto?');">
            <i class="bi bi-trash"></i> Eliminar
        </a>
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
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Código de Barras *</label>
                        <input type="text" name="codigo_barra" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> >
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del producto *</label>
                        <input type="text" name="nombre" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> >
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad de unidades *</label>
                        <input type="number" name="cantidad" min="0" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> value="0">
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Precio Costo *</label>
                            <input type="number" name="precio_costo" min="0" step="0.01" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> value="0.00" placeholder="$0.00" >
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Precio Venta *</label>
                            <input type="number" name="precio_venta" min="0" step="0.01" class="form-control" required <?php if(!$puede_insertar) echo 'readonly'; ?> placeholder="$0.00" >
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagen del producto</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*" <?php if(!$puede_insertar) echo 'disabled'; ?> >
                    </div>
                    <button class="btn btn-success w-100" type="submit" <?php if(!$puede_insertar) echo 'disabled'; ?>>Registrar</button>
                </form>
            </div>
    </div>

    <!-- Modal para editar producto (mejorado: permite cambiar/eliminar imagen) -->
    <div id="modalEditar" class="modal">
            <div class="modal-content">
                <span class="close" id="cerrarEditar">&times;</span>
                <h3 class="mb-3">Editar Producto</h3>
                <form id="formEditar" method="post" action="inventario.php" enctype="multipart/form-data">
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
                    <div class="mb-3">
                        <label class="form-label">Imagen actual</label>
                        <div id="edit_imagen_actual" style="margin-bottom:0.5rem;"></div>
                        <button type="button" class="btn btn-outline-danger btn-sm mb-2" id="btnEliminarImagen" style="display:none;">Eliminar imagen</button>
                        <input type="hidden" name="eliminar_imagen" id="eliminar_imagen" value="0">
                        <label class="form-label">Cambiar imagen</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                    </div>
                    <button class="btn btn-warning w-100" type="submit">Actualizar</button>
                </form>
            </div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </div>

    <!-- Botón para abrir el modal de stock -->
    <button class="btn btn-secondary mb-3" id="abrirModalStock">
        <i class="bi bi-box-seam"></i> Ajustar Stock
    </button>

    <!-- Modal para ajustar stock -->
    <div id="modalStock" class="modal">
        <div class="modal-content">
            <span class="close" id="cerrarModalStock">&times;</span>
            <h3 class="mb-3">Ajustar Stock de Productos</h3>
            <form method="post" action="inventario.php">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Código de Barras</th>
                            <th>Nombre</th>
                            <th>Unidades Disponibles</th>
                            <th>Sumar</th>
                            <th>Restar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Mostrar todos los productos para ajuste rápido
                    $productos_stock = pg_query($conn, "SELECT id_producto, barcode, nombre_prod, cantidad_prod FROM producto ORDER BY id_producto");
                    while($row = pg_fetch_assoc($productos_stock)):
                    ?>
                        <tr>
                            <td><?php echo $row['id_producto']; ?></td>
                            <td><?php echo htmlspecialchars($row['barcode']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_prod']); ?></td>
                            <td><?php echo htmlspecialchars($row['cantidad_prod']); ?></td>
                            <td><input type="number" name="sumar[<?php echo $row['id_producto']; ?>]" min="0" class="form-control form-control-sm" style="width:80px;"></td>
                            <td><input type="number" name="restar[<?php echo $row['id_producto']; ?>]" min="0" class="form-control form-control-sm" style="width:80px;"></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <button class="btn btn-primary w-100" type="submit" name="ajustar_stock">Aplicar Cambios</button>
            </form>
        </div>
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
    function editarProducto(id, codigo, nombre, cantidad, precio_costo, precio_venta, imagen_url) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_codigo').value = codigo;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_cantidad').value = cantidad || 0;
    document.getElementById('edit_precio_costo').value = precio_costo;
    document.getElementById('edit_precio_venta').value = precio_venta;
    // Imagen actual
    var imgDiv = document.getElementById('edit_imagen_actual');
    var btnEliminar = document.getElementById('btnEliminarImagen');
    var inputEliminar = document.getElementById('eliminar_imagen');
    if (imagen_url && imagen_url !== '') {
        imgDiv.innerHTML = '<img src="' + imagen_url + '" style="max-width:120px;max-height:80px;border-radius:6px;">';
        btnEliminar.style.display = 'inline-block';
        inputEliminar.value = '0';
    } else {
        imgDiv.innerHTML = '<span class="text-muted">Sin imagen</span>';
        btnEliminar.style.display = 'none';
        inputEliminar.value = '0';
    }
    btnEliminar.onclick = function() {
        imgDiv.innerHTML = '<span class="text-muted">Sin imagen</span>';
        inputEliminar.value = '1';
        btnEliminar.style.display = 'none';
    };
    document.getElementById('modalEditar').style.display = 'block';
}

    // Modal para ver imagen (mejorado, usa id y css)
    function verImagen(url) {
    var modal = document.createElement('div');
    modal.id = 'modalImagenCustom';
    var img = document.createElement('img');
    img.src = url;
    modal.appendChild(img);
    modal.onclick = function() { document.body.removeChild(modal); };
    document.body.appendChild(modal);
}

    // Modal de stock
    var modalStock = document.getElementById('modalStock');
    var btnStock = document.getElementById('abrirModalStock');
    var closeStock = document.getElementById('cerrarModalStock');
    btnStock.onclick = function() { modalStock.style.display = 'block'; }
    closeStock.onclick = function() { modalStock.style.display = 'none'; }
    window.onclick = function(event) {
        if (event.target == modalStock) { modalStock.style.display = 'none'; }
    }
    </script>
</body>
</html>
