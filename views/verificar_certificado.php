<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* 🔥 IMPORTANTE: ESTO LO HACE PÚBLICO (SIN SIDEBAR) */
$public = true;

require_once __DIR__ . '/../db.php';

/* =============================
   FUNCION SLUG
============================= 
function generarSlug($texto)
{
    $texto = strtolower($texto);

    // reemplazar acentos por _
    $texto = preg_replace('/[áéíóúñü]/u', '_', $texto);

    // reemplazar todo lo que no sea válido por _
    $texto = preg_replace('/[^a-z0-9]/', '_', $texto);

    // ⚠️ NO limpiar dobles underscores
    return trim($texto, '_');
}

/* =============================
   VALIDAR CÓDIGO
============================= */

$codigo = trim($_GET['codigo'] ?? '');

if (!preg_match('/^[A-Z0-9]+$/', $codigo)) {
    $codigo = '';
}

$data = null;

if ($codigo) {

    $stmt = $pdo->prepare("
    SELECT 
        cert.codigo_certificado,
        cert.fecha_emision,
        cert.archivo_pdf,
        a.nombre,
        a.apellido_paterno,
        a.apellido_materno,
        c.curso_nombre,
        c.horas_cronologicas,
        e.fecha_inicio,
        e.fecha_fin
    FROM dir_cursos_certificados cert
    JOIN dir_cursos_matriculas m ON m.id = cert.matricula_id
    JOIN dir_cursos_alumnos a ON a.id = m.alumno_id
    JOIN dir_cursos_ediciones e ON e.id = m.edicion_id
    JOIN dir_cursos_catalogo c ON c.id = e.curso_id
    WHERE cert.codigo_certificado = ?
    ");

    $stmt->execute([$codigo]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =============================
   FORMATO FECHA
============================= */

function fechaEspanol($fecha)
{
    if (!$fecha)
        return '';

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





?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Validación de Certificado</title>

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

                            <?php
                            // 🔥 GENERAR SLUG DEL CURSO
                            $curso_nombre = $data['curso_nombre'];

                            // Convertir a slug
                            $curso_slug = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($data['curso_nombre']));
                            $ruta_certificado = "certificados/" . $curso_slug . "/" . $data['archivo_pdf'];
                            ?>

                            <h2 class="text-success mb-4">
                                ✔ Certificado válido
                            </h2>

                            <h3 class="fw-bold">
                                <?= htmlspecialchars($data['nombre']) ?>
                                <?= htmlspecialchars($data['apellido_paterno']) ?>
                                <?= htmlspecialchars($data['apellido_materno']) ?>
                            </h3>

                            <p class="text-muted">Ha aprobado el curso</p>

                            <h4 class="fw-bold mt-3">
                                <?= htmlspecialchars($data['curso_nombre']) ?>
                            </h4>

                            <hr>

                            <p><strong>Duración:</strong>
                                <?= $data['horas_cronologicas'] ?> horas
                            </p>

                            <p>
                                <strong>Periodo:</strong><br>
                                <?= fechaEspanol($data['fecha_inicio']) ?><br>
                                al<br>
                                <?= fechaEspanol($data['fecha_fin']) ?>
                            </p>

                            <p>
                                <strong>Fecha de emisión:</strong><br>
                                <?= fechaEspanol($data['fecha_emision']) ?>
                            </p>

                            <hr>

                            <p class="codigo">
                                Código de verificación:<br>
                                <strong>
                                    <?= htmlspecialchars($data['codigo_certificado']) ?>
                                </strong>
                            </p>

                            <?php if (!empty($data['archivo_pdf'])): ?>
                                <a href="<?= $ruta_certificado ?>" class="btn btn-success mt-3" target="_blank">
                                    Descargar certificado
                                </a>
                            <?php endif; ?>

                        <?php else: ?>

                            <h2 class="text-danger mb-4">
                                ❌ Certificado no válido
                            </h2>

                            <p>
                                El código ingresado no corresponde a un certificado registrado.
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