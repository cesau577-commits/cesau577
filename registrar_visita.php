
<?php
require_once __DIR__ . '/config.php';

auth();

if ($_SESSION['rol'] === 'docente') {
    http_response_code(403);
    exit('Acceso restringido.');
}

require_once __DIR__ . '/enviar_correo.php';

$error = '';
$datos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkcsrf();

    $datos = $_POST;

    $nombre = trim($_POST['nombre'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $institucion = trim($_POST['institucion'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $pid = postint('personal_id');
    $uid = (int)$_SESSION['usuario_id'];

    if ($nombre === '' || $motivo === '' || $pid <= 0) {
        $error = 'Completa todos los campos obligatorios.';
    } elseif (
        mb_strlen($nombre) > 120 ||
        mb_strlen($motivo) > 255 ||
        mb_strlen($documento) > 30 ||
        mb_strlen($telefono) > 20 ||
        mb_strlen($institucion) > 120
    ) {
        $error = 'Uno de los campos supera el límite permitido.';
    } else {
        $guardado = false;
        $transaccion = false;
        $visita = 0;
        $estadoCorreo = 'Sin correo';
        $dest = null;

        try {
            // Comprobar el destino
            $st = $conexion->prepare(
                "SELECT id,nombre,correo,usuario_id
                 FROM cv_personal
                 WHERE id = ? AND estado = 1"
            );
            $st->bind_param('i', $pid);
            $st->execute();
            $dest = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$dest) {
                throw new Exception('El destino no está disponible.');
            }

            $conexion->begin_transaction();
            $transaccion = true;

            // Buscar visitante por documento
            $vid = 0;

            if ($documento !== '') {
                $st = $conexion->prepare(
                    "SELECT id FROM cv_visitantes
                     WHERE documento = ? LIMIT 1"
                );
                $st->bind_param('s', $documento);
                $st->execute();
                $anterior = $st->get_result()->fetch_assoc();
                $st->close();

                if ($anterior) {
                    $vid = (int)$anterior['id'];
                }
            }

            if ($vid > 0) {
                $st = $conexion->prepare(
                    "UPDATE cv_visitantes
                     SET nombre = ?, telefono = ?,
                         institucion = ?
                     WHERE id = ?"
                );
                $st->bind_param(
                    'sssi',
                    $nombre, $telefono, $institucion, $vid
                );
                $st->execute();
                $st->close();
            } else {
                $doc = $documento !== '' ? $documento : null;

                $st = $conexion->prepare(
                    "INSERT INTO cv_visitantes
                     (documento,nombre,telefono,institucion)
                     VALUES (?,?,?,?)"
                );
                $st->bind_param(
                    'ssss',
                    $doc, $nombre, $telefono, $institucion
                );
                $st->execute();
                $vid = (int)$conexion->insert_id;
                $st->close();
            }

            $estadoCorreo = filter_var(
                $dest['correo'] ?? '',
                FILTER_VALIDATE_EMAIL
            ) ? 'Pendiente' : 'Sin correo';

            // Registrar entrada
            $st = $conexion->prepare(
                "INSERT INTO cv_visitas
                 (visitante_id,personal_id,motivo,
                  fecha,hora_entrada,estado,
                  registrado_por,notificacion_estado)
                 VALUES
                 (?,?,?,CURDATE(),NOW(),'Dentro',?,?)"
            );

            $st->bind_param(
                'iisis',
                $vid, $pid, $motivo, $uid, $estadoCorreo
            );
            $st->execute();
            $visita = (int)$conexion->insert_id;
            $st->close();

            // Crear notificación interna
            if (!empty($dest['usuario_id'])) {
                $receptor = (int)$dest['usuario_id'];

                $mensaje = 'Nueva visita de '.$nombre.
                    '. Motivo: '.$motivo;

                $st = $conexion->prepare(
                    "INSERT INTO cv_notificaciones
                     (mensaje,fecha,leida,usuario_id,visita_id)
                     VALUES (?,NOW(),0,?,?)"
                );
                $st->bind_param(
                    'sii',
                    $mensaje, $receptor, $visita
                );
                $st->execute();
                $st->close();
            }

            // Preparar historial del correo
            if ($estadoCorreo === 'Pendiente') {
                $correo = $dest['correo'];

                $st = $conexion->prepare(
                    "INSERT INTO cv_correos
                     (visita_id,destinatario)
                     VALUES (?,?)"
                );
                $st->bind_param('is', $visita, $correo);
                $st->execute();
                $st->close();
            }

            $conexion->commit();
            $transaccion = false;
            $guardado = true;

        } catch (Throwable $e) {
            if ($transaccion) {
                try {
                    $conexion->rollback();
                } catch (Throwable $ignore) {}
            }

            error_log('INAC registro: '.$e->getMessage());

            $error = 'No se pudo registrar la visita. '
                   . 'Comprueba los datos e inténtalo nuevamente.';
        }

        // El correo se intenta únicamente si la visita
        // ya quedó guardada en la base de datos.
        if ($guardado) {
            $ok = false;

            if ($estadoCorreo === 'Pendiente') {
                try {
                    $ok = enviarCorreoVisita(
                        $dest['correo'],
                        $dest['nombre'],
                        $nombre,
                        $motivo,
                        date('Y-m-d H:i:s')
                    );
                } catch (Throwable $e) {
                    error_log('INAC correo: '.$e->getMessage());
                }

                $estadoFinal = $ok ? 'Enviada' : 'Error';

                try {
                    $st = $conexion->prepare(
                        "UPDATE cv_visitas
                         SET notificacion_estado = ?
                         WHERE id = ?"
                    );
                    $st->bind_param(
                        'si', $estadoFinal, $visita
                    );
                    $st->execute();
                    $st->close();

                    $st = $conexion->prepare(
                        "UPDATE cv_correos
                         SET estado = ?,
                             intentos = intentos + 1,
                             enviado_en = IF(
                                 ? = 'Enviada', NOW(), NULL
                             ),
                             ultimo_error = IF(
                                 ? = 'Error',
                                 'Revisar SMTP o proveedor',
                                 NULL
                             )
                         WHERE visita_id = ?"
                    );
                    $st->bind_param(
                        'sssi',
                        $estadoFinal,
                        $estadoFinal,
                        $estadoFinal,
                        $visita
                    );
                    $st->execute();
                    $st->close();

                } catch (Throwable $e) {
                    error_log(
                        'INAC estado correo: '.$e->getMessage()
                    );
                }
            }

            if ($estadoCorreo === 'Sin correo') {
                $mensajeFinal = 'Destino sin correo configurado.';
            } elseif ($ok) {
                $mensajeFinal = 'Correo aceptado por el servidor SMTP.';
            } else {
                $mensajeFinal = 'Correo no enviado. Revisar SMTP.';
            }

            flash(
                'Visita #'.$visita.' registrada correctamente. '.
                $mensajeFinal
            );

            go('index.php');
        }
    }
}

$personal = $conexion->query(
    "SELECT id,nombre,cargo,departamento
     FROM cv_personal
     WHERE estado = 1
     ORDER BY nombre"
)->fetch_all(MYSQLI_ASSOC);

header_ui('Registrar visita');
?>

<div class="panel p-4" style="max-width:950px">

    <h4 class="mb-2">Registrar nueva visita</h4>

    <p class="text-secondary mb-4">
        Completa los datos del visitante y selecciona
        a quién se dirige. Al validar la entrada,
        INAC generará la notificación correspondiente.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf"
               value="<?= h(csrf()) ?>">

        <h5 class="mb-3">Datos del visitante</h5>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">
                    Nombre completo *
                </label>
                <input class="form-control"
                       name="nombre"
                       maxlength="120"
                       value="<?= h($datos['nombre'] ?? '') ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Documento de identidad
                </label>
                <input class="form-control"
                       name="documento"
                       maxlength="30"
                       value="<?= h($datos['documento'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input class="form-control"
                       name="telefono"
                       maxlength="20"
                       value="<?= h($datos['telefono'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">
                    Institución de procedencia
                </label>
                <input class="form-control"
                       name="institucion"
                       maxlength="120"
                       value="<?= h($datos['institucion'] ?? '') ?>">
            </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-3">Información de la visita</h5>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">
                    Docente o departamento *
                </label>

                <select class="form-select"
                        name="personal_id" required>
                    <option value="">
                        Selecciona un destino
                    </option>

                    <?php foreach ($personal as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"
                            <?= (int)($datos['personal_id'] ?? 0)
                                === (int)$p['id']
                                ? 'selected' : '' ?>>
                            <?= h(
                                $p['nombre'].' — '.$p['cargo'].
                                ' / '.$p['departamento']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">
                    Motivo de visita *
                </label>
                <textarea class="form-control"
                          name="motivo"
                          maxlength="255"
                          rows="4"
                          placeholder="Explica el motivo de la visita..."
                          required><?= h($datos['motivo'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <div class="alert alert-info">
                    Al validar la entrada, se registrará
                    la visita y se generará un aviso
                    para la persona seleccionada.
                </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit"
                        class="btn btn-primary">
                    Validar entrada y notificar
                </button>

                <a href="index.php"
                   class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

<?php footer_ui(); ?>
