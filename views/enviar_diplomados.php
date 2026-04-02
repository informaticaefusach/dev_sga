<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/mailer.php';

/* =====================================
   FILTRO
===================================== */

$programa = trim($_GET['programa'] ?? $_POST['programa'] ?? '');

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
   ENVIAR CORREOS
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registrosSeleccionados = $_POST['registros'] ?? [];

    if ($programa === '') {
        $error = "Debes seleccionar un programa.";
    } elseif (empty($registrosSeleccionados)) {
        $error = "Debes seleccionar al menos un diploma para enviar.";
    } else {
        $enviados = 0;
        $fallidos = 0;

        foreach ($registrosSeleccionados as $registro_id) {
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
                    codigo_certificado,
                    archivo_pdf,
                    fecha_emision
                FROM dir_diplomados_registros
                WHERE id = ?
                  AND nombre_programa = ?
            ");
            $stmt->execute([$registro_id, $programa]);
            $row = $stmt->fetch();

            if (!$row) {
                $fallidos++;
                continue;
            }

            if (empty($row['email'])) {
                $fallidos++;
                continue;
            }

            if (empty($row['codigo_certificado']) || empty($row['archivo_pdf'])) {
                $fallidos++;
                continue;
            }

            $nombreCompleto = trim(
                $row['nombre'] . ' ' .
                $row['apellido_paterno'] . ' ' .
                $row['apellido_materno']
            );

            $link_certificado = 'https://cert.capusach.cl/index.php?page=verificar_certificado&codigo=' . $row['codigo_certificado'];

            try {
                enviarCorreoCertificado([
                    'destinatario' => $row['email'],
                    'nombre_destinatario' => $nombreCompleto,
                    'asunto' => 'Entrega de Diploma - ' . $row['nombre_programa'] . ' - Capacitacion USACH',
                    'ruta_plantilla' => __DIR__ . '/../templates/email/certificado.html',
                    'variables' => [
                        'nombre_participante' => $nombreCompleto,
                        'nombre_del_curso_o_diplomado' => $row['nombre_programa'],
                        'tipo_documento' => 'Diploma',
                        'codigo_certificado' => $row['codigo_certificado'],
                        'link_certificado' => $link_certificado
                    ]
                ]);

                $enviados++;

            } catch (Exception $e) {
                $fallidos++;
            }
        }

        $mensaje = "Proceso completado. Enviados: $enviados. Fallidos: $fallidos.";
    }
}

/* =====================================
   LISTADO DEL PROGRAMA SELECCIONADO
===================================== */

$registros = [];

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
            codigo_certificado,
            archivo_pdf,
            fecha_emision
        FROM dir_diplomados_registros
        WHERE nombre_programa = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$programa]);
    $registros = $stmt->fetchAll();
}

?>

<div class="container-fluid">

    <h2 class="mb-4">Enviar diplomas por correo</h2>

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
                <input type="hidden" name="page" value="enviar_diplomados">

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

                        <a href="index.php?page=enviar_diplomados" class="btn btn-secondary">
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
                    Selecciona un programa para visualizar los diplomas generados.
                </div>

            <?php elseif (empty($registros)): ?>

                <div class="alert alert-warning mb-0">
                    No se encontraron registros para el programa seleccionado.
                </div>

            <?php else: ?>

                <form method="POST" action="index.php?page=enviar_diplomados">
                    <input type="hidden" name="programa" value="<?= htmlspecialchars($programa) ?>">

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            Enviar diplomas seleccionados
                        </button>

                        <a href="index.php?page=listado_diplomados&programa=<?= urlencode($programa) ?>"
                            class="btn btn-secondary">
                            Volver al listado
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" onclick="toggleTodos(this)">
                                    </th>
                                    <th>Alumno</th>
                                    <th>Email</th>
                                    <th>RUT</th>
                                    <th>Nota</th>
                                    <th>Estado</th>
                                    <th>Codigo</th>
                                    <th>Diploma</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $r): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="registros[]" value="<?= $r['id'] ?>"
                                                <?= (empty($r['archivo_pdf']) || empty($r['codigo_certificado']) || empty($r['email'])) ? 'disabled' : '' ?>>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(trim($r['nombre'] . ' ' . $r['apellido_paterno'] . ' ' . $r['apellido_materno'])) ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['email']) ?></td>
                                        <td><?= htmlspecialchars($r['rut']) ?></td>
                                        <td><?= htmlspecialchars($r['nota']) ?></td>
                                        <td>
                                            <?php if (!empty($r['aprobado'])): ?>
                                                <span class="badge bg-success">Aprobado</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Reprobado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($r['codigo_certificado'] ?? '') ?></td>
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
                </form>

            <?php endif; ?>

        </div>
    </div>

</div>

<script>
    function toggleTodos(source) {
        const checkboxes = document.querySelectorAll('input[name="registros[]"]');
        checkboxes.forEach(function (cb) {
            if (!cb.disabled) {
                cb.checked = source.checked;
            }
        });
    }
</script>