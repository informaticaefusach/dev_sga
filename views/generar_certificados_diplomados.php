<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/* =====================================
   FUNCIONES
===================================== */

function fechaEspanolDiplomado($fecha)
{
    if (!$fecha) {
        return '';
    }

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

    if (!$t) {
        return '';
    }

    return date('j', $t) . ' de ' . $meses[(int) date('n', $t)] . ' del ' . date('Y', $t);
}

function slugDiplomado($texto)
{
    $texto = trim($texto);
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');

    return $texto ?: 'diplomado';
}

/* =====================================
   FILTROS
===================================== */

$programa = trim($_GET['programa'] ?? $_POST['programa'] ?? '');
$plantilla = trim($_GET['plantilla'] ?? $_POST['plantilla'] ?? 'plantilla_certificado.docx');

$mensaje = '';
$error = '';

/* =====================================
   PROGRAMAS DISPONIBLES
===================================== */

$stmt = $pdo->query("
    SELECT DISTINCT nombre_programa
    FROM dir_diplomados_registros
    WHERE nombre_programa IS NOT NULL
      AND nombre_programa <> ''
    ORDER BY nombre_programa
");
$programas = $stmt->fetchAll();

/* =====================================
   PLANTILLAS DISPONIBLES
===================================== */

$plantillas_dir = __DIR__ . '/../plantillas';
$plantillas = [];

if (is_dir($plantillas_dir)) {
    $archivos = scandir($plantillas_dir);

    foreach ($archivos as $archivo) {
        if (pathinfo($archivo, PATHINFO_EXTENSION) === 'docx') {
            $plantillas[] = $archivo;
        }
    }
}

sort($plantillas);

if (!in_array($plantilla, $plantillas, true)) {
    $plantilla = 'plantilla_certificado.docx';
}

/* =====================================
   GENERAR CERTIFICADOS
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($programa === '') {
        $error = "Debes seleccionar un programa.";
    } elseif (empty($plantilla)) {
        $error = "Debes seleccionar una plantilla.";
    } else {
        $template_path = $plantillas_dir . '/' . basename($plantilla);

        if (!file_exists($template_path)) {
            $error = "No se encontro la plantilla seleccionada.";
        } else {
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    nombre_programa,
                    nombre,
                    apellido_paterno,
                    apellido_materno,
                    email,
                    rut,
                    nota,
                    aprobado,
                    horas,
                    fecha_inicio,
                    fecha_termino,
                    tipo_documento,
                    plantilla,
                    codigo_certificado,
                    archivo_pdf,
                    fecha_emision
                FROM dir_diplomados_registros
                WHERE nombre_programa = ?
                  AND aprobado = 1
                ORDER BY id ASC
            ");
            $stmt->execute([$programa]);
            $registros = $stmt->fetchAll();

            if (empty($registros)) {
                $error = "No hay registros aprobados para el programa seleccionado.";
            } else {
                $base_certificados_dir = __DIR__ . '/../certificados/diplomados/';
                $programa_slug = slugDiplomado($programa);
                $certificados_dir = $base_certificados_dir . $programa_slug . '/';

                if (!file_exists($certificados_dir)) {
                    mkdir($certificados_dir, 0775, true);
                }

                $generados = 0;
                $omitidos = 0;
                $fechaHoy = date('Y-m-d');

                foreach ($registros as $r) {
                    if (!empty($r['archivo_pdf']) && !empty($r['codigo_certificado'])) {
                        $omitidos++;
                        continue;
                    }

                    $nombreCompleto = trim(
                        $r['nombre'] . ' ' .
                        $r['apellido_paterno'] . ' ' .
                        $r['apellido_materno']
                    );

                    $codigo = strtoupper(substr(md5($r['id'] . $nombreCompleto . microtime(true)), 0, 10));

                    $url = "https://cert.capusach.cl/index.php?page=verificar_certificado&codigo=$codigo";

                    $qr_path = $certificados_dir . "qr_" . $codigo . ".png";

                    $result = Builder::create()
                        ->writer(new PngWriter())
                        ->data($url)
                        ->size(300)
                        ->margin(10)
                        ->build();

                    $result->saveToFile($qr_path);

                    if (!file_exists($qr_path) || filesize($qr_path) == 0) {
                        continue;
                    }

                    $template = new TemplateProcessor($template_path);

                    $template->setValue('nombre', $nombreCompleto);
                    $template->setValue('curso', $r['nombre_programa']);
                    $template->setValue('nota', $r['nota']);
                    $template->setValue('horas', $r['horas']);
                    $template->setValue('rut', $r['rut'] ?? '');
                    $template->setValue('fecha_inicio', fechaEspanolDiplomado($r['fecha_inicio']));
                    $template->setValue('fecha_fin', fechaEspanolDiplomado($r['fecha_termino']));
                    $template->setValue('fecha_termino', fechaEspanolDiplomado($r['fecha_termino']));
                    $template->setValue('fecha_certificado', fechaEspanolDiplomado($fechaHoy));
                    $template->setValue('tipo_documento', $r['tipo_documento'] ?: 'Diploma');

                    $template->setImageValue('qr', [
                        'path' => realpath($qr_path),
                        'width' => 120,
                        'height' => 120,
                        'ratio' => true
                    ]);

                    $nombre_archivo = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($nombreCompleto));

                    $docx = $certificados_dir . "certificado_" . $nombre_archivo . ".docx";
                    $pdf = $certificados_dir . "certificado_" . $nombre_archivo . ".pdf";

                    $template->saveAs($docx);

                    $cmd = "HOME=/tmp /usr/bin/libreoffice --headless --nologo --nofirststartwizard " .
                        "--convert-to pdf " .
                        escapeshellarg($docx) .
                        " --outdir " .
                        escapeshellarg($certificados_dir);

                    exec($cmd);

                    if (!file_exists($pdf)) {
                        continue;
                    }

                    $stmtUpdate = $pdo->prepare("
                        UPDATE dir_diplomados_registros
                        SET
                            plantilla = ?,
                            codigo_certificado = ?,
                            archivo_pdf = ?,
                            fecha_emision = ?
                        WHERE id = ?
                    ");

                    $stmtUpdate->execute([
                        basename($template_path),
                        $codigo,
                        basename($pdf),
                        $fechaHoy,
                        $r['id']
                    ]);

                    if (file_exists($docx)) {
                        unlink($docx);
                    }

                    if (file_exists($qr_path)) {
                        unlink($qr_path);
                    }

                    $generados++;
                }

                $mensaje = "Proceso completado. Generados: $generados. Omitidos: $omitidos.";
            }
        }
    }
}

/* =====================================
   LISTADO DEL PROGRAMA SELECCIONADO
===================================== */

$registrosPrograma = [];

if ($programa !== '') {
    $stmt = $pdo->prepare("
        SELECT
            id,
            nombre_programa,
            nombre,
            apellido_paterno,
            apellido_materno,
            email,
            rut,
            nota,
            aprobado,
            horas,
            fecha_inicio,
            fecha_termino,
            tipo_documento,
            plantilla,
            codigo_certificado,
            archivo_pdf,
            fecha_emision
        FROM dir_diplomados_registros
        WHERE nombre_programa = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$programa]);
    $registrosPrograma = $stmt->fetchAll();
}

