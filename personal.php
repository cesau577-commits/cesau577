
<?php
require_once __DIR__ . '/config.php';
admin();

$error = '';
$datos = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkcsrf();

    $accion = $_POST['accion'] ?? '';
    $id = postint('id');

    try {
        if ($accion === 'guardar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            $depto = trim($_POST['departamento'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $usuario = postint('usuario_id');
            $uid = $usuario ?: null;

            if ($nombre === '' || $cargo === '') {
                throw new Exception(
                    'Nombre y cargo son obligatorios.'
                );
            }

            if (
                mb_strlen($nombre) > 120 ||
                mb_strlen($cargo) > 80 ||
                mb_strlen($depto) > 100 ||
                strlen($correo) > 190
            ) {
                throw new Exception(
                    'Uno de los campos es demasiado largo.'
                );
            }

            if (
                $correo !== '' &&
                !filter_var($correo, FILTER_VALIDATE_EMAIL)
            ) {
                throw new Exception(
                    'El correo electrónico no es válido.'
                );
            }

            if ($uid !== null) {
                $st = $conexion->prepare(
                    "SELECT id FROM cv_usuarios
                     WHERE id = ? AND rol = 'docente'
                     AND estado = 1"
                );
                $st->bind_param('i', $uid);
                $st->execute();

                if (!$st->get_result()->fetch_assoc()) {
                    throw new Exception(
                        'La cuenta docente no es válida.'
                    );
                }
                $st->close();
            }

            if ($id > 0) {
                $st = $conexion->prepare(
                    "UPDATE cv_personal
                     SET nombre = ?, cargo = ?,
                         departamento = ?, correo = ?,
                         usuario_id = ?
                     WHERE id = ?"
                );
                $st->bind_param(
                    'ssssii',
                    $nombre, $cargo, $depto,
                    $correo, $uid, $id
                );
                $st->execute();
                $st->close();

                flash('Personal actualizado correctamente.');
            } else {
                $st = $conexion->prepare(
                    "INSERT INTO cv_personal
                     (nombre,cargo,departamento,correo,usuario_id)
                     VALUES (?,?,?,?,?)"
                );
                $st->bind_param(
                    'ssssi',
                    $nombre, $cargo, $depto,
                    $correo, $uid
                );
                $st->execute();
                $st->close();

                flash('Personal agregado correctamente.');
            }

            go('personal.php');
        }

        if ($accion === 'estado') {
            if ($id <= 0) {
                throw new Exception('Registro inválido.');
            }

            $st = $conexion->prepare(
                "UPDATE cv_personal
                 SET estado = IF(estado = 1, 0, 1)
                 WHERE id = ?"
            );
            $st->bind_param('i', $id);
            $st->execute();
            $st->close();

            flash('Estado actualizado correctamente.');
            go('personal.php');
        }

        throw new Exception('Acción no válida.');

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$personal = $conexion->query(
    "SELECT p.id, p.nombre, p.cargo,
            p.departamento, p.correo,
            p.usuario_id, p.estado,
            u.usuario
     FROM cv_personal p
     LEFT JOIN cv_usuarios u
        ON u.id = p.usuario_id
     ORDER BY p.id DESC"
)->fetch_all(MYSQLI_ASSOC);

$usuarios = $conexion->query(
    "SELECT id, usuario, nombre
     FROM cv_usuarios
     WHERE rol = 'docente' AND estado = 1
     ORDER BY nombre"
)->fetch_all(MYSQLI_ASSOC);

$editar = null;

if (isset($_GET['editar'])) {
    $editarId = (int)$_GET['editar'];

    foreach ($personal as $p) {
        if ((int)$p['id'] === $editarId) {
            $editar = $p;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'guardar'
    && $error !== '') {
    $editar = [
        'id' => (int)($datos['id'] ?? 0),
        'nombre' => $datos['nombre'] ?? '',
        'cargo' => $datos['cargo'] ?? '',
        'departamento' => $datos['departamento'] ?? '',
        'correo' => $datos['correo'] ?? '',
        'usuario_id' => $datos['usuario_id'] ?? 0
    ];
}

header_ui('Docentes y departamentos');
?>

<div class="panel p-4 mb-4">
    <h4 class="mb-2">
        <?= $editar ? 'Editar personal' : 'Agregar docente o destino' ?>
    </h4>

    <p class="text-secondary">
        Registra a las personas o departamentos que
        pueden recibir visitas en INAC.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf"
               value="<?= h(csrf()) ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id"
               value="<?= (int)($editar['id'] ?? 0) ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">
                    Nombre completo o destino *
                </label>
                <input class="form-control"
                       name="nombre"
                       maxlength="120"
                       value="<?= h($editar['nombre'] ?? '') ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Cargo o tipo *</label>
                <select class="form-select" name="cargo" required>
                    <?php
                    $cargos = [
                        'Docente',
                        'Director',
                        'Subdirector',
                        'Administrativo',
                        'Departamento',
                        'Otro'
                    ];
                    $cargoActual = $editar['cargo'] ?? '';
                    if ($cargoActual !== '' &&
                        !in_array($cargoActual, $cargos, true)) {
                        $cargos[] = $cargoActual;
                    }
                    ?>
                    <option value="">Seleccionar</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?= h($cargo) ?>"
                            <?= $cargoActual === $cargo
                                ? 'selected' : '' ?>>
                            <?= h($cargo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Departamento</label>
                <input class="form-control"
                       name="departamento"
                       maxlength="100"
                       placeholder="Ej. Desarrollo de Software"
                       value="<?= h($editar['departamento'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Correo institucional
                </label>
                <input type="email"
                       class="form-control"
                       name="correo"
                       maxlength="190"
                       placeholder="docente@institucion.edu"
                       value="<?= h($editar['correo'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label class="form-label">
                    Cuenta vinculada para notificaciones
                </label>
                <select class="form-select" name="usuario_id">
                    <option value="0">Sin cuenta vinculada</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"
                            <?= (int)($editar['usuario_id'] ?? 0)
                                === (int)$u['id']
                                ? 'selected' : '' ?>>
                            <?= h($u['nombre'].' ('.$u['usuario'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-secondary">
                    Vincula la cuenta para que el docente
                    reciba avisos dentro de INAC.
                </small>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary">
                    <?= $editar ? 'Guardar cambios' : '+ Agregar personal' ?>
                </button>

                <?php if ($editar): ?>
                    <a href="personal.php"
                       class="btn btn-outline-secondary">
                        Cancelar edición
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="panel p-4">
    <h4 class="mb-3">Personal registrado</h4>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th>Correo</th>
                    <th>Cuenta</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personal as $p): ?>
                    <tr>
                        <td><?= h($p['nombre']) ?></td>
                        <td><?= h($p['cargo']) ?></td>
                        <td><?= h($p['departamento'] ?? '') ?></td>
                        <td><?= h($p['correo'] ?? '') ?></td>
                        <td><?= h($p['usuario'] ?? 'Sin cuenta') ?></td>
                        <td>
                            <span class="badge <?= $p['estado']
                                ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $p['estado']
                                    ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="personal.php?editar=<?= (int)$p['id'] ?>">
                                    Editar
                                </a>

                                <form method="POST">
                                    <input type="hidden" name="csrf"
                                           value="<?= h(csrf()) ?>">
                                    <input type="hidden" name="accion"
                                           value="estado">
                                    <input type="hidden" name="id"
                                           value="<?= (int)$p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary">
                                        <?= $p['estado']
                                            ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$personal): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary">
                            No hay personal registrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php footer_ui(); ?>
