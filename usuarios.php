
<?php
require_once __DIR__ . '/config.php';

admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    checkcsrf();

    $accion = $_POST['accion'] ?? '';

    // CREAR NUEVO USUARIO
    if ($accion === 'crear') {

        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        $rol = $_POST['rol'] ?? '';

        if ($nombre === '') {
            $error = 'Debes escribir el nombre completo.';

        } elseif ($usuario === '') {
            $error = 'Debes escribir el nombre de usuario.';

        } elseif (strlen($pass) < 4) {
            $error = 'La contraseña debe tener mínimo 4 caracteres.';

        } elseif (strlen($nombre) > 100 ||
                  strlen($usuario) > 50) {
            $error = 'El nombre o usuario es demasiado largo.';

        } elseif (!in_array($rol, [
            'administrador',
            'recepcionista',
            'docente'
        ], true)) {
            $error = 'Selecciona un rol válido.';

        } else {

            try {

                $hash = password_hash(
                    $pass,
                    PASSWORD_DEFAULT
                );

                $stmt = $conexion->prepare(
                    "INSERT INTO cv_usuarios
                    (nombre, usuario, password, rol, estado)
                    VALUES (?, ?, ?, ?, 1)"
                );

                $stmt->bind_param(
                    'ssss',
                    $nombre,
                    $usuario,
                    $hash,
                    $rol
                );

                $stmt->execute();
                $stmt->close();

                flash('Usuario creado correctamente.');
                go('usuarios.php');

            } catch (Throwable $e) {
                $error = 'No se pudo crear el usuario. Es posible que ya exista.';
            }
        }
    }

    // CAMBIAR CONTRASEÑA
    elseif ($accion === 'password') {

        $id = (int)($_POST['id'] ?? 0);
        $nueva = (string)($_POST['nueva_password'] ?? '');

        if ($id <= 0) {
            $error = 'Usuario inválido.';

        } elseif (strlen($nueva) < 4) {
            $error = 'La contraseña debe tener mínimo 4 caracteres.';

        } else {

            try {

                $hash = password_hash(
                    $nueva,
                    PASSWORD_DEFAULT
                );

                $stmt = $conexion->prepare(
                    "UPDATE cv_usuarios
                     SET password = ?
                     WHERE id = ?"
                );

                $stmt->bind_param('si', $hash, $id);
                $stmt->execute();
                $stmt->close();

                flash('Contraseña actualizada correctamente.');
                go('usuarios.php');

            } catch (Throwable $e) {
                $error = 'No se pudo actualizar la contraseña.';
            }
        }
    }

    // ACTIVAR O DESACTIVAR USUARIO
    elseif ($accion === 'estado') {

        $id = (int)($_POST['id'] ?? 0);

        if ($id === (int)$_SESSION['usuario_id']) {
            $error = 'No puedes desactivar tu propia cuenta.';

        } elseif ($id <= 0) {
            $error = 'Usuario inválido.';

        } else {

            try {

                $stmt = $conexion->prepare(
                    "UPDATE cv_usuarios
                     SET estado = IF(estado = 1, 0, 1)
                     WHERE id = ?"
                );

                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                flash('Estado del usuario actualizado.');
                go('usuarios.php');

            } catch (Throwable $e) {
                $error = 'No se pudo actualizar el usuario.';
            }
        }
    }
}

// CONSULTAR USUARIOS
$rows = $conexion->query(
    "SELECT id, nombre, usuario, rol, estado
     FROM cv_usuarios
     ORDER BY id DESC"
);

$usuarios = $rows->fetch_all(MYSQLI_ASSOC);

header_ui('Administrar usuarios');
?>

<!-- CREAR USUARIO -->
<div class="panel p-4 mb-4">

    <h4 class="mb-3">Crear nuevo usuario</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden"
               name="csrf"
               value="<?= h(csrf()) ?>">

        <input type="hidden"
               name="accion"
               value="crear">

        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label">
                    Nombre completo
                </label>

                <input type="text"
                       name="nombre"
                       class="form-control"
                       maxlength="100"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Nombre de usuario
                </label>

                <input type="text"
                       name="usuario"
                       class="form-control"
                       maxlength="50"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Contraseña
                </label>

                <input type="password"
                       name="password"
                       class="form-control"
                       minlength="4"
                       autocomplete="new-password"
                       required>

                <small class="text-muted">
                    Mínimo 4 caracteres.
                </small>
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Rol del usuario
                </label>

                <select name="rol" class="form-select">

                    <option value="recepcionista">
                        Recepcionista
                    </option>

                    <option value="docente">
                        Docente
                    </option>

                    <option value="administrador">
                        Administrador
                    </option>

                </select>
            </div>

            <div class="col-12">
                <button type="submit"
                        class="btn btn-primary">
                    + Crear usuario
                </button>
            </div>

        </div>
    </form>
</div>

<!-- LISTADO DE USUARIOS -->
<div class="panel p-4">

    <h4 class="mb-3">Usuarios registrados</h4>

    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($usuarios as $r): ?>

                <tr>

                    <td><?= h($r['nombre']) ?></td>

                    <td><?= h($r['usuario']) ?></td>

                    <td><?= h(ucfirst($r['rol'])) ?></td>

                    <td>
                        <?php if ($r['estado']): ?>

                            <span class="badge bg-success">
                                Activo
                            </span>

                        <?php else: ?>

                            <span class="badge bg-danger">
                                Inactivo
                            </span>

                        <?php endif; ?>
                    </td>

                    <td>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#pass<?= (int)$r['id'] ?>">
                            Cambiar contraseña
                        </button>

                        <?php if ((int)$r['id'] !==
                                  (int)$_SESSION['usuario_id']): ?>

                            <form method="POST"
                                  class="d-inline">

                                <input type="hidden"
                                       name="csrf"
                                       value="<?= h(csrf()) ?>">

                                <input type="hidden"
                                       name="accion"
                                       value="estado">

                                <input type="hidden"
                                       name="id"
                                       value="<?= (int)$r['id'] ?>">

                                <button type="submit"
                                        class="btn btn-sm btn-outline-secondary">
                                    <?= $r['estado']
                                        ? 'Desactivar'
                                        : 'Activar' ?>
                                </button>

                            </form>

                        <?php endif; ?>

                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

<!-- VENTANAS PARA CAMBIAR CONTRASEÑA -->
<?php foreach ($usuarios as $r): ?>

<div class="modal fade"
     id="pass<?= (int)$r['id'] ?>"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Cambiar contraseña de
                        <?= h($r['usuario']) ?>
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <input type="hidden"
                           name="csrf"
                           value="<?= h(csrf()) ?>">

                    <input type="hidden"
                           name="accion"
                           value="password">

                    <input type="hidden"
                           name="id"
                           value="<?= (int)$r['id'] ?>">

                    <label class="form-label">
                        Nueva contraseña
                    </label>

                    <input type="password"
                           name="nueva_password"
                           class="form-control"
                           minlength="4"
                           autocomplete="new-password"
                           required>

                    <small class="text-muted">
                        Mínimo 4 caracteres.
                    </small>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="btn btn-primary">
                        Guardar contraseña
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<?php endforeach; ?>

<?php footer_ui(); ?>
