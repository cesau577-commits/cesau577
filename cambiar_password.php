
<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $_SESSION["csrf_password"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf"] ?? "";

    if (
        !isset($_SESSION["csrf_password"]) ||
        !hash_equals($_SESSION["csrf_password"], $token)
    ) {
        $error = "Solicitud no válida.";
    } else {

        $actual = $_POST["actual"] ?? "";
        $nueva = $_POST["nueva"] ?? "";
        $confirmar = $_POST["confirmar"] ?? "";

        $id = (int) $_SESSION["usuario_id"];

        $stmt = $conexion->prepare(
            "SELECT password FROM cv_usuarios WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($hash_actual);

        $existe = $stmt->fetch();
        $stmt->close();

        if (!$existe || !password_verify($actual, $hash_actual)) {
            $error = "La contraseña actual es incorrecta.";
        } elseif (strlen($nueva) < 4) {
            $error = "La nueva contraseña debe tener al menos 4 caracteres.";
        } elseif ($nueva !== $confirmar) {
            $error = "Las contraseñas nuevas no coinciden.";
        } elseif (hash_equals($actual, $nueva)) {
            $error = "La contraseña nueva debe ser diferente.";
        } else {

            $nuevo_hash = password_hash($nueva, PASSWORD_DEFAULT);

            $actualizar = $conexion->prepare(
                "UPDATE cv_usuarios SET password = ? WHERE id = ?"
            );

            $actualizar->bind_param("si", $nuevo_hash, $id);

            if ($actualizar->execute()) {
                $mensaje = "Contraseña cambiada correctamente.";
                session_regenerate_id(true);
                $_SESSION["csrf_password"] = bin2hex(random_bytes(32));
            } else {
                $error = "No se pudo actualizar la contraseña.";
            }

            $actualizar->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cambiar contraseña | SICVI</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body {
    background: linear-gradient(135deg,#0b1935,#1c3f79);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.card {
    max-width: 460px;
    width: 95%;
    border-radius: 18px;
    border: none;
}
.btn-azul {
    background: #193b75;
    color: white;
}
.btn-azul:hover {
    background: #102a56;
    color: white;
}
</style>
</head>
<body>

<div class="card shadow-lg p-4">
    <h3 class="text-center fw-bold mb-2">◆ SICVI</h3>
    <p class="text-center text-secondary">
        Cambiar contraseña
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf"
            value="<?= htmlspecialchars(
                $_SESSION["csrf_password"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            ) ?>">

        <div class="mb-3">
            <label class="form-label">Contraseña actual</label>
            <input type="password" name="actual"
                   class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="nueva"
                   class="form-control" minlength="4"
                   autocomplete="new-password" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirmar contraseña nueva</label>
            <input type="password" name="confirmar"
                   class="form-control" minlength="4"
                   autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-azul w-100">
            Guardar nueva contraseña
        </button>
    </form>

    <a href="index.php" class="btn btn-outline-secondary mt-3">
        Volver al panel principal
    </a>
</div>

</body>
</html>
