<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* PUBLICO */
$public = true;

require_once __DIR__ . '/../db.php';

/* =============================
   FUNCIONES
============================= */

function fechaEspanol($fecha)
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

function slugTexto($texto)
{
    $texto = trim($texto);
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');

    return $texto ?: 'archivo';
}

/* =============================
   VALIDAR CODIGO
============================= */

$codigo = trim($_GET['codigo'] ?? '');

if (!preg_match('/^[A-Z0-9]+$/', $codigo)) {
    $codigo = '';
}

$data = null;
$tipo = null;
$ruta_certificado = '';

if ($codigo) {

    /* =====================================
       BUSCAR EN CERTIFICADOS DE CURSOS
    ===================================== */

    $stmt = $pdo->prepare("
        SELECT 
            cert.codigo_certificado,
            cert.fecha_emision,
            cert.archivo_pdf,
            a.nombre,
            a.apellido_paterno,
            a.apellido_materno,
            c.curso_nombre,
            c.curso_slug,
            c.horas_cronologicas,
            e.fecha_inicio,
            e.fecha_fin,
            e.version
        FROM dir_cursos_certificados cert
        JOIN dir_cursos_matriculas m ON m.id = cert.matricula_id
        JOIN dir_cursos_alumnos a ON a.id = m.alumno_id
        JOIN dir_cursos_ediciones e ON e.id = m.edicion_id
        JOIN dir_cursos_catalogo c ON c.id = e.curso_id
        WHERE cert.codigo_certificado = ?
        LIMIT 1
    ");

    $stmt->execute([$codigo]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $tipo = 'curso';

        $curso_slug = $data['curso_slug'];
        $version = $data['version'] ?? '1';
        $version_slug = 'v' . preg_replace('/[^0-9]/', '', $version);

        $ruta_certificado = "/certificados/" . $curso_slug . "_" . $version_slug . "/" . $data['archivo_pdf'];
    }

    /* =====================================
       SI NO EXISTE, BUSCAR EN DIPLOMADOS
    ===================================== */

    if (!$data) {
        $stmt = $pdo->prepare("
            SELECT
                codigo_certificado,
                fecha_emision,
                archivo_pdf,
                nombre,
                apellido_paterno,
                apellido_materno,
                nombre_programa,
                horas,
                fecha_inicio,
                fecha_termino,
                tipo_documento
            FROM dir_diplomados_registros
            WHERE codigo_certificado = ?
            LIMIT 1
        ");

        $stmt->execute([$codigo]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $tipo = 'diplomado';

            $programa_slug = slugTexto($data['nombre_programa']);
            $ruta_certificado = "/certificados/diplomados/" . $programa_slug . "/" . $data['archivo_pdf'];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Validacion de Certificado</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .card-valid {
            border-left: 6px solid #28a745;
        }

        .card-invalid {
            border-left: 6px solid #dc3545;
        }

        .codigo {
            font-size: 14px;
            color: #888;
        }
    </style>
</head>

<body>

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-md-8">

                <div class="card shadow-lg border-0 <?= $data ? 'card-valid' : 'card-invalid' ?>">

                    <div class="card-body text-center p-5">

                        <?php if ($data): ?>

                            <h2 class="text-success mb-4">
                                Certificado valido
                            </h2>

                            <h3 class="fw-bold">
                                <?= htmlspecialchars($data['nombre']) ?>
                                <?= htmlspecialchars($data['apellido_paterno']) ?>
                                <?= htmlspecialchars($data['apellido_materno']) ?>
                            </h3>

                            <?php if ($tipo === 'curso'): ?>

                                <p class="text-muted">Ha aprobado el curso</p>

                                <h4 class="fw-bold mt-3">
                                    <?= htmlspecialchars($data['curso_nombre']) ?>
                                </h4>

                                <hr>

                                <p>
                                    <strong>Duracion:</strong>
                                    <?= htmlspecialchars($data['horas_cronologicas']) ?> horas
                                </p>

                                <p>
                                    <strong>Periodo:</strong><br>
                                    <?= fechaEspanol($data['fecha_inicio']) ?><br>
                                    al<br>
                                    <?= fechaEspanol($data['fecha_fin']) ?>
                                </p>

                            <?php elseif ($tipo === 'diplomado'): ?>

                                <p class="text-muted">
                                    <?= htmlspecialchars($data['tipo_documento'] ?: 'Diploma') ?> registrado correctamente
                                </p>

                                <h4 class="fw-bold mt-3">
                                    <?= htmlspecialchars($data['nombre_programa']) ?>
                                </h4>

                                <hr>

                                <p>
                                    <strong>Duracion:</strong>
                                    <?= htmlspecialchars($data['horas']) ?> horas
                                </p>

                                <p>
                                    <strong>Periodo:</strong><br>
                                    <?= fechaEspanol($data['fecha_inicio']) ?><br>
                                    al<br>
                                    <?= fechaEspanol($data['fecha_termino']) ?>
                                </p>

                            <?php endif; ?>

                            <p>
                                <strong>Fecha de emision:</strong><br>
                                <?= fechaEspanol($data['fecha_emision']) ?>
                            </p>

                            <hr>

                            <p class="codigo">
                                Codigo de verificacion:<br>
                                <strong>
                                    <?= htmlspecialchars($tipo === 'curso' ? $data['codigo_certificado'] : $data['codigo_certificado']) ?>
                                </strong>
                            </p>

                            <?php if (!empty($data['archivo_pdf'])): ?>
                                <a href="<?= htmlspecialchars($ruta_certificado) ?>" class="btn btn-success mt-3"
                                    target="_blank">
                                    Descargar certificado
                                </a>
                            <?php endif; ?>

                        <?php else: ?>

                            <h2 class="text-danger mb-4">
                                Certificado no valido
                            </h2>

                            <p>
                                El codigo ingresado no corresponde a un certificado registrado.
                            </p>

                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="javascript:history.back()" class="btn btn-secondary">
                                Volver
                            </a>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>