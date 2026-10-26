
<?php
require_once __DIR__ . '/config.php';

auth();

$uid = (int)$_SESSION['usuario_id'];
$esAdmin = ($_SESSION['rol'] === 'administrador');

// MARCAR NOTIFICACIÓN COMO LEÍDA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkcsrf();

    $id = postint('id');

    if ($id > 0) {
        $st = $conexion->prepare(
            "UPDATE cv_notificaciones
             SET leida = 1
             WHERE id = ? AND usuario_id = ?"
        );

        $st->bind_param('ii', $id, $uid);
        $st->execute();
        $st->close();

        flash('Notificación marcada como leída.');
    }

    go('notificaciones.php');
}

// OBTENER NOTIFICACIONES DEL USUARIO
$st = $conexion->prepare(
    "SELECT id, mensaje, fecha, leida
     FROM cv_notificaciones
     WHERE usuario_id = ?
     ORDER BY id DESC
     LIMIT 100"
);

$st->bind_param('i', $uid);
$st->execute();

$notificaciones = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$pendientes = 0;

foreach ($notificaciones as $n) {
    if (!$n['leida']) {
        $pendientes++;
    }
}

header_ui('Mis notificaciones');
?>

<div class="panel p-4 mb-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Mis notificaciones</h4>
            <p class="text-secondary mb-0">
                Aquí encontrarás los avisos de visitas recibidas.
            </p>
        </div>

        <span class="badge bg-primary">
            <?= $pendientes ?> sin leer
        </span>
    </div>

    <?php if (empty($notificaciones)): ?>

        <div class="alert alert-info">
            No tienes notificaciones por el momento.
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($notificaciones as $n): ?>

                        <tr>
                            <td>
                                <?= h($n['mensaje']) ?>
                            </td>

                            <td>
                                <?= h($n['fecha']) ?>
                            </td>

                            <td>
                                <?php if ($n['leida']): ?>
                                    <span class="badge bg-success">
                                        Leída
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        Nueva
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!$n['leida']): ?>

                                    <form method="POST">
                                        <input type="hidden"
                                               name="csrf"
                                               value="<?= h(csrf()) ?>">

                                        <input type="hidden"
                                               name="id"
                                               value="<?= (int)$n['id'] ?>">

                                        <button class="btn btn-sm btn-outline-primary">
                                            Marcar como leída
                                        </button>
                                    </form>

                                <?php else: ?>
                                    <span class="text-secondary">
                                        Revisada
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

<?php if ($esAdmin): ?>

<?php
$correos = $conexion->query(
    "SELECT c.id, c.destinatario, c.estado,
            c.intentos, c.visita_id
     FROM cv_correos c
     ORDER BY c.id DESC
     LIMIT 100"
)->fetch_all(MYSQLI_ASSOC);
?>

<div class="panel p-4">

    <h4 class="mb-3">Historial de correos enviados</h4>

    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead>
                <tr>
                    <th>Visita</th>
                    <th>Destinatario</th>
                    <th>Estado</th>
                    <th>Intentos</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($correos as $c): ?>

                    <tr>
                        <td>#<?= (int)$c['visita_id'] ?></td>

                        <td><?= h($c['destinatario']) ?></td>

                        <td>
                            <?php if ($c['estado'] === 'Enviada'): ?>

                                <span class="badge bg-success">
                                    Aceptada por SMTP
                                </span>

                            <?php elseif ($c['estado'] === 'Error'): ?>

                                <span class="badge bg-danger">
                                    Error
                                </span>

                            <?php else: ?>

                                <span class="badge bg-warning text-dark">
                                    Pendiente
                                </span>

                            <?php endif; ?>
                        </td>

                        <td><?= (int)$c['intentos'] ?></td>
                    </tr>

                <?php endforeach; ?>

                <?php if (!$correos): ?>
                    <tr>
                        <td colspan="4" class="text-center text-secondary">
                            No hay correos registrados.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>

    </div>
</div>

<?php endif; ?>

<?php footer_ui(); ?>
