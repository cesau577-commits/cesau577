
<?php
// CONFIGURACIÓN DE CORREO - INAC

// Servidor SMTP de Gmail
define('SMTP_HOST', 'smtp.gmail.com');

// Correo que enviará las notificaciones
define('SMTP_USER', 'tu_correo@gmail.com');

// Contraseña de aplicación de Google
// NO es la contraseña normal de Gmail
define('SMTP_PASS', 'TU_CLAVE_DE_APLICACION_GMAIL');

// Puerto SMTP
define('SMTP_PORT', 587);

// Correo remitente
define('SMTP_FROM', 'tu_correo@gmail.com');

// Nombre que verá el destinatario
define('SMTP_FROM_NAME', 'INAC - Control de Visitas');
?>
