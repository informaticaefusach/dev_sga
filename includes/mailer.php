<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

cargarEnv(__DIR__ . '/../.env');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function cargarPlantillaCorreo($ruta, array $variables = [])
{
    if (!file_exists($ruta)) {
        throw new Exception("No se encontro la plantilla de correo: " . $ruta);
    }

    $html = file_get_contents($ruta);

    foreach ($variables as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
    }

    return $html;
}

function enviarCorreoCertificado(array $data)
{
    $mail = new PHPMailer(true);

    $host = $_ENV['MAIL_HOST'] ?? '';
    $port = (int) ($_ENV['MAIL_PORT'] ?? 587);
    $username = $_ENV['MAIL_USERNAME'] ?? '';
    $password = $_ENV['MAIL_PASSWORD'] ?? '';
    $encryption = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
    $from = $_ENV['MAIL_FROM'] ?? $username;
    $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Certificados Cursos';

    if (!$host || !$username || !$password || !$from) {
        throw new Exception("Faltan variables MAIL_* en el archivo .env");
    }

    $destinatario = $data['destinatario'] ?? '';
    $nombreDestinatario = $data['nombre_destinatario'] ?? '';
    $asunto = $data['asunto'] ?? 'Entrega de Certificado';
    $rutaPlantilla = $data['ruta_plantilla'] ?? __DIR__ . '/../templates/email/certificado.html';
    $variables = $data['variables'] ?? [];
    $adjuntos = $data['adjuntos'] ?? [];

    if (!$destinatario) {
        throw new Exception("No se especifico el destinatario del correo");
    }

    $bodyHtml = cargarPlantillaCorreo($rutaPlantilla, $variables);

    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;

    if ($encryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->Port = $port;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($from, $fromName);
    $mail->addAddress($destinatario, $nombreDestinatario);

    foreach ($adjuntos as $adjunto) {
        if (is_array($adjunto)) {
            $rutaAdjunto = $adjunto['ruta'] ?? '';
            $nombreAdjunto = $adjunto['nombre'] ?? '';
        } else {
            $rutaAdjunto = $adjunto;
            $nombreAdjunto = '';
        }

        if ($rutaAdjunto && file_exists($rutaAdjunto)) {
            if ($nombreAdjunto) {
                $mail->addAttachment($rutaAdjunto, $nombreAdjunto);
            } else {
                $mail->addAttachment($rutaAdjunto);
            }
        }
    }

    $mail->isHTML(true);
    $mail->Subject = $asunto;
    $mail->Body = $bodyHtml;
    $mail->AltBody = strip_tags(
        str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>', '</table>'],
            PHP_EOL,
            $bodyHtml
        )
    );

    $mail->send();

    return true;
}
