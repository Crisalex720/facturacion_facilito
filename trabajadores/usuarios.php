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

// Crear usuario
if (isset($_POST['crear'])) {
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cargo = $_POST['cargo'];
    $contrasena = $_POST['contrasena'];
    $id_pais = $_POST['id_pais'];
    $id_depart = $_POST['id_depart'];
    $id_ciudad = $_POST['id_ciudad'];
    $sql = "INSERT INTO trabajadores (cedula, nombre, apellido, cargo, \"contraseña\", id_pais, id_depart, id_ciudad) VALUES ($cedula, '$nombre', '$apellido', '$cargo', '$contrasena', $id_pais, $id_depart, $id_ciudad)";
    if (pg_query($conn, $sql)) {
        $mensaje = 'Usuario registrado correctamente.';
    } else {
        $mensaje = 'Error al registrar usuario.';
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    if (pg_query($conn, "DELETE FROM trabajadores WHERE id_trab = $id")) {
        $mensaje = 'Usuario eliminado correctamente.';
    } else {
        $mensaje = 'Error al eliminar usuario.';
    }
}

// Editar usuario
if (isset($_POST['editar'])) {
    $id = $_POST['id_trab'];
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cargo = $_POST['cargo'];
    $contrasena = $_POST['contrasena'];
    $id_pais = $_POST['id_pais'];
    $id_depart = $_POST['id_depart'];
    $id_ciudad = $_POST['id_ciudad'];
    $sql = "UPDATE trabajadores SET cedula=$cedula, nombre='$nombre', apellido='$apellido', cargo='$cargo', \"contraseña\"='$contrasena', id_pais=$id_pais, id_depart=$id_depart, id_ciudad=$id_ciudad WHERE id_trab=$id";
    if (pg_query($conn, $sql)) {
        $mensaje = 'Usuario actualizado correctamente.';
    } else {
        $mensaje = 'Error al actualizar usuario.';
    }
}

// Listar usuarios
$usuarios = pg_query($conn, "SELECT * FROM trabajadores ORDER BY id_trab DESC");

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
    <title>Módulo de Devoluciones</title>
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
                        <a class="btn btn-info nav-btn disabled" href="../trabajadores/usuarios.php" tabindex="-1" aria-disabled="true">Usuarios</a>
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
                        <a class="btn btn-outline-info nav-btn" href="../clientes/registro_clientes.php">Clientes</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="btn btn-outline-danger nav-btn" href="../logout.php">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="usuarios-container container py-4">
    <h2 class="usuarios-title">Gestión de Usuarios</h2>
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
    <div class="card usuarios-form-card mb-4">
        <div class="card-body">
            <form method="post" class="usuarios-form-row">
                <input type="hidden" name="id_trab" id="id_trab">
                <div class="fila-campos">
                    <div><input type="number" name="cedula" class="form-control" placeholder="Cédula" required></div>
                    <div><input type="text" name="nombre" class="form-control" placeholder="Nombre" required></div>
                    <div><input type="text" name="apellido" class="form-control" placeholder="Apellido" required></div>
                </div>
                <div class="fila-campos">
                    <div><input type="text" name="cargo" class="form-control" placeholder="Cargo" required></div>
                    <div style="position:relative;">
                        <input type="password" name="contrasena" id="reg_contrasena" class="form-control" placeholder="Contraseña" required>
                        <button type="button" class="btn btn-outline-secondary btn-sm password-toggle-btn" style="position:absolute;top:6px;right:6px;" onclick="toggleRegPassword()">
                            <span id="reg_eye_icon">
                                <!-- Ojo SVG -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/></svg>
                            </span>
                        </button>
                    </div>
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
    <div class="card usuarios-table-card">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Cargo</th>
                        <th>País</th>
                        <th>Depto</th>
                        <th>Ciudad</th>
                        <th>Contraseña</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($u = pg_fetch_assoc($usuarios)): ?>
                    <tr>
                        <td><?php echo $u['id_trab']; ?></td>
                        <td><?php echo $u['cedula']; ?></td>
                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($u['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($u['cargo']); ?></td>
                        <td><?php echo $u['id_pais']; ?></td>
                        <td><?php echo $u['id_depart']; ?></td>
                        <td><?php echo $u['id_ciudad']; ?></td>
                        <td class="password-cell">
                            <span class="password-mask" id="pw_mask_<?php echo $u['id_trab']; ?>">••••••••</span>
                            <span class="d-none" id="pw_real_<?php echo $u['id_trab']; ?>"><?php echo htmlspecialchars($u['contraseña']); ?></span>
                            <button type="button" class="btn btn-outline-secondary btn-sm password-toggle-btn" onclick="togglePassword(<?php echo $u['id_trab']; ?>)">
                                <span id="eye_icon_<?php echo $u['id_trab']; ?>">
                                    <!-- Ojo SVG -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                            </button>
                        </td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" onclick="editarUsuario(
                                '<?php echo $u['id_trab']; ?>',
                                '<?php echo $u['cedula']; ?>',
                                '<?php echo htmlspecialchars($u['nombre']); ?>',
                                '<?php echo htmlspecialchars($u['apellido']); ?>',
                                '<?php echo htmlspecialchars($u['cargo']); ?>',
                                '<?php echo htmlspecialchars($u['contraseña']); ?>',
                                '<?php echo $u['id_pais']; ?>',
                                '<?php echo $u['id_depart']; ?>',
                                '<?php echo $u['id_ciudad']; ?>'
                            )"><i class="bi bi-pencil-square"></i> Editar</button>
                            <a href="?eliminar=<?php echo $u['id_trab']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar usuario?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Modal para editar usuario -->
            <div id="modalEditarUsuario" class="modal">
                <div class="modal-content" style="max-width:600px;margin:auto;">
                    <span class="close" id="cerrarModalEditarUsuario">&times;</span>
                    <h3 class="mb-3">Editar Usuario</h3>
                    <form method="post" class="usuarios-form-row" id="formEditarUsuario">
                        <input type="hidden" name="id_trab" id="edit_id_trab">
                        <div class="fila-campos">
                            <div><input type="number" name="cedula" id="edit_cedula" class="form-control" placeholder="Cédula" required></div>
                            <div><input type="text" name="nombre" id="edit_nombre" class="form-control" placeholder="Nombre" required></div>
                            <div><input type="text" name="apellido" id="edit_apellido" class="form-control" placeholder="Apellido" required></div>
                        </div>
                        <div class="fila-campos">
                            <div><input type="text" name="cargo" id="edit_cargo" class="form-control" placeholder="Cargo" required></div>
                            <div style="position:relative;">
                                <input type="password" name="contrasena" id="edit_contrasena" class="form-control" placeholder="Contraseña" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm password-toggle-btn" style="position:absolute;top:6px;right:6px;" onclick="toggleEditPassword()">
                                    <span id="edit_eye_icon">
                                        <!-- Ojo SVG -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/></svg>
                                    </span>
                                </button>
                            </div>
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

// SVGs para ojo y ojo tachado
const svgEye = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/></svg>';
const svgEyeOff = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M1.5 12s4-7.5 10.5-7.5c2.1 0 4.1.5 5.9 1.4M22.5 12s-4 7.5-10.5 7.5c-2.1 0-4.1-.5-5.9-1.4"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.5 9.5l5 5"/></svg>';

// Mostrar/ocultar contraseña en tabla
function togglePassword(id) {
    var mask = document.getElementById('pw_mask_' + id);
    var real = document.getElementById('pw_real_' + id);
    var icon = document.getElementById('eye_icon_' + id);
    if (mask.classList.contains('d-none')) {
        mask.classList.remove('d-none');
        real.classList.add('d-none');
        if (icon) icon.innerHTML = svgEye;
    } else {
        mask.classList.add('d-none');
        real.classList.remove('d-none');
        if (icon) icon.innerHTML = svgEyeOff;
    }
}
// Mostrar/ocultar contraseña en modal editar
function toggleEditPassword() {
    var input = document.getElementById('edit_contrasena');
    var icon = document.getElementById('edit_eye_icon');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.innerHTML = svgEyeOff;
    } else {
        input.type = 'password';
        if (icon) icon.innerHTML = svgEye;
    }
}
// Modal editar usuario
var modalEditarUsuario = document.getElementById('modalEditarUsuario');
var cerrarModalEditarUsuario = document.getElementById('cerrarModalEditarUsuario');
cerrarModalEditarUsuario.onclick = function() { modalEditarUsuario.style.display = 'none'; }
window.onclick = function(event) {
    if (event.target == modalEditarUsuario) { modalEditarUsuario.style.display = 'none'; }
}
function editarUsuario(id, cedula, nombre, apellido, cargo, contrasena, id_pais, id_depart, id_ciudad) {
    document.getElementById('edit_id_trab').value = id;
    document.getElementById('edit_cedula').value = cedula;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_apellido').value = apellido;
    document.getElementById('edit_cargo').value = cargo;
    document.getElementById('edit_contrasena').value = contrasena;
    document.getElementById('edit_id_pais').value = id_pais;
    document.getElementById('edit_id_depart').value = id_depart;
    // Filtrar ciudades según departamento
    var selectCiudad = document.getElementById('edit_id_ciudad');
    for (var i = 0; i < selectCiudad.options.length; i++) {
        var opt = selectCiudad.options[i];
        opt.style.display = (opt.getAttribute('data-depart') == id_depart || opt.value == '') ? '' : 'none';
    }
    selectCiudad.value = id_ciudad;
    modalEditarUsuario.style.display = 'block';
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

// Mostrar/ocultar contraseña en registro
function toggleRegPassword() {
    var input = document.getElementById('reg_contrasena');
    var icon = document.getElementById('reg_eye_icon');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.innerHTML = svgEyeOff;
    } else {
        input.type = 'password';
        if (icon) icon.innerHTML = svgEye;
    }
}
</script>
</body>
</html>