
<?php

// ==========================================
// INAC - SISTEMA INSTITUCIONAL
// DE CONTROL DE VISITAS
// Instituto Nacional Albert Camus
// ==========================================

// CONEXIÓN A BASE DE DATOS
require_once __DIR__ . '/conexion.php';

// ZONA HORARIA
date_default_timezone_set('America/El_Salvador');

// ==========================================
// CONFIGURACIÓN DE SESIÓN
// ==========================================

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);

    session_start();
}

// ==========================================
// FUNCIONES DE SEGURIDAD
// ==========================================

// Evitar código HTML no deseado
function h($v) {
    return htmlspecialchars(
        (string)($v ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Verificar que exista una sesión activa
function auth() {

    if (empty($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Permitir acceso solo al administrador
function admin() {

    auth();

    if (($_SESSION['rol'] ?? '') !== 'administrador') {
        http_response_code(403);
        exit('Acceso restringido');
    }
}

// Crear token CSRF
function csrf() {

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }

    return $_SESSION['csrf'];
}

// Verificar token CSRF
function checkcsrf() {

    if (!hash_equals(
        csrf(),
        (string)($_POST['csrf'] ?? '')
    )) {
        http_response_code(403);
        exit('Solicitud no válida');
    }
}

// Obtener un número entero enviado por POST
function postint($k) {

    return filter_input(
        INPUT_POST,
        $k,
        FILTER_VALIDATE_INT
    ) ?: 0;
}

// Guardar mensajes temporales
function flash($m) {
    $_SESSION['flash'] = $m;
}

// Redireccionar
function go($page) {
    header('Location: ' . $page);
    exit;
}

// ==========================================
// INTERFAZ PRINCIPAL DE INAC
// ==========================================

function header_ui($title) {

    auth();

    $rol = $_SESSION['rol'] ?? '';
    $nombre = $_SESSION['nombre'] ?? '';

    // Detectar la página actual
    $pagina = basename(
        parse_url(
            $_SERVER['REQUEST_URI'] ?? '',
            PHP_URL_PATH
        )
    );

    // Resaltar la opción seleccionada
    $activo = function($archivo) use ($pagina) {
        return $pagina === $archivo ? ' active' : '';
    };

    ?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?= h($title) ?> | INAC</title>

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- ESTILOS DEL SISTEMA -->
    <link rel="stylesheet" href="estilo.css?v=3">

</head>

<body>

<div class="shell">

    <!-- =====================================
         MENÚ LATERAL
    ====================================== -->

    <aside class="sidebar">

        <!-- LOGO Y NOMBRE INSTITUCIONAL -->

        <div class="brand">

            <img
                src="imagenes/camuslogo.png"
                alt="Logo del Instituto Nacional Albert Camus"
                class="brand-logo"
            >

            <div>INAC</div>

            <small>
                Control Institucional de Visitas
            </small>

        </div>

        <!-- OPCIONES DEL MENÚ -->

        <nav class="nav flex-column gap-1">

            <!-- PANEL PRINCIPAL -->

            <a
                class="<?= $activo('index.php') ?>"
                href="index.php"
            >
                ▦ Panel principal
            </a>

            <!-- ADMINISTRADOR Y RECEPCIONISTA -->

            <?php if (
                in_array(
                    $rol,
                    ['administrador', 'recepcionista'],
                    true
                )
            ): ?>

                <a
                    class="<?= $activo('registrar_visita.php') ?>"
                    href="registrar_visita.php"
                >
                    ＋ Registrar visita
                </a>

                <a
                    class="<?= $activo('registrar_salida.php') ?>"
                    href="registrar_salida.php"
                >
                    ↗ Registrar salida
                </a>

            <?php endif; ?>

            <!-- NOTIFICACIONES -->

            <a
                class="<?= $activo('notificaciones.php') ?>"
                href="notificaciones.php"
            >
                ♧ Notificaciones
            </a>

            <!-- OPCIONES DEL ADMINISTRADOR -->

            <?php if ($rol === 'administrador'): ?>

                <a
                    class="<?= $activo('personal.php') ?>"
                    href="personal.php"
                >
                    ♙ Personal
                </a>

                <a
                    class="<?= $activo('usuarios.php') ?>"
                    href="usuarios.php"
                >
                    ♙ Usuarios
                </a>

                <a
                    class="<?= $activo('reportes.php') ?>"
                    href="reportes.php"
                >
                    ▤ Reportes
                </a>

            <?php endif; ?>

        </nav>

        <!-- INFORMACIÓN DE LA SESIÓN -->

        <div class="mt-auto small opacity-75">

            <div class="mb-2">

                Sesión:
                <?= h($nombre) ?>

            </div>

            <a
                class="text-white"
                href="logout.php"
            >
                Cerrar sesión
            </a>

        </div>

    </aside>

    <!-- =====================================
         CONTENIDO PRINCIPAL
    ====================================== -->

    <main class="main">

        <div class="top">

            <h1>
                <?= h($title) ?>
            </h1>

            <span>
                <?= h(ucfirst($rol)) ?>
            </span>

        </div>

        <!-- MENSAJES DEL SISTEMA -->

        <?php if (!empty($_SESSION['flash'])): ?>

            <div class="alert alert-info">

                <?= h($_SESSION['flash']) ?>

            </div>

            <?php unset($_SESSION['flash']); ?>

        <?php endif; ?>

<?php
}

// ==========================================
// PIE DE PÁGINA
// ==========================================

function footer_ui() {

    echo '</main></div></body></html>';
}

?>
