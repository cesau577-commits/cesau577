
<?php
require_once __DIR__ . '/config.php';
auth();

$role = $_SESSION['rol'];
$uid = (int)$_SESSION['usuario_id'];

function countq($db, $sql) {
    $resultado = $db->query($sql);
    return (int)$resultado->fetch_row()[0];
}

$visitantes = countq(
    $conexion,
    "SELECT COUNT(*) FROM cv_visitantes"
);

$hoy = countq(
    $conexion,
    "SELECT COUNT(*) FROM cv_visitas WHERE fecha = CURDATE()"
);

$dentro = countq(
    $conexion,
    "SELECT COUNT(*) FROM cv_visitas WHERE estado = 'Dentro'"
);

$st = $conexion->prepare(
    "SELECT COUNT(*) FROM cv_notificaciones
     WHERE usuario_id = ? AND leida = 0"
);
$st->bind_param('i', $uid);
$st->execute();
$avisos = (int)$st->get_result()->fetch_row()[0];
$st->close();

if ($role === 'docente') {
    $st = $conexion->prepare(
        "SELECT v.id,
                t.nombre AS visitante,
                p.nombre AS destino,
                v.motivo,
                v.hora_entrada,
                v.estado
         FROM cv_visitas v
         INNER JOIN cv_visitantes t
            ON t.id = v.visitante_id
         INNER JOIN cv_personal p
            ON p.id = v.personal_id
         WHERE p.usuario_id = ?
         ORDER BY v.id DESC
         LIMIT 20"
    );
    $st->bind_param('i', $uid);
    $st->execute();
    $visitas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
} else {
    $visitas = $conexion->query(
        "SELECT v.id,
                t.nombre AS visitante,
                p.nombre AS destino,
                v.motivo,
                v.hora_entrada,
                v.estado
         FROM cv_visitas v
         INNER JOIN cv_visitantes t
            ON t.id = v.visitante_id
         LEFT JOIN cv_personal p
            ON p.id = v.personal_id
         ORDER BY v.id DESC
         LIMIT 20"
    )->fetch_all(MYSQLI_ASSOC);
}

header_ui('Panel principal');
?>

<div class="row g-3 mb-4">

    <?php
    $metricas = [
        ['Visitantes', $visitantes],
        ['Visitas hoy', $hoy],
        ['Personas dentro', $dentro],
        ['Avisos sin leer', $avisos]
    ];
    ?>

    <?php foreach ($metricas as $item): ?>
        <div class="col-6 col-xl-3">
            <div class="panel p-4">
                <div class="text-secondary">
                    <?= h($item[0]) ?>
                </div>
                <div class="metric">
                    <?= (int)$item[1] ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<div class="panel p-4 mb-4">

    <div class="d-flex justify-content-between
                align-items-center mb-3 flex-wrap gap-2">

        <h4 class="mb-0">Visitas recientes</h4>

        <div class="d-flex gap-2 flex-wrap">

            <?php if ($role !== 'docente'): ?>

                <a href="registrar_visita.php"
                   class="btn btn-primary">
                    + Nueva visita
                </a>

                <a href="registrar_salida.php"
                   class="btn btn-outline-primary">
                    Registrar salida
                </a>

            <?php endif; ?>

            <?php if ($role === 'administrador'): ?>

                <a href="reportes.php"
                   class="btn btn-outline-primary">
                    Ver reportes
                </a>

            <?php endif; ?>

        </div>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead>
                <tr>
                    <th>Visitante</th>
                    <th>Destino</th>
                    <th>Motivo</th>
                    <th>Entrada</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($visitas as $r): ?>

                    <tr>
                        <td><?= h($r['visitante']) ?></td>
                        <td><?= h($r['destino'] ?? 'Sin asignar') ?></td>
                        <td><?= h($r['motivo']) ?></td>
                        <td><?= h($r['hora_entrada']) ?></td>

                        <td>
                            <?php if ($r['estado'] === 'Dentro'): ?>
                                <span class="badge bg-primary">
                                    Dentro
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    <?= h($r['estado']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (!$visitas): ?>
                    <tr>
                        <td colspan="5"
                            class="text-center text-secondary">
                            No hay visitas registradas.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>

    </div>

</div>

<?php footer_ui(); ?>
