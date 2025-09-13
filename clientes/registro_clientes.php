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

// Mensaje de feedback
$mensaje = '';

// Crear cliente
if (isset($_POST['crear'])) {
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $id_pais = $_POST['id_pais'];
    $id_depart = $_POST['id_depart'];
    $id_ciudad = $_POST['id_ciudad'];
    $sql = "INSERT INTO cliente (cedula, nombre_cl, correo, id_pais, id_depart, id_ciudad) VALUES ($cedula, '$nombre', '$correo', $id_pais, $id_depart, $id_ciudad)";
    if (pg_query($conn, $sql)) {
        $mensaje = 'Cliente registrado correctamente.';
    } else {
        $mensaje = 'Error al registrar cliente.';
    }
}

// Eliminar cliente
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if (pg_query($conn, "DELETE FROM cliente WHERE id_cliente = $id")) {
        $mensaje = 'Cliente eliminado correctamente.';
    } else {
        $mensaje = 'Error al eliminar cliente.';
    }
}

// Editar cliente
if (isset($_POST['editar'])) {
    $id = $_POST['id_cliente'];
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $id_pais = $_POST['id_pais'];
    $id_depart = $_POST['id_depart'];
    $id_ciudad = $_POST['id_ciudad'];
    $sql = "UPDATE cliente SET cedula=$cedula, nombre_cl='$nombre', correo='$correo', id_pais=$id_pais, id_depart=$id_depart, id_ciudad=$id_ciudad WHERE id_cliente=$id";
    if (pg_query($conn, $sql)) {
        $mensaje = 'Cliente actualizado correctamente.';
    } else {
        $mensaje = 'Error al actualizar cliente.';
    }
}

// Listar clientes
$clientes = pg_query($conn, "SELECT c.*, d.nombre_depart, p.nombre_pais, ciu.nombre_ciudad FROM cliente c LEFT JOIN departamento d ON c.id_depart = d.id_depart LEFT JOIN pais p ON c.id_pais = p.id_pais LEFT JOIN ciudad ciu ON c.id_ciudad = ciu.id_ciudad ORDER BY c.id_cliente DESC");

// Listar países, departamentos y ciudades para selects
$paises = pg_query($conn, "SELECT * FROM pais ORDER BY nombre_pais");
$departamentos = pg_query($conn, "SELECT * FROM departamento ORDER BY nombre_depart");
$ciudades = pg_query($conn, "SELECT * FROM ciudad ORDER BY nombre_ciudad");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Clientes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Barra de navegación superior -->
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
                        <a class="btn btn-outline-secondary nav-btn" href="../devoluciones/devoluciones.php">Devoluciones</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-success nav-btn" href="../inventario/inventario.php">Inventario</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-info nav-btn disabled" href="../clientes/registro_clientes.php" tabindex="-1" aria-disabled="true">Clientes</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-danger nav-btn" href="../logout.php">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="clientes-container container py-4">
    <h2 class="clientes-title">Gestión de Clientes</h2>
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
    <div class="card clientes-form-card mb-4">
        <div class="card-body">
            <form method="post" class="clientes-form-row">
                <input type="hidden" name="id_cliente" id="id_cliente">
                <div class="fila-campos">
                    <div><input type="number" name="cedula" class="form-control" placeholder="Cédula" required></div>
                    <div><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div>
                    <div><input type="email" name="correo" class="form-control" placeholder="Correo" required></div>
                </div>
                <div class="fila-selects">
                    <div>
                        <select name="id_pais" class="form-select" required>
                            <option value="">País</option>
                            <?php while($p = pg_fetch_assoc($paises)): ?>
                                <option value="<?php echo $p['id_pais']; ?>"><?php echo $p['nombre_pais']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <select name="id_depart" class="form-select" required>
                            <option value="">Depto</option>
                            <?php while($d = pg_fetch_assoc($departamentos)): ?>
                                <option value="<?php echo $d['id_depart']; ?>"><?php echo $d['nombre_depart']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <select name="id_ciudad" class="form-select" required>
                            <option value="">Ciudad</option>
                            <?php while($c = pg_fetch_assoc($ciudades)): ?>
                                <option value="<?php echo $c['id_ciudad']; ?>"><?php echo $c['nombre_ciudad']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="fila-boton">
                    <button type="submit" name="crear" class="btn btn-success w-100">Registrar</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card clientes-table-card">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>País</th>
                        <th>Depto</th>
                        <th>Ciudad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($cl = pg_fetch_assoc($clientes)): ?>
                    <tr>
                        <td><?php echo $cl['id_cliente']; ?></td>
                        <td><?php echo $cl['cedula']; ?></td>
                        <td><?php echo htmlspecialchars($cl['nombre_cl']); ?></td>
                        <td><?php echo htmlspecialchars($cl['correo']); ?></td>
                        <td><?php echo htmlspecialchars($cl['nombre_pais']); ?></td>
                        <td><?php echo htmlspecialchars($cl['nombre_depart']); ?></td>
                        <td><?php echo htmlspecialchars($cl['nombre_ciudad']); ?></td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" onclick="editarCliente(
                                '<?php echo $cl['id_cliente']; ?>',
                                '<?php echo $cl['cedula']; ?>',
                                '<?php echo htmlspecialchars($cl['nombre_cl']); ?>',
                                '<?php echo htmlspecialchars($cl['correo']); ?>',
                                '<?php echo $cl['id_pais']; ?>',
                                '<?php echo $cl['id_depart']; ?>',
                                '<?php echo $cl['id_ciudad']; ?>'
                            )"><i class="bi bi-pencil-square"></i> Editar</button>
                            <a href="?eliminar=<?php echo $cl['id_cliente']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar cliente?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Modal para editar cliente -->
            <div id="modalEditarCliente" class="modal">
                <div class="modal-content" style="max-width:600px;margin:auto;">
                    <span class="close" id="cerrarModalEditarCliente">&times;</span>
                    <h3 class="mb-3">Editar Cliente</h3>
                    <form method="post" class="clientes-form-row" id="formEditarCliente">
                        <input type="hidden" name="id_cliente" id="edit_id_cliente">
                        <div class="fila-campos">
                            <div><input type="number" name="cedula" id="edit_cedula" class="form-control" placeholder="Cédula" required></div>
                            <div><input type="text" name="nombre" id="edit_nombre" class="form-control" placeholder="Nombre" required></div>
                            <div><input type="email" name="correo" id="edit_correo" class="form-control" placeholder="Correo" required></div>
                        </div>
                        <div class="fila-selects">
                            <div>
                                <select name="id_pais" id="edit_id_pais" class="form-select" required>
                                    <option value="">País</option>
                                    <?php pg_result_seek($paises, 0); while($p = pg_fetch_assoc($paises)): ?>
                                        <option value="<?php echo $p['id_pais']; ?>"><?php echo $p['nombre_pais']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <select name="id_depart" id="edit_id_depart" class="form-select" required>
                                    <option value="">Depto</option>
                                    <?php pg_result_seek($departamentos, 0); while($d = pg_fetch_assoc($departamentos)): ?>
                                        <option value="<?php echo $d['id_depart']; ?>"><?php echo $d['nombre_depart']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div>
                                <select name="id_ciudad" id="edit_id_ciudad" class="form-select" required>
                                    <option value="">Ciudad</option>
                                    <?php pg_result_seek($ciudades, 0); while($c = pg_fetch_assoc($ciudades)): ?>
                                        <option value="<?php echo $c['id_ciudad']; ?>" data-depart="<?php echo $c['id_depart']; ?>"><?php echo $c['nombre_ciudad']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="fila-boton">
                            <button type="submit" name="editar" class="btn btn-warning w-100">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
