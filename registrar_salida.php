
<?php
require_once __DIR__ . '/config.php';

auth();

// Solo administrador y recepcionista
if (!in_array(
    $_SESSION['rol'],
    ['administrador', 'recepcionista'],
    true
)) {
    http_response_code(403);
    exit('No tienes permiso para registrar salidas.');
}

$error = '';

// REGISTRAR SALIDA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    checkcsrf();

    $id = postint('id');

    if ($id <= 0) {
        $error = 'Selecciona una visita válida.';
    } else {

        try {

            // Actualizar solo visitas que siguen dentro
            $st = $conexion->prepare(
                "UPDATE cv_visitas
                 SET estado = 'Finalizada',
                     hora_salida = NOW()
                 WHERE id = ?
                 AND estado = 'Dentro'"
            );

            $st->bind_param('i', $id);
            $st->execute();

            $actualizadas = $st->affected_rows;
            $st->close();

            if ($actualizadas === 1) {
                flash('Salida registrada correctamente.');
                go('registrar_salida.php');
            } else {
                $error = 'La visita ya fue finalizada o no existe.';
            }

        } catch (Throwable $e) {
            error_log('INAC salida: ' . $e->getMessage());

            $error = 'No se pudo registrar la salida. '
                   . 'Revisa la estructura de cv_visitas.';
        }
    }
}

// CONSULTAR PERSONAS DENTRO
$visitas = $conexion->query(
    "SELECT
        v.id,
        vi.nombre AS visitante,
        vi.documento,
        p.nombre AS destino,
        v.motivo,
        v.fecha,
        v.hora_entrada
     FROM cv_visitas v
     INNER JOIN cv_visitantes vi
        ON vi.id = v.visitante_id
     INNER JOIN cv_personal p
        ON p.id = v.personal_id
     WHERE v.estado = 'Dentro'
     ORDER BY v.hora_entrada DESC, v.id DESC"
)->fetch_all(MYSQLI_ASSOC);

$total = count($visitas);

header_ui('Registrar salida');
?>

<div class="panel p-4 mb-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h4 class="mb-1">Registrar salida de visitantes</h4>
            <p class="text-secondary mb-0">
                Selecciona al visitante que está saliendo del instituto.
            </p>
        </div>

        <span class="badge bg-primary">
            <?= $total ?> dentro
        </span>

    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$visitas): ?>

        <div class="alert alert-success">
            No hay visitantes pendientes de salida.
        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>Visita</th>
                        <th>Visitante</th>
                        <th>Documento</th>
                        <th>Destino</th>
                        <th>Motivo</th>
                        <th>Entrada</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($visitas as $v): ?>

                        <tr>

                            <td>#<?= (int)$v['id'] ?></td>

                            <td><?= h($v['visitante']) ?></td>

                            <td>
                                <?= h($v['documento'] ?: 'Sin documento') ?>
                            </td>

                            <td><?= h($v['destino']) ?></td>

                            <td><?= h($v['motivo']) ?></td>

                            <td><?= h($v['hora_entrada']) ?></td>

                            <td>
                                <form method="POST"
                                      onsubmit="return confirm('¿Confirmar la salida de este visitante?');">

                                    <input type="hidden"
                                           name="csrf"
                                           value="<?= h(csrf()) ?>">

                                    <input type="hidden"
                                           name="id"
                                           value="<?= (int)$v['id'] ?>">

                                    <button type="submit"
                                            class="btn btn-sm btn-primary">
                                        Registrar salida
                                    </button>

                                </form>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>

<?php footer_ui(); ?>
