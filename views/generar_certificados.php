<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/* =====================================
   FORMATO FECHA ESPAÑOL
===================================== */

function fechaEspanol($fecha)
{
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];

    $t = strtotime($fecha);

    return date('j', $t) . ' de ' . $meses[(int) date('n', $t)] . ' del ' . date('Y', $t);
}

/* =====================================
   EDICION
===================================== */

$edicion_id = $_GET['edicion_id'] ?? null;

if (!$edicion_id) {
    die("Edición no especificada");
}

/* =====================================
   ALUMNOS APROBADOS
===================================== */

$stmt = $pdo->prepare("
SELECT
m.id as matricula_id,
a.nombre,
a.apellido_paterno,
a.apellido_materno,
m.nota_final,
c.curso_nombre,
c.horas_cronologicas,
e.fecha_inicio,
e.fecha_fin,
e.version
FROM dir_cursos_matriculas m
JOIN dir_cursos_alumnos a ON a.id = m.alumno_id
JOIN dir_cursos_ediciones e ON e.id = m.edicion_id
JOIN dir_cursos_catalogo c ON c.id = e.curso_id
WHERE m.edicion_id = ?
AND m.aprobado = 1
");

$stmt->execute([$edicion_id]);
$alumnos = $stmt->fetchAll();

if (!$alumnos) {
    echo "<script>
    alert('No hay alumnos aprobados');
    window.location='index.php?page=matriculas&edicion_id=$edicion_id';
    </script>";
    exit;
}

/* =====================================
   RUTAS
===================================== */

$template_path = __DIR__ . '/../plantillas/plantilla_certificado.docx';
$base_certificados_dir = __DIR__ . '/../certificados/';

// Tomar nombre del curso (del primer alumno)
$curso_nombre = $alumnos[0]['curso_nombre'];

$version = $alumnos[0]['version'] ?? '1';

$version_slug = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($version));

// Sanitizar nombre de carpeta
$curso_slug = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($curso_nombre));

// Ruta final por curso
$certificados_dir = $base_certificados_dir . $curso_slug . '_v' . $version_slug . '/';

// Crear carpeta si no existe
if (!file_exists($certificados_dir)) {
    mkdir($certificados_dir, 0775, true);
}


$archivos = [];
$generados = 0;
$omitidos = 0;
$fechaHoy = date('Y-m-d');

/* =====================================
   GENERAR CERTIFICADOS
===================================== */

