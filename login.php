<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../facturacion_facilito/style.css"> <!-- 👈 tu archivo CSS separado -->
</head>
<body>
    <div class="login-card">
        <h2>Iniciar Sesión</h2>
        <form action="../facturacion_facilito/validar_login.php" method="POST">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="clave" name="clave" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === '1') { ?>
    <div id="modalLoginError" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:350px;margin:auto;">
            <span class="close" id="cerrarModalLoginError">&times;</span>
            <div>Usuario o contraseña incorrectos.</div>
        </div>
    </div>
    <script>
    window.onload = function() {
        var modal = document.getElementById('modalLoginError');
        var closeBtn = document.getElementById('cerrarModalLoginError');
        if (modal) {
            modal.style.display = 'block';
            closeBtn.onclick = function() { modal.style.display = 'none'; window.history.replaceState(null, '', window.location.pathname); };
            window.onclick = function(event) { if (event.target == modal) { modal.style.display = 'none'; window.history.replaceState(null, '', window.location.pathname); } }
        }
    }
    </script>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
