<?php

require_once __DIR__ . '/../db.php';

$curso_id = $_GET['curso_id'] ?? '';

/* =============================
   LISTA DE CURSOS
============================= */

$cursos = $pdo->query("
SELECT id, curso_nombre
FROM dir_cursos_catalogo
ORDER BY curso_nombre
")->fetchAll();


/* =============================
   CONSULTA EDICIONES
============================= */

$sql = "
SELECT
e.id,
c.curso_nombre,
e.version,
e.fecha_inicio,
e.fecha_fin,
e.modalidad,
e.cupo_maximo,
e.estado
FROM dir_cursos_ediciones e
JOIN dir_cursos_catalogo c ON c.id = e.curso_id
WHERE 1=1
";

$params = [];

if ($curso_id != '') {

    $sql .= " AND c.id = ?";
    $params[] = $curso_id;

}

$sql .= " ORDER BY e.fecha_inicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$ediciones = $stmt->fetchAll();

?>

<div class="container-fluid">

    <h2 class="mb-4">Ediciones de Curso</h2>


    <!-- FILTRO -->

    <div class="card shadow mb-4">

        <div class="card-body">

            <form method="GET">

                <input type="hidden" name="page" value="ediciones">

                <div class="row">

                    <div class="col-md-4">

                        <label>Curso</label>

                        <select name="curso_id" class="form-control">

                            <option value="">Todos los cursos</option>

                            <?php foreach ($cursos as $c): ?>

                                <option value="<?= $c['id'] ?>" <?= $curso_id == $c['id'] ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($c['curso_nombre']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-4 d-flex align-items-end">

                        <button class="btn btn-primary me-2">

                            Filtrar

                        </button>

                        <a href="index.php?page=ediciones" class="btn btn-secondary">

                            Limpiar

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- BOTON NUEVA EDICION -->

    <div class="mb-3">

        <a href="index.php?page=nueva_edicion" class="btn btn-success">

            <i class="fas fa-plus"></i>
            Nueva edición

        </a>

    </div>



    <!-- TABLA -->

    <div class="card shadow">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>
                            <th>Curso</th>
                            <th>Versión</th>
                            <th>Fecha inicio</th>
                            <th>Fecha término</th>
                            <th>Modalidad</th>
                            <th>Cupo</th>
                            <th>Estado</th>
                            <th width="200">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($ediciones)): ?>

                            <tr>

                                <td colspan="9" class="text-center">

                                    No hay ediciones registradas

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($ediciones as $e): ?>

                                <tr>

                                    <td>
                                        <?= $e['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($e['curso_nombre']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($e['version']) ?>
                                    </td>

                                    <td>
                                        <?= $e['fecha_inicio'] ?>
                                    </td>

                                    <td>
                                        <?= $e['fecha_fin'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($e['modalidad']) ?>
                                    </td>

                                    <td>
                                        <?= $e['cupo_maximo'] ?>
                                    </td>

                                    <td>

                                        <?php if ($e['estado'] == 1): ?>

                                            <span class="badge bg-success">
                                                Activa
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                Inactiva
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <a href="index.php?page=editar_edicion&id=<?= $e['id'] ?>"
                                            class="btn btn-sm btn-warning">

                                            <i class="fas fa-edit"></i>

                                        </a>

                                        <a href="index.php?page=matriculas&edicion_id=<?= $e['id'] ?>"
                                            class="btn btn-sm btn-info">

                                            <i class="fas fa-users"></i>

                                        </a>

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