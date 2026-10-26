
<?php
// ========================================
// INAC - REPORTES DE VISITAS
// ========================================

require_once __DIR__ . '/config.php';

// Solo administradores
admin();

// OBTENER FILTROS
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
$buscar = trim($_GET['buscar'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$exportar = ($_GET['exportar'] ?? '') === 'csv';

$error = '';

// VALIDAR FECHAS
function fechaValida($fecha) {
    if ($fecha === '') {
        return true;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $fecha);

    return $d && $d->format('Y-m-d') === $fecha;
}

if (!fechaValida($desde) || !fechaValida($hasta)) {
    $error = 'Las fechas no son válidas.';
}

if (
    $desde !== '' &&
    $hasta !== '' &&
    $desde > $hasta
) {
    $error = 'La fecha inicial no puede ser mayor que la final.';
}

if (!in_array($estado, ['', 'Dentro', 'Finalizada'], true)) {
    $error = 'El estado seleccionado no es válido.';
}

if (mb_strlen($buscar) > 120) {
    $error = 'El texto de búsqueda es demasiado largo.';
}

// CONSULTA PRINCIPAL
$sql = "
    SELECT
        v.id,
        t.nombre AS visitante,
        t.documento,
        t.telefono,
        p.nombre AS destino,
        p.departamento,
        v.motivo,
        v.fecha,
        v.hora_entrada,
        v.hora_salida,
        v.estado,
        v.notificacion_estado
    FROM cv_visitas v
    INNER JOIN cv_visitantes t
        ON t.id = v.visitante_id
    LEFT JOIN cv_personal p
        ON p.id = v.personal_id
    WHERE 1 = 1
";

$parametros = [];
$tipos = '';

if ($desde !== '') {
    $sql .= " AND v.fecha >= ?";
    $parametros[] = $desde;
    $tipos .= 's';
}

if ($hasta !== '') {
    $sql .= " AND v.fecha <= ?";
    $parametros[] = $hasta;
    $tipos .= 's';
}

if ($buscar !== '') {
    $sql .= "
        AND (
            t.nombre LIKE ?
            OR p.nombre LIKE ?
            OR p.departamento LIKE ?
        )
    ";

    $texto = '%' . $buscar . '%';

    $parametros[] = $texto;
    $parametros[] = $texto;
    $parametros[] = $texto;

    $tipos .= 'sss';
}

if ($estado !== '') {
    $sql .= " AND v.estado = ?";
    $parametros[] = $estado;
    $tipos .= 's';
}

$sql .= " ORDER BY v.id DESC";

// CONSULTAR RESULTADOS
$visitas = [];

if ($error === '') {
    $st = $conexion->prepare($sql);

    if ($parametros) {
        $st->bind_param($tipos, ...$parametros);
    }

    $st->execute();

    $visitas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}

// RESUMEN
$total = count($visitas);
$dentro = 0;
$finalizadas = 0;

foreach ($visitas as $v) {
    if ($v['estado'] === 'Dentro') {
        $dentro++;
    }

    if ($v['estado'] === 'Finalizada') {
        $finalizadas++;
    }
}

// EXPORTAR A CSV
if ($exportar && $error === '') {

    // Evitar que Excel interprete contenido
    // introducido por usuarios como fórmulas.
    function seguroCSV($valor) {
        $valor = (string)($valor ?? '');

        if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $valor)) {
            $valor = "'" . $valor;
        }

        return $valor;
    }

    $nombreArchivo =
        'INAC_Reporte_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header(
        'Content-Disposition: attachment; filename="' .
        $nombreArchivo . '"'
    );
    header('Cache-Control: no-store');

    $salida = fopen('php://output', 'w');

    // BOM para que Excel reconozca acentos
    fwrite($salida, "\xEF\xBB\xBF");

    // Separador compatible con Excel en español
    fwrite($salida, "sep=;\r\n");

    fputcsv($salida, [
        'ID',
        'Visitante',
        'Documento',
        'Teléfono',
        'Destino',
        'Departamento',
        'Motivo',
        'Fecha',
        'Hora de entrada',
        'Hora de salida',
        'Estado',
        'Estado del correo'
    ], ';', '"', '');

    foreach ($visitas as $v) {
        fputcsv($salida, [
            $v['id'],
            seguroCSV($v['visitante']),
            seguroCSV($v['documento']),
            seguroCSV($v['telefono']),
            seguroCSV($v['destino']),
            seguroCSV($v['departamento']),
            seguroCSV($v['motivo']),
            $v['fecha'],
            $v['hora_entrada'],
            $v['hora_salida'],
            seguroCSV($v['estado']),
            seguroCSV($v['notificacion_estado'])
        ], ';', '"', '');
    }

    fclose($salida);
    exit;
}

