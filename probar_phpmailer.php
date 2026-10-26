
<?php

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo 'PHPMailer instalado correctamente en INAC';
} else {
    echo 'Error al cargar PHPMailer';
}
?>
