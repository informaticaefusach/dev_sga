<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

/* ================================
   FILTROS
================================ */

$curso_id = $_GET['curso_id'] ?? '';
$edicion_id = $_GET['edicion_id'] ?? '';
$estado = $_GET['estado'] ?? '';

/* ================================
   CURSOS
================================ */

$cursos = $pdo->query("
SELECT id, curso_nombre
FROM dir_cursos_catalogo
ORDER BY curso_nombre
")->fetchAll();

/* ================================
   EDICIONES
================================ */

$ediciones = $pdo->query("
SELECT 
e.id,
c.curso_nombre
FROM dir_cursos_ediciones e
JOIN dir_cursos_catalogo c ON c.id = e.curso_id
ORDER BY e.id DESC
")->fetchAll();

/* ================================
   CONSULTA PRINCIPAL
================================ */

$sql = "
SELECT
m.id,
c.curso_nombre,
c.curso_slug,
e.id AS edicion_id,
e.version,
a.nombre,
a.apellido_paterno,
a.apellido_materno,
a.email,
m.nota_final,
m.aprobado,

/* 🔥 CERTIFICADO */
cert.id as certificado_generado,
cert.archivo_pdf

FROM dir_cursos_matriculas m

JOIN dir_cursos_alumnos a ON a.id = m.alumno_id
JOIN dir_cursos_ediciones e ON e.id = m.edicion_id
JOIN dir_cursos_catalogo c ON c.id = e.curso_id

LEFT JOIN dir_cursos_certificados cert 
ON cert.matricula_id = m.id

WHERE 1=1
";

$params = [];

/* FILTROS */

if ($curso_id != '') {
    $sql .= " AND c.id = ?";
    $params[] = $curso_id;
}

if ($edicion_id != '') {
    $sql .= " AND e.id = ?";
    $params[] = $edicion_id;
}

if ($estado == 'aprobados') {
    $sql .= " AND m.aprobado = 1";
}

if ($estado == 'reprobados') {
    $sql .= " AND m.aprobado = 0";
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$matriculas = $stmt->fetchAll();

/* ================================
   RESUMEN
================================ */

$total = count($matriculas);
$aprobados = count(array_filter($matriculas, fn($m) => $m['aprobado']));
$reprobados = $total - $aprobados;

$version = $m['version'] ?? '1';
$version_slug = 'v' . preg_replace('/[^0-9]/', '', $version);


?>

<div class="container-fluid">

    <h2 class="mb-4">Matrículas</h2>

    <!-- =============================
RESUMEN
============================= -->

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

    <!-- =============================
FILTROS
============================= -->

    <div class="card shadow mb-4">
        <div class="card-body">

            <form method="GET">

                <input type="hidden" name="page" value="matriculas">

                <div class="row">

                    <div class="col-md-3">
                        <label>Curso</label>
                        <select name="curso_id" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $curso_id == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['curso_nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Edición</label>
                        <select name="edicion_id" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($ediciones as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $edicion_id == $e['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['curso_nombre']) ?> - Edición
                                    <?= $e['id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="aprobados" <?= $estado == 'aprobados' ? 'selected' : '' ?>>Aprobados</option>
                            <option value="reprobados" <?= $estado == 'reprobados' ? 'selected' : '' ?>>Reprobados</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary me-2">Filtrar</button>
                        <a href="index.php?page=matriculas" class="btn btn-secondary">Limpiar</a>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- =============================
BOTONES
============================= -->

    <div class="mb-3">

        <a href="index.php?page=importar_alumnos" class="btn btn-success">
            Importar Excel
        </a>

        <button class="btn btn-info">
            Exportar Excel
        </button>

        <?php if ($edicion_id): ?>

            <a href="index.php?page=generar_certificados&edicion_id=<?= $edicion_id ?>" class="btn btn-warning">

                Generar certificados

            </a>

        <?php else: ?>

            <button class="btn btn-warning" disabled>
                Seleccione una edición
            </button>

        <?php endif; ?>

    </div>

    <!-- =============================
TABLA
============================= -->

    <div class="card shadow">
        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Curso</th>
                            <th>Alumno</th>
                            <th>Email</th>
                            <th>Nota</th>
                            <th>Estado</th>
                            <th>Certificado</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($matriculas)): ?>

                            <tr>
                                <td colspan="7" class="text-center">
                                    No hay resultados
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($matriculas as $m): ?>

                                <tr>

                                    <td>
                                        <?= $m['id'] ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($m['curso_nombre']) ?>
                                        </strong><br>
                                        <small>Edición
                                            <?= $m['edicion_id'] ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($m['nombre']) ?>
                                        <?= htmlspecialchars($m['apellido_paterno']) ?>
                                        <?= htmlspecialchars($m['apellido_materno']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($m['email']) ?>
                                    </td>

                                    <td>
                                        <?php if ($m['nota_final'] !== null): ?>
                                            <span
                                                class="<?= $m['nota_final'] >= 4 ? 'text-success fw-bold' : 'text-danger fw-bold' ?>">
                                                <?= $m['nota_final'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin nota</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($m['aprobado']): ?>
                                            <span class="badge bg-success">Aprobado</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Reprobado</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>

                                        <?php if ($m['certificado_generado']): ?>

                                            <?php
                                            $version = $m['version'] ?? '1';
                                            $version_slug = 'v' . preg_replace('/[^0-9]/', '', $version);

                                            $slug_carpeta = $m['curso_slug'] . "_" . $version_slug;

                                            $ruta_fisica = BASE_PATH . "/certificados/" . $slug_carpeta . "/" . $m['archivo_pdf'];

                                            if (file_exists($ruta_fisica)) {
                                                $ruta_certificado = base_url() . "/certificados/" . $slug_carpeta . "/" . $m['archivo_pdf'];
                                            } else {
                                                $ruta_certificado = "#";
                                            }
                                            ?>

                                            <?php if ($ruta_certificado != "#"): ?>

                                                <span class="badge bg-success">Generado</span><br>

                                                <a href="<?= $ruta_certificado ?>" class="btn btn-success" target="_blank">
                                                    Descargar
                                                </a>

                                            <?php else: ?>

                                                <span class="badge bg-warning">Archivo no encontrado</span>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">Pendiente</span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>
        </div>
    </div>

</div>