// INTERFAZ
header_ui('Reportes de visitas');
?>

<div class="panel p-4 mb-4">

    <h4 class="mb-2">Reportes de INAC</h4>

    <p class="text-secondary">
        Consulta el historial de entradas y salidas
        registradas en el instituto.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="GET">

        <div class="row g-3">

            <div class="col-md-3">
                <label class="form-label">
                    Desde
                </label>

                <input type="date"
                       name="desde"
                       class="form-control"
                       value="<?= h($desde) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    Hasta
                </label>

                <input type="date"
                       name="hasta"
                       class="form-control"
                       value="<?= h($hasta) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    Buscar persona o destino
                </label>

                <input type="text"
                       name="buscar"
                       class="form-control"
                       maxlength="120"
                       placeholder="Nombre o departamento"
                       value="<?= h($buscar) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    Estado
                </label>

                <select name="estado" class="form-select">

                    <option value="">
                        Todos
                    </option>

                    <option value="Dentro"
                        <?= $estado === 'Dentro'
                            ? 'selected' : '' ?>>
                        Dentro
                    </option>

                    <option value="Finalizada"
                        <?= $estado === 'Finalizada'
                            ? 'selected' : '' ?>>
                        Finalizada
                    </option>

                </select>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">

                <button type="submit"
                        class="btn btn-primary">
                    Buscar visitas
                </button>

                <button type="submit"
                        name="exportar"
                        value="csv"
                        class="btn btn-success">
                    Exportar a Excel (CSV)
                </button>

                <a href="reportes.php"
                   class="btn btn-outline-secondary">
                    Limpiar filtros
                </a>

            </div>

        </div>

    </form>

</div>

<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="panel p-4">
            <div class="text-secondary">
                Total de visitas
            </div>

            <div class="metric">
                <?= $total ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel p-4">
            <div class="text-secondary">
                Personas dentro
            </div>

            <div class="metric">
                <?= $dentro ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel p-4">
            <div class="text-secondary">
                Visitas finalizadas
            </div>

            <div class="metric">
                <?= $finalizadas ?>
            </div>
        </div>
    </div>

</div>

<div class="panel p-4">

    <h4 class="mb-3">Historial de visitas</h4>

    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Visitante</th>
                    <th>Destino</th>
                    <th>Motivo</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Estado</th>
                    <th>Correo</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($visitas as $v): ?>

                    <tr>

                        <td>
                            #<?= (int)$v['id'] ?>
                        </td>

                        <td>
                            <?= h($v['visitante']) ?>
                        </td>

                        <td>
                            <?= h($v['destino'] ?? 'Sin asignar') ?>
                        </td>

                        <td>
                            <?= h($v['motivo']) ?>
                        </td>

                        <td>
                            <?= h($v['hora_entrada']) ?>
                        </td>

                        <td>
                            <?= h($v['hora_salida'] ?? 'Pendiente') ?>
                        </td>

                        <td>
                            <?php if ($v['estado'] === 'Dentro'): ?>

                                <span class="badge bg-primary">
                                    Dentro
                                </span>

                            <?php else: ?>

                                <span class="badge bg-success">
                                    <?= h($v['estado']) ?>
                                </span>

                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h($v['notificacion_estado'] ?? 'Sin correo') ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$visitas): ?>

                    <tr>
                        <td colspan="8"
                            class="text-center text-secondary">
                            No se encontraron visitas.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

    </div>

</div>

<?php footer_ui(); ?>
