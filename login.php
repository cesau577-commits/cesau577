
<?php

// ==========================================
// INAC - INICIO DE SESIÓN
// Instituto Nacional Albert Camus
// ==========================================

require_once __DIR__ . '/config.php';

// Si ya inició sesión, ir al panel principal
if (!empty($_SESSION['usuario_id'])) {
    go('index.php');
}

$error = '';

// ==========================================
// PROCESAR INICIO DE SESIÓN
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar seguridad del formulario
    checkcsrf();

    $user = trim($_POST['usuario'] ?? '');
    $pass = (string)($_POST['password'] ?? '');

    // Buscar usuario activo
    $st = $conexion->prepare(
        "SELECT id, nombre, password, rol
         FROM cv_usuarios
         WHERE usuario = ?
         AND estado = 1
         LIMIT 1"
    );

    $st->bind_param('s', $user);
    $st->execute();

    $row = $st->get_result()->fetch_assoc();
    $st->close();

    // Verificar contraseña
    $ok = $row && password_verify(
        $pass,
        $row['password']
    );

    $uid = $row ? (int)$row['id'] : null;
    $estado = $ok ? 'Correcto' : 'Fallido';

    // Registrar intento de acceso
    $log = $conexion->prepare(
        "INSERT INTO cv_accesos
         (usuario_id, resultado)
         VALUES (?, ?)"
    );

    $log->bind_param('is', $uid, $estado);
    $log->execute();
    $log->close();

    // Si los datos son correctos
    if ($ok) {

        // Renovar identificador de sesión
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $uid;
        $_SESSION['nombre'] = $row['nombre'];
        $_SESSION['rol'] = $row['rol'];

        // Ir al panel principal
        go('index.php');
    }

    // Mensaje si los datos son incorrectos
    $error = 'Usuario o contraseña incorrectos.';
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | INAC</title>

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- ESTILOS DEL SISTEMA -->
    <link rel="stylesheet" href="estilo.css?v=3">

    <style>

        /* TARJETA DE INICIO DE SESIÓN */

        .login-card {
            max-width: 430px;
            width: 94%;
            padding: 35px;
            border-radius: 22px;
            background: white;
            box-shadow: 0 20px 55px rgba(0,0,0,0.25);
        }

        /* LOGO DEL INSTITUTO */

        .login-logo {
            display: block;
            width: 125px;
            height: 125px;
            object-fit: contain;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: white;
        }

        /* NOMBRE INAC */

        .login-title {
            font-size: 34px;
            font-weight: 800;
            color: #112e60;
            margin-bottom: 5px;
        }

        /* DESCRIPCIÓN */

        .login-subtitle {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }

        /* BOTÓN */

        .login-button {
            padding: 12px;
            font-weight: 700;
            font-size: 15px;
            border-radius: 10px;
        }

        /* PIE INSTITUCIONAL */

        .login-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 12px;
            color: #94a3b8;
        }

        @media (max-width: 480px) {

            .login-card {
                padding: 25px;
            }

            .login-logo {
                width: 105px;
                height: 105px;
            }

            .login-title {
                font-size: 29px;
            }

        }

    </style>

</head>

<body class="login-bg">

    <div class="login-card">

        <!-- LOGO Y NOMBRE DEL INSTITUTO -->

        <div class="text-center mb-4">

            <img
                src="imagenes/camuslogo.png"
                alt="Logo del Instituto Nacional Albert Camus"
                class="login-logo"
            >

            <h2 class="login-title">
                INAC
            </h2>

            <p class="login-subtitle">
                Sistema Institucional de Control de Visitas
            </p>

        </div>

        <!-- MENSAJE DE ERROR -->

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= h($error) ?>
            </div>

        <?php endif; ?>

        <!-- FORMULARIO DE INICIO DE SESIÓN -->

        <form method="POST">

            <!-- SEGURIDAD CSRF -->

            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf()) ?>"
            >

            <!-- USUARIO -->

            <div class="mb-3">

                <label class="form-label">
                    Usuario
                </label>

                <input
                    type="text"
                    class="form-control"
                    name="usuario"
                    maxlength="50"
                    placeholder="Ingrese su usuario"
                    autocomplete="username"
                    required
                >

            </div>

            <!-- CONTRASEÑA -->

            <div class="mb-4">

                <label class="form-label">
                    Contraseña
                </label>

                <input
                    type="password"
                    class="form-control"
                    name="password"
                    placeholder="Ingrese su contraseña"
                    autocomplete="current-password"
                    required
                >

            </div>

            <!-- BOTÓN INICIAR SESIÓN -->

            <button
                type="submit"
                class="btn btn-primary w-100 login-button"
            >
                Iniciar sesión
            </button>

        </form>

        <!-- PIE DE PÁGINA -->

        <div class="login-footer">
            Instituto Nacional Albert Camus
            <div>INAC - Control de Visitas</div>
        </div>

    </div>

</body>
</html>
