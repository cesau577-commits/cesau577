
<?php
// ========================================
// INAC - SISTEMA DE CONTROL DE VISITAS
// ENVÍO DE CORREOS CON PHPMAILER
// ========================================

// Configuración SMTP
require_once __DIR__ . '/correo_config.php';

// Archivos de PHPMailer
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

// Función utilizada por registrar_visita.php
function enviarCorreoVisita(
    $email,
    $docente,
    $visitante,
    $motivo,
    $fecha
) {

    // Verificar configuración del correo
    if (
        !defined('SMTP_HOST') ||
        !defined('SMTP_USER') ||
        !defined('SMTP_PASS') ||
        !defined('SMTP_FROM') ||
        SMTP_HOST === '' ||
        SMTP_USER === '' ||
        SMTP_PASS === '' ||
        SMTP_FROM === ''
    ) {
        error_log('INAC: Configuración SMTP incompleta.');
        return false;
    }

    // Validar correo del destinatario
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log('INAC: Correo del destinatario inválido.');
        return false;
    }

    // Crear objeto PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {

        // CONFIGURACIÓN DEL SERVIDOR
        $mail->isSMTP();

        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;

        // Seguridad TLS
        $mail->SMTPSecure =
            PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = SMTP_PORT;

        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';

        // Remitente
        $mail->setFrom(
            SMTP_FROM,
            SMTP_FROM_NAME
        );

        // Destinatario
        $mail->addAddress($email, $docente);

        // Respuestas
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

        // CORREO EN FORMATO HTML
        $mail->isHTML(true);

        $mail->Subject =
            'INAC - Nueva visita registrada';

        // Evitar que los datos introducidos
        // se interpreten como HTML
        $nombreDocente = htmlspecialchars(
            $docente,
            ENT_QUOTES,
            'UTF-8'
        );

        $nombreVisitante = htmlspecialchars(
            $visitante,
            ENT_QUOTES,
            'UTF-8'
        );

        $motivoVisita = nl2br(htmlspecialchars(
            $motivo,
            ENT_QUOTES,
            'UTF-8'
        ));

        $fechaVisita = htmlspecialchars(
            $fecha,
            ENT_QUOTES,
            'UTF-8'
        );

        // Diseño del correo
        $mail->Body = '
        <div style="
            font-family:Arial,sans-serif;
            max-width:600px;
            margin:auto;
            border:1px solid #e2e8f0;
            border-radius:12px;
            overflow:hidden;
        ">

            <div style="
                background-color:#10264b;
                padding:30px;
                text-align:center;
                color:#ffffff;
            ">

                <h1 style="
                    margin:0;
                    font-size:32px;
                    color:#ffffff;
                ">
                    INAC
                </h1>

                <p style="
                    margin-top:8px;
                    color:#dbeafe;
                ">
                    Sistema Institucional de Control de Visitas
                </p>

            </div>

            <div style="
                padding:30px;
                color:#1e293b;
            ">

                <h2 style="color:#10264b;">
                    Nueva visita registrada
                </h2>

                <p>
                    Estimado/a <strong>'.$nombreDocente.'</strong>:
                </p>

                <p>
                    Le informamos que se ha registrado
                    una persona que desea visitarle
                    en las instalaciones del instituto.
                </p>

                <div style="
                    background:#f1f5f9;
                    padding:20px;
                    border-radius:8px;
                ">

                    <p>
                        <strong>Visitante:</strong>
                        '.$nombreVisitante.'
                    </p>

                    <p>
                        <strong>Motivo:</strong>
                        '.$motivoVisita.'
                    </p>

                    <p>
                        <strong>Fecha y hora:</strong>
                        '.$fechaVisita.'
                    </p>

                </div>

                <p>
                    Por favor, comuníquese con recepción
                    para atender la visita.
                </p>

                <p>
                    Atentamente,<br>
                    <strong>Administración INAC</strong>
                </p>

            </div>

            <div style="
                background:#10264b;
                color:#ffffff;
                padding:15px;
                text-align:center;
                font-size:12px;
            ">
                Este mensaje fue generado automáticamente
                por INAC.
            </div>

        </div>';

        // Versión de texto para otros clientes
        $mail->AltBody =
            "INAC - Nueva visita registrada\n\n" .
            "Estimado/a: $docente\n\n" .
            "Se ha registrado una nueva visita.\n\n" .
            "Visitante: $visitante\n" .
            "Motivo: $motivo\n" .
            "Fecha y hora: $fecha\n\n" .
            "Comuníquese con recepción para atenderla.\n\n" .
            "Administración INAC";

        // Enviar correo
        $mail->send();

        return true;

    } catch (Throwable $e) {

        // Registrar el error sin mostrar
        // información sensible al visitante
        error_log(
            'INAC - Error SMTP: ' . $e->getMessage()
        );

        return false;
    }
}
?>
