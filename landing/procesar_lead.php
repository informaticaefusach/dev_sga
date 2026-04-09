<?php

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/env.php';
cargarEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../db.php';

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró vendor/autoload.php.'
    ]);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function clean_input($data)
{
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }

    $data = trim($data);
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $data);
    return $data;
}

function log_error_custom($message, $data = null)
{
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $line = '[' . $timestamp . '] ' . $message;

    if ($data !== null) {
        $line .= ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    $line .= PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND);
}

function parse_email_list(string $raw): array
{
    $result = [];
    $items = array_filter(array_map('trim', explode(',', $raw)));

    foreach ($items as $item) {
        $parts = array_map('trim', explode('|', $item, 2));
        $email = $parts[0] ?? '';
        $name = $parts[1] ?? '';

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result[] = [
                'email' => $email,
                'name' => $name
            ];
        }
    }

    return $result;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
    exit;
}

$nombre = clean_input($_POST['nombre'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$telefono = clean_input($_POST['telefono'] ?? '');
$actividad = clean_input($_POST['actividad'] ?? '');
$region = clean_input($_POST['region'] ?? '');
$mensaje = clean_input($_POST['mensaje'] ?? '');
$curso = clean_input($_POST['curso'] ?? '');
$slug = clean_input($_POST['slug'] ?? '');
$curso_id = clean_input($_POST['curso_id'] ?? '');

if ($nombre === '' || $email === '' || $mensaje === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Debes completar nombre, correo y mensaje.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'El correo electrónico no es válido.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO land_leads (
            lead_name,
            lead_email,
            lead_phone,
            lead_country,
            lead_activity,
            lead_comments,
            lead_question,
            lead_source,
            lead_timestamp
        ) VALUES (
            :lead_name,
            :lead_email,
            :lead_phone,
            :lead_country,
            :lead_activity,
            :lead_comments,
            :lead_question,
            :lead_source,
            NOW()
        )
    ");

    $stmt->execute([
        ':lead_name' => $nombre,
        ':lead_email' => $email,
        ':lead_phone' => $telefono !== '' ? $telefono : null,
        ':lead_country' => $region !== '' ? $region : null,
        ':lead_activity' => $actividad !== '' ? $actividad : null,
        ':lead_comments' => $mensaje,
        ':lead_question' => $curso !== '' ? $curso : null,
        ':lead_source' => $slug !== '' ? 'landing:' . $slug : 'landing'
    ]);
} catch (PDOException $e) {
    log_error_custom('Error guardando lead en BD', [
        'error' => $e->getMessage(),
        'email' => $email
    ]);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudo guardar la información.'
    ]);
    exit;
}

$mail = new PHPMailer(true);

try {
    $smtpHost = $_ENV['LANDING_MAIL_HOST'] ?? '';
    $smtpPort = (int)($_ENV['LANDING_MAIL_PORT'] ?? 587);
    $smtpUser = $_ENV['LANDING_MAIL_USERNAME'] ?? '';
    $smtpPass = $_ENV['LANDING_MAIL_PASSWORD'] ?? '';
    $smtpFrom = $_ENV['LANDING_MAIL_FROM'] ?? '';
    $smtpFromName = $_ENV['LANDING_MAIL_FROM_NAME'] ?? 'Landing Cursos';
    $smtpEncryption = strtolower($_ENV['LANDING_MAIL_ENCRYPTION'] ?? 'tls');

    $appEnv = strtolower($_ENV['APP_ENV'] ?? 'dev');

    $devEmailsRaw = $_ENV['LANDING_DEV_EMAILS'] ?? '';
    $prodEmailsRaw = $_ENV['LANDING_PROD_EMAILS'] ?? '';

    $destinatarios = $appEnv === 'prod'
        ? parse_email_list($prodEmailsRaw)
        : parse_email_list($devEmailsRaw);

    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->Port = $smtpPort;
    $mail->CharSet = 'UTF-8';

    if ($smtpEncryption === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->setFrom($smtpFrom, $smtpFromName);

    if (empty($destinatarios)) {
        throw new Exception('No hay destinatarios configurados para el entorno actual.');
    }

    foreach ($destinatarios as $destinatario) {
        $mail->addAddress($destinatario['email'], $destinatario['name']);
    }

    $mail->addReplyTo($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo lead desde landing: ' . ($curso !== '' ? $curso : 'Curso');

    $body  = '<h3>Nuevo contacto desde landing</h3>';
    $body .= '<p><strong>Curso:</strong> ' . htmlspecialchars($curso) . '</p>';
    $body .= '<p><strong>Slug:</strong> ' . htmlspecialchars($slug) . '</p>';
    $body .= '<p><strong>Curso ID:</strong> ' . htmlspecialchars($curso_id) . '</p>';
    $body .= '<p><strong>Nombre:</strong> ' . htmlspecialchars($nombre) . '</p>';
    $body .= '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>';
    $body .= '<p><strong>Teléfono:</strong> ' . htmlspecialchars($telefono) . '</p>';
    $body .= '<p><strong>Actividad:</strong> ' . htmlspecialchars($actividad) . '</p>';
    $body .= '<p><strong>Región:</strong> ' . htmlspecialchars($region) . '</p>';
    $body .= '<p><strong>Mensaje:</strong><br>' . nl2br(htmlspecialchars($mensaje)) . '</p>';

    $mail->Body = $body;
    $mail->AltBody =
        "Nuevo contacto desde landing\n\n" .
        "Curso: {$curso}\n" .
        "Slug: {$slug}\n" .
        "Curso ID: {$curso_id}\n" .
        "Nombre: {$nombre}\n" .
        "Email: {$email}\n" .
        "Teléfono: {$telefono}\n" .
        "Actividad: {$actividad}\n" .
        "Región: {$region}\n\n" .
        "Mensaje:\n{$mensaje}";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Tu solicitud fue enviada correctamente.'
    ]);
} catch (Exception $e) {
    log_error_custom('Error enviando correo', [
        'error' => $mail->ErrorInfo ?: $e->getMessage(),
        'email' => $email
    ]);

    echo json_encode([
        'success' => false,
        'message' => 'Se guardó la solicitud, pero no se pudo enviar el correo.'
    ]);
}