?>

<div class="container-fluid">

    <h2 class="mb-4">Generar certificados de diplomados</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">

            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="generar_certificados_diplomados">

                <div class="row">
                    <div class="col-md-6">
                        <label>Programa</label>
                        <select name="programa" class="form-control">
                            <option value="">Seleccione un programa</option>
                            <?php foreach ($programas as $p): ?>
                                <option value="<?= htmlspecialchars($p['nombre_programa']) ?>"
                                    <?= $programa === $p['nombre_programa'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nombre_programa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            Filtrar
                        </button>

                        <a href="index.php?page=generar_certificados_diplomados" class="btn btn-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <?php if ($programa === ''): ?>

                <div class="alert alert-secondary mb-0">
                    Selecciona un programa para revisar y generar certificados.
                </div>

            <?php elseif (empty($registrosPrograma)): ?>

                <div class="alert alert-warning mb-0">
                    No se encontraron registros para el programa seleccionado.
                </div>

            <?php else: ?>

                <form method="POST" action="index.php?page=generar_certificados_diplomados">
                    <input type="hidden" name="programa" value="<?= htmlspecialchars($programa) ?>">

                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Plantilla</label>
                            <select name="plantilla" class="form-control">
                                <?php foreach ($plantillas as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>" <?= $plantilla === $p ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-warning">
                                Generar certificados aprobados
                            </button>
                        </div>

                        <div class="col-md-auto">
                            <a href="index.php?page=listado_diplomados&programa=<?= urlencode($programa) ?>"
                                class="btn btn-secondary">
                                Volver al listado
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        Solo se generaran certificados para registros con estado <strong>aprobado</strong>.
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Alumno</th>
                                <th>Email</th>
                                <th>RUT</th>
                                <th>Nota</th>
                                <th>Estado</th>
                                <th>Horas</th>
                                <th>Fecha inicio</th>
                                <th>Fecha termino</th>
                                <th>Codigo</th>
                                <th>Certificado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrosPrograma as $r): ?>
                                <tr>
                                    <td>
                                        <?= $r['id'] ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(trim($r['nombre'] . ' ' . $r['apellido_paterno'] . ' ' . $r['apellido_materno'])) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['email']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['rut']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['nota']) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['aprobado'])): ?>
                                            <span class="badge bg-success">Aprobado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Reprobado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['horas']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['fecha_inicio']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['fecha_termino']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['codigo_certificado'] ?? '') ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['archivo_pdf'])): ?>
                                            <span class="text-success">Generado</span>
                                        <?php else: ?>
                                            <span class="text-muted">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>

</div>