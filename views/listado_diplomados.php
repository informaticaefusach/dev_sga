<?php

require_once __DIR__ . '/../db.php';

/* =====================================
   FILTROS
===================================== */

$programa = trim($_GET['programa'] ?? '');
$filtro_aplicado = ($programa !== '');

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
   CONSULTA PRINCIPAL
===================================== */

$registros = [];

if ($filtro_aplicado) {
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
            fecha_emision,
            created_at
        FROM dir_diplomados_registros
        WHERE nombre_programa = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$programa]);
    $registros = $stmt->fetchAll();
}

/* =====================================
   RESUMEN
===================================== */

$total = count($registros);
$aprobados = count(array_filter($registros, fn($r) => !empty($r['aprobado'])));
$reprobados = $total - $aprobados;

?>

<div class="container-fluid">

    <h2 class="mb-4">Listado de diplomados importados</h2>

    <?php if ($filtro_aplicado): ?>
        <div class="alert alert-info">
            Total: <strong>
                <?= $total ?>
            </strong> |
            Aprobados: <strong>
                <?= $aprobados ?>
            </strong> |
            Reprobados: <strong>
                <?= $reprobados ?>
            </strong>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary">
            Selecciona un programa para visualizar los registros importados.
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">

            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="listado_diplomados">

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

                        <a href="index.php?page=listado_diplomados" class="btn btn-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <?php if (!$filtro_aplicado): ?>

                <div class="alert alert-light mb-0">
                    Aun no se ha aplicado ningun filtro.
                </div>

            <?php elseif (empty($registros)): ?>

                <div class="alert alert-warning mb-0">
                    No se encontraron registros para el programa seleccionado.
                </div>

            <?php else: ?>

                <div class="mb-3">
                    <a href="index.php?page=generar_certificados_diplomados&programa=<?= urlencode($programa) ?>"
                        class="btn btn-warning">
                        Generar certificados
                    </a>
                </div>

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
                            <?php foreach ($registros as $r): ?>
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