// Obtener selects
const selectPais = document.querySelector('select[name="id_pais"]');
const selectDepto = document.querySelector('select[name="id_depart"]');
const selectCiudad = document.querySelector('select[name="id_ciudad"]');

// Guardar todas las ciudades en JS
const ciudadesData = [
<?php
pg_result_seek($ciudades, 0);
while($c = pg_fetch_assoc($ciudades)) {
    echo "{id: {$c['id_ciudad']}, nombre: '".addslashes($c['nombre_ciudad'])."', id_depart: {$c['id_depart']}},\n";
}
?>
];

// Bloquear ciudad inicialmente
selectCiudad.disabled = true;

// Al cambiar departamento, filtrar ciudades
selectDepto.addEventListener('change', function() {
    const idDept = this.value;
    selectCiudad.innerHTML = '<option value="">Ciudad</option>';
    if (!idDept) {
        selectCiudad.disabled = true;
        return;
    }
    const filtradas = ciudadesData.filter(c => c.id_depart == idDept);
    filtradas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.nombre;
        selectCiudad.appendChild(opt);
    });
    selectCiudad.disabled = false;
});

// Modal editar cliente
var modalEditarCliente = document.getElementById('modalEditarCliente');
var cerrarModalEditarCliente = document.getElementById('cerrarModalEditarCliente');
cerrarModalEditarCliente.onclick = function() { modalEditarCliente.style.display = 'none'; }
window.onclick = function(event) {
    if (event.target == modalEditarCliente) { modalEditarCliente.style.display = 'none'; }
}
function editarCliente(id, cedula, nombre, correo, id_pais, id_depart, id_ciudad) {
    document.getElementById('edit_id_cliente').value = id;
    document.getElementById('edit_cedula').value = cedula;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_correo').value = correo;
    document.getElementById('edit_id_pais').value = id_pais;
    document.getElementById('edit_id_depart').value = id_depart;
    // Filtrar ciudades según departamento
    var selectCiudad = document.getElementById('edit_id_ciudad');
    for (var i = 0; i < selectCiudad.options.length; i++) {
        var opt = selectCiudad.options[i];
        opt.style.display = (opt.getAttribute('data-depart') == id_depart || opt.value == '') ? '' : 'none';
    }
    selectCiudad.value = id_ciudad;
    modalEditarCliente.style.display = 'block';
}
// Al cambiar departamento en modal editar, filtrar ciudades
var selectEditDept = document.getElementById('edit_id_depart');
selectEditDept.addEventListener('change', function() {
    var idDept = this.value;
    var selectCiudad = document.getElementById('edit_id_ciudad');
    for (var i = 0; i < selectCiudad.options.length; i++) {
        var opt = selectCiudad.options[i];
        opt.style.display = (opt.getAttribute('data-depart') == idDept || opt.value == '') ? '' : 'none';
    }
    selectCiudad.value = '';
});
</script>
</body>
</html>