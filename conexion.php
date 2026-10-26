
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "TU_HOST_MYSQL";            // Host de tu base de datos
$usuario = "TU_USUARIO_MYSQL";     // Usuario MySQL
$password = "TU_CONTRASENA_MYSQL"; // Contraseña MySQL
$base_datos = "TU_BASE_DE_DATOS";

try {
    $conexion = new mysqli(
        $host,
        $usuario,
        $password,
        $base_datos
    );

    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Error de conexión MySQL: " . $e->getMessage());
    http_response_code(500);
    exit("No se pudo conectar con la base de datos. Revisa la configuración.");
}
?>
