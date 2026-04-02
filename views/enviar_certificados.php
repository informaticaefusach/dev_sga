<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/mailer.php';

/* =====================================
   FILTROS
===================================== */

$curso_id = $_GET['curso_id'] ?? $_POST['curso_id'] ?? '';
$edicion_id = $_GET['edicion_id'] ?? $_POST['edicion_id'] ?? '';

$mensaje = '';
$error = '';

/* =====================================
   LISTAR CURSOS
===================================== */

$stmt = $pdo->query("
    SELECT id, curso_nombre
    FROM dir_cursos_catalogo
    ORDER BY curso_nombre
");
$cursos = $stmt->fetchAll();

/* =====================================
   LISTAR EDICIONES SEGUN CURSO
===================================== */

$ediciones = [];

if ($curso_id !== '') {
    $stmt = $pdo->prepare("
        SELECT
            e.id,
            e.version,
            e.fecha_inicio,
            e.fecha_fin
        FROM dir_cursos_ediciones e
        WHERE e.curso_id = ?
        ORDER BY e.id DESC
    ");
    $stmt->execute([$curso_id]);
    $ediciones = $stmt->fetchAll();
}

/* =====================================
   ENVIAR CORREOS
===================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $certificadosSeleccionados = $_POST['certificados'] ?? [];

    if ($curso_id === '') {
        $error = "Debes seleccionar un curso.";
    } elseif ($edicion_id === '') {
        $error = "Debes seleccionar una edicion.";
    } elseif (empty($certificadosSeleccionados)) {
        $error = "Debes seleccionar al menos un certificado para enviar.";
    } else {
        $enviados = 0;
        $fallidos = 0;

        foreach ($certificadosSeleccionados as $certificado_id) {
            $stmt = $pdo->prepare("
                SELECT
                    cert.id AS certificado_id,
                    cert.codigo_certificado,
                    cert.archivo_pdf,
                    cert.fecha_emision,
                    a.nombre,
                    a.apellido_paterno,
                    a.apellido_materno,
                    a.email,
                    c.curso_nombre,
                    e.version
                FROM dir_cursos_certificados cert
                JOIN dir_cursos_matriculas m ON m.id = cert.matricula_id
                JOIN dir_cursos_alumnos a ON a.id = m.alumno_id
                JOIN dir_cursos_ediciones e ON e.id = m.edicion_id
                JOIN dir_cursos_catalogo c ON c.id = e.curso_id
                WHERE cert.id = ?
                  AND e.id = ?
                  AND c.id = ?
            ");
            $stmt->execute([$certificado_id, $edicion_id, $curso_id]);
            $row = $stmt->fetch();

            if (!$row) {
                $fallidos++;
                continue;
            }

            $asunto = 'Entrega de Certificado / Diploma - ' . $row['curso_nombre'] . ' - Capacitacion USACH';

            if (empty($row['email'])) {
                $stmtLog = $pdo->prepare("
                    INSERT INTO dir_cursos_envios_certificados
                    (certificado_id, email_destino, asunto, estado, mensaje_error)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtLog->execute([
                    $row['certificado_id'],
                    '',
                    $asunto,
                    'error',
                    'El alumno no tiene email registrado.'
                ]);

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
                    'asunto' => $asunto,
                    'ruta_plantilla' => __DIR__ . '/../templates/email/certificado.html',
                    'variables' => [
                        'nombre_participante' => $nombreCompleto,
                        'nombre_del_curso_o_diplomado' => $row['curso_nombre'],
                        'tipo_documento' => 'Certificado de Aprobacion',
                        'codigo_certificado' => $row['codigo_certificado'],
                        'link_certificado' => $link_certificado
                    ]
                ]);

                $stmtLog = $pdo->prepare("
                    INSERT INTO dir_cursos_envios_certificados
                    (certificado_id, email_destino, asunto, estado, mensaje_error)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtLog->execute([
                    $row['certificado_id'],
                    $row['email'],
                    $asunto,
                    'enviado',
                    null
                ]);

                $enviados++;

            } catch (Exception $e) {
                $stmtLog = $pdo->prepare("
                    INSERT INTO dir_cursos_envios_certificados
                    (certificado_id, email_destino, asunto, estado, mensaje_error)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtLog->execute([
                    $row['certificado_id'],
                    $row['email'],
                    $asunto,
                    'error',
                    $e->getMessage()
                ]);

                $fallidos++;
            }
        }

        $mensaje = "Proceso completado. Enviados: $enviados. Fallidos: $fallidos.";
    }
}

/* =====================================
   LISTAR CERTIFICADOS SEGUN EDICION
===================================== */

$certificados = [];

if ($curso_id !== '' && $edicion_id !== '') {
    $stmt = $pdo->prepare("
        SELECT
            cert.id AS certificado_id,
            cert.codigo_certificado,
            cert.archivo_pdf,
            cert.fecha_emision,
            a.nombre,
            a.apellido_paterno,
            a.apellido_materno,
            a.email,
            (
                SELECT ec.fecha_envio
                FROM dir_cursos_envios_certificados ec
                WHERE ec.certificado_id = cert.id
                  AND ec.estado = 'enviado'
                ORDER BY ec.fecha_envio DESC
                LIMIT 1
            ) AS ultimo_envio
        FROM dir_cursos_certificados cert
        JOIN dir_cursos_matriculas m ON m.id = cert.matricula_id
        JOIN dir_cursos_alumnos a ON a.id = m.alumno_id
        JOIN dir_cursos_ediciones e ON e.id = m.edicion_id
        JOIN dir_cursos_catalogo c ON c.id = e.curso_id
        WHERE c.id = ?
          AND e.id = ?
        ORDER BY a.nombre, a.apellido_paterno, a.apellido_materno
    ");
    $stmt->execute([$curso_id, $edicion_id]);
    $certificados = $stmt->fetchAll();
}

?>

<div class="container-fluid">

    <h2 class="mb-4">Enviar certificados por correo</h2>

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
                <input type="hidden" name="page" value="enviar_certificados">

                <div class="row">
                    <div class="col-md-4">
                        <label>Curso</label>
                        <select name="curso_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Seleccione un curso</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?= $curso['id'] ?>" <?= $curso_id == $curso['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curso['curso_nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Edicion</label>
                        <select name="edicion_id" class="form-control" <?= $curso_id === '' ? 'disabled' : '' ?>>
                            <option value="">Seleccione una edicion</option>
                            <?php foreach ($ediciones as $edicion): ?>
                                <option value="<?= $edicion['id'] ?>" <?= $edicion_id == $edicion['id'] ? 'selected' : '' ?>>
                                    Edicion
                                    <?= htmlspecialchars($edicion['id']) ?>
                                    <?php if (!empty($edicion['version'])): ?>
                                        - Version
                                        <?= htmlspecialchars($edicion['version']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($edicion['fecha_inicio']) || !empty($edicion['fecha_fin'])): ?>
                                        (
                                        <?= htmlspecialchars($edicion['fecha_inicio']) ?> -
                                        <?= htmlspecialchars($edicion['fecha_fin']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            Filtrar
                        </button>

                        <a href="index.php?page=enviar_certificados" class="btn btn-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <?php if ($curso_id === '' || $edicion_id === ''): ?>

                <div class="alert alert-info mb-0">
                    Selecciona un curso y una edicion para ver los certificados disponibles.
                </div>

            <?php elseif (empty($certificados)): ?>

                <div class="alert alert-warning mb-0">
                    No hay certificados generados para la edicion seleccionada.
                </div>

            <?php else: ?>

                <form method="POST" action="index.php?page=enviar_certificados">
                    <input type="hidden" name="curso_id" value="<?= htmlspecialchars($curso_id) ?>">
                    <input type="hidden" name="edicion_id" value="<?= htmlspecialchars($edicion_id) ?>">

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">
                            Enviar certificados seleccionados
                        </button>

                        <a href="index.php?page=matriculas&edicion_id=<?= urlencode($edicion_id) ?>"
                            class="btn btn-secondary">
                            Volver a matriculas
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
                                    <th>Codigo</th>
                                    <th>Fecha emision</th>
                                    <th>Ultimo envio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificados as $c): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="certificados[]" value="<?= $c['certificado_id'] ?>">
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(trim($c['nombre'] . ' ' . $c['apellido_paterno'] . ' ' . $c['apellido_materno'])) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($c['email'] ?: 'Sin email') ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($c['codigo_certificado']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($c['fecha_emision']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($c['ultimo_envio'] ?: 'No enviado') ?>
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
        const checkboxes = document.querySelectorAll('input[name="certificados[]"]');
        checkboxes.forEach(function (cb) {
            cb.checked = source.checked;
        });
    }
</script>