foreach ($alumnos as $a) {

    /* EVITAR DUPLICADOS */
    $stmt = $pdo->prepare("
        SELECT id FROM dir_cursos_certificados
        WHERE matricula_id = ?
    ");
    $stmt->execute([$a['matricula_id']]);

    if ($stmt->fetch()) {
        $omitidos++;
        continue;
    }

    $nombre = $a['nombre'] . " " . $a['apellido_paterno'] . " " . $a['apellido_materno'];

    /* =============================
       GENERAR CODIGO
    ============================= */

    $codigo = strtoupper(substr(md5($nombre . time()), 0, 10));

    /* =============================
       GENERAR QR CORRECTO
    ============================= */

    $url = "https://cert.capusach.cl/index.php?page=verificar_certificado&codigo=$codigo";

    $qr_path = $certificados_dir . "qr_" . $codigo . ".png";

    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($url)
        ->size(300)
        ->margin(10)
        ->build();

    /* GUARDAR */
    $result->saveToFile($qr_path);

    /* VALIDAR */
    if (!file_exists($qr_path) || filesize($qr_path) == 0) {
        die("Error: QR no generado correctamente");
    }

    /* =============================
       CREAR TEMPLATE
    ============================= */

    $template = new TemplateProcessor($template_path);

    $template->setValue('nombre', $nombre);
    $template->setValue('curso', $a['curso_nombre']);
    $template->setValue('nota', $a['nota_final']);
    $template->setValue('horas', $a['horas_cronologicas']);

    $template->setValue('fecha_inicio', fechaEspanol($a['fecha_inicio']));
    $template->setValue('fecha_fin', fechaEspanol($a['fecha_fin']));
    $template->setValue('fecha_certificado', fechaEspanol($fechaHoy));

    /* INSERTAR QR */
    $template->setImageValue('qr', [
        'path' => realpath($qr_path),
        'width' => 120,
        'height' => 120,
        'ratio' => true
    ]);

    /* =============================
       GENERAR DOCX
    ============================= */

    $nombre_archivo = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($nombre));

    $docx = $certificados_dir . "certificado_" . $nombre_archivo . ".docx";

    $template->saveAs($docx);

    /* =============================
       CONVERTIR A PDF
    ============================= */

    $pdf = $certificados_dir . "certificado_" . $nombre_archivo . ".pdf";

    $cmd = "HOME=/tmp /usr/bin/libreoffice --headless --nologo --nofirststartwizard " .
        "--convert-to pdf " .
        escapeshellarg($docx) .
        " --outdir " .
        escapeshellarg($certificados_dir);

    exec($cmd);

    /* =============================
       SI SE CREÓ → GUARDAR
    ============================= */

    if (!file_exists($pdf)) {
        echo "❌ PDF NO EXISTE: " . $pdf . "<br>";
        continue;
    }

    echo "✅ PDF OK: " . $pdf . "<br>";

    /* DEBUG DATOS */
    echo "<pre>";
    print_r([
        'matricula_id' => $a['matricula_id'],
        'codigo' => $codigo,
        'archivo' => basename($pdf),
        'fecha' => $fechaHoy
    ]);
    echo "</pre>";

    try {

        $stmtInsert = $pdo->prepare("
        INSERT INTO dir_cursos_certificados
        (matricula_id, codigo_certificado, archivo_pdf, fecha_emision)
        VALUES (?, ?, ?, ?)
    ");

        $stmtInsert->execute([
            $a['matricula_id'],
            $codigo,
            basename($pdf),
            $fechaHoy
        ]);

        echo "✅ INSERT OK - Matricula: " . $a['matricula_id'] . "<br>";

        $archivos[] = $pdf;
        $generados++;

    } catch (PDOException $e) {

        echo "💥 ERROR EN INSERT:<br>";
        echo $e->getMessage() . "<br><br>";

        echo "<b>Datos enviados:</b><br>";
        print_r([
            'matricula_id' => $a['matricula_id'],
            'codigo' => $codigo,
            'archivo' => basename($pdf),
            'fecha' => $fechaHoy
        ]);

        exit;
    }

    /* LIMPIAR */
    if (file_exists($docx))
        unlink($docx);
    if (file_exists($qr_path))
        unlink($qr_path);
}

/* =====================================
   CREAR ZIP
===================================== */

$zip_file = $certificados_dir . $curso_slug . "_edicion_" . $edicion_id . ".zip";

$zip = new ZipArchive();

if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {

    foreach ($archivos as $file) {
        $zip->addFile($file, basename($file));
    }

    $zip->close();
}

?>

<div class="container-fluid">

    <div class="card shadow mt-4">

        <div class="card-body text-center">

            <h3 class="text-success">Proceso completado</h3>

            <p>
                Nuevos certificados: <strong>
                    <?= $generados ?>
                </strong><br>
                Ya existentes: <strong>
                    <?= $omitidos ?>
                </strong>
            </p>

            <div class="mt-4">

                <?php if ($generados > 0): ?>
                    <a href="certificados/<?= $curso_slug ?>/<?= basename($zip_file) ?>" class="btn btn-success" download>
                        Descargar ZIP
                    </a>
                <?php endif; ?>

                <a href="index.php?page=matriculas&edicion_id=<?= $edicion_id ?>" class="btn btn-primary">
                    Volver
                </a>

            </div>

        </div>

    </div>